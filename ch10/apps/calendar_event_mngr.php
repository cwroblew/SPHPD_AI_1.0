<?php
   
   require_once "calendar.conf";
   require_once $THEME_CLASS;
   require_once $EVENT_CLASS;
   require_once $MESSAGE_CLASS;
   
   
   class calendarEventMngr extends PHPApplication {

     function run()
     {    
         $event_mode = $this->getRequestField('event_mode');        
         $themeObj = new Theme($this->dbi,null,'pr_tool');
         $this->themeObj = $themeObj;
         $this->theme = $themeObj->getUserTheme($this->getUID());
         $this->displayCalendarEventMngrHome(CALENDAR_EVENT_TEMPLATE, $event_mode);
     }
     
     function authorize()
     {
         return true;
     }

     function displayCalendarEventMngrHome($templateFile = null, $mode = null)
     {
         global $REL_APP_PATH;
         $event_id = $this->getRequestField('event_id');
         $date = $this->getRequestField('date');
         $step = $this->getRequestField('step');
         
         $mode = empty($mode) ? 'add' : $mode;         
         
         $mode = strtolower($mode);
         
         if (!strcmp($mode, 'add') && $step == 2)
         {
            $this->addEvent();
            return;
         }
         
         if (!strcmp($mode, 'modify') && $step == 2)
         {
            $this->modifyEvent();
            return;	
         }
         
         if (!strcmp($mode, 'delete'))
         {
            $this->deleteEvent();
            return;	
         }
         
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', $templateFile);
         
         $template->set_block('fh', 'mainBlock', 'main');
         $template->set_block('mainBlock', 'eventBlock', 'event');
         $template->set_block('mainBlock', 'viewBlock', 'view');
         
         $template->set_var('MODE', ucwords($mode));
         
         $eventObj = new Event($this->dbi); 
         $events = $eventObj->getOwnEvents($this->getUID(), $date);
         
         if(!empty($event_id))
         {
           $selectedEvent = new Event($this->dbi, $event_id);
           $template->set_var('MOD_EID', $event_id);
           $viewers = $selectedEvent->getViewers();
           if (!empty($viewers))
           {   
              if ((count($viewers) == 1) && (in_array($this->getUID(), $viewers)))
              {
              	$template->set_var('OTHER_CHK_OPT', null);
              }
              else
              {
                $template->set_var('OTHER_CHK_OPT', 'checked');
              }  
              if (in_array(0, $viewers))
              {
                 $view_every = true;
                 $template->set_var('VIEW_EVERY_CHOSEN', 'selected');
              }
           }
           $repMode = $selectedEvent->getRepeatMode();
           
           if (empty($repMode))
           {
           	$template->set_var('WEEK_CHECKED', null);	
           	$template->set_var('MONTH_CHECKED', null);
           	$template->set_var('YEAR_CHECKED', null);
           }
           else if (strlen($repMode) == 3)
           {
              $template->set_var('WEEK_CHECKED', 'checked');	
           }
           else if (strlen($repMode) <= 2)
           {
                 $template->set_var('MONTH_CHECKED', 'checked');		
           }
           else if (strlen($repMode) > 3)
           {
                 $template->set_var('YEAR_CHECKED', 'checked');		
           }
           
         }	
         
         
         if (!empty($events))
         {
             while (list($eventId, $event_title) = each($events))
             {
               $template->set_var('EVENT_ID', $eventId);
               $template->set_var('EVENT_TITLE', stripslashes($event_title));
               $template->set_var('CHECKED', ($event_id == $eventId) ? 'checked' : null);
               $template->parse('event','eventBlock', true);
             }
         }    
         else
         {
             $template->set_var('event', $this->getMessage('EVENT_NOT_FOUND'));	
         }
         global $USER_DB_URL;
         $authDBI = new DBI($USER_DB_URL);
         $userObj = new User($authDBI);
         $users = $userObj->getUserList();
         asort($users);
         reset($users);
         
         while(list($uid, $uname) = each($users))
         {
            if ($uid != $this->getUID())
            {
               list($name, $host) = explode('@', $uname);
               $template->set_var('VIEW_UID', $uid);
               $template->set_var('VIEW_NAME', ucfirst($name));
               if(!empty($viewers))
               {   
                if (in_array($uid, $viewers))
                {
                   $template->set_var('VIEW_CHOSEN',  (isset($view_every) && $view_every) ? null : 'SELECTED');	
                }
                else
                {
                   $template->set_var('VIEW_CHOSEN', null);		
                }
               }   
               else
               {
                $template->set_var('VIEW_CHOSEN', null);		
               }
               $template->parse('view', 'viewBlock', true);
            }   
         }
         
         $template->set_var(array(
                                  'EVENT_TITLE'         =>       isset($selectedEvent) && $selectedEvent ? $selectedEvent->getEventTitle() : null,
                                  'EVENT_DATE'          =>       isset($selectedEvent) && $selectedEvent ? $selectedEvent->getEventDate() : $date,
                                  'EVENT_DESC'          =>       isset($selectedEvent) && $selectedEvent ? $selectedEvent->getEventDesc() : null,
                                  'FLAG'                =>       mktime(),
                                  'REM_CHECKED'         =>       isset($selectedEvent) && $selectedEvent ? ($selectedEvent->getEventReminder() ? 'checked' : null) : null
                                 )        
                           );
         global $REL_APP_PATH;
         $template->set_var('CALENDAR_EVENT_MNGR', $REL_APP_PATH.'/'.CALENDAR_EVENT_MNGR);
         $template->set_var('DATE', $date);
         $template->set_var('CALENDAR_MNGR', $REL_APP_PATH.'/'.CALENDAR_MNGR);
         $this->showContents($template->parse('mblock', 'mainBlock'));
     }
     
     function deleteEvent()
     {
        $del_eid = $this->getRequestField('del_eid');
        $eventObj = new Event($this->dbi);
        
        $msg_id = $eventObj->getEventReminder($del_eid);
        global $INTRANET_DB_URL;
        $intraDB = new DBI($INTRANET_DB_URL);
        $msgObj = new Message($intraDB);
        
        $msgObj->deleteMessage($msg_id);
        $msgObj->deleteViewers($msg_id);
        
        $eventObj->deleteViewers($del_eid);        
        $eventObj->deleteRepeatMode($del_eid);
        $status = $eventObj->deleteEvent($del_eid);
        
        $this->showContents($this->getMessage($status ? 'EVENT_DELETED' : 'EVENT_NOT_DELETED'));
     }
     
     function modifyEvent()
     {
         $event_date = $this->getRequestField('event_date');
         $event_title = $this->getRequestField('event_title');
         $event_desc = $this->getRequestField('event_desc');
         $rem = $this->getRequestField('rem');
         $flag = $this->getRequestField('flag');
         $viewers = $this->getRequestField('viewers');
         $show_others = $this->getRequestField('show_others');
         $mod_eid = $this->getRequestField('mod_eid');
         $rep_mode = $this->getRequestField('rep_mode');
         
         
         if (strcmp($show_others, 'ON'))
         {
           $viewers = array($this->getUID());
         }
         else
         {
           array_push($viewers, $this->getUID());	
         }
         
         if (empty($event_date) || empty($event_title))
         {
             $this->alert('INPUT_MISSING');
             return;
         }
         
         list($eventMonth, $eventDay, $eventYear) = explode("-", $event_date);
         
         if (!checkDate($eventMonth, $eventDay, $eventYear))
         {
             $this->alert('INVALID_DATE');
             return;	
         }
         
         $eventObj = new Event($this->dbi);
         $msg_id = $eventObj->getEventReminder($mod_eid);

         
         global $INTRANET_DB_URL;
         $intraDB = new DBI($INTRANET_DB_URL);
         $msgObj = new Message($intraDB);
         if ($rem)
         {
             if ($msg_id > 0)
             {
               $msgObj->modifyMessage($msg_id, $event_title, mktime(0,0,0, $eventMonth, $eventDay, $eventYear), $event_desc, mktime());
             }
             else
             {
               $msg_id = $msgObj->addMessage($event_title, mktime(0,0,0, $eventMonth, $eventDay, $eventYear), $event_desc, mktime(), $this->getUID(), 0);
               $msgObj->addViewer($msg_id, array($this->getUID())); 
             }  
         }
         else
         {
             $msgObj->deleteMessage($msg_id);	
             $msgObj->deleteViewers($msg_id);	
         }
         
         $params = array(
                         'EVENT_ID'          =>   $mod_eid,
                         'USER_ID'           =>   $this->getUID(),
                         'EVENT_TITLE'       =>   $event_title,
                         'EVENT_DATE'        =>   $event_date,
                         'EVENT_DESC'        =>   $event_desc,
                         'REMINDER_ID'       =>   $rem ? $msg_id : 0,
                         'FLAG'              =>   $this->getUID().$flag
                        );
                        
         $status = $eventObj->modifyEvent($params);
         if ($status)
         {
             $eventObj->deleteViewers($mod_eid);
             $eventObj->addViewer($mod_eid, $viewers);
             $eventObj->deleteRepeatMode($mod_eid);
             if (!empty($rep_mode))
             {
                switch($rep_mode)
                {
                   case 1:
                      $eventObj->addRepeatMode($mod_eid, date("d", mktime(0,0,0, $eventMonth, $eventDay, $eventYear)));
                      break;
                   case 2:
                      $eventObj->addRepeatMode($mod_eid, date("m-d", mktime(0,0,0, $eventMonth, $eventDay, $eventYear)));
                      break;	
                   case 3:                      
                      $eventObj->addRepeatMode($mod_eid, date("D", mktime(0,0,0, $eventMonth, $eventDay, $eventYear)));
                      break;   
                }
             }
         }
         if ($rem && !$status)
         {
             $msgObj->deleteMessage($msg_id);
             $msgObj->deleteViewers($msg_id);
         }
         $this->showContents($this->getMessage($status ? 'EVENT_MODIFIED' : 'EVENT_NOT_MODIFIED'));
     }
     
     function addEvent()
     {
         $event_date = $this->getRequestField('event_date');
         $event_title = $this->getRequestField('event_title');
         $event_desc = $this->getRequestField('event_desc');
         $rem = $this->getRequestField('rem');
         $flag = $this->getRequestField('flag');
         $viewers = $this->getRequestField('viewers');
         $show_others = $this->getRequestField('show_others');         
         $rep_mode = $this->getRequestField('rep_mode');
         
         if (strcmp($show_others, 'ON'))
         {
           $viewers = array($this->getUID());
         }
         else
         {
           array_push($viewers, $this->getUID());	
         }
         
         if (empty($event_date) || empty($event_title))
         {
             $this->alert('INPUT_MISSING');
             return;
         }
         
         list($eventMonth, $eventDay, $eventYear) = explode("-", $event_date);
         
         if (!checkDate($eventMonth, $eventDay, $eventYear))
         {
             $this->alert('INVALID_DATE');
             return;	
         }
         
         if ($rem)
         {
             global $INTRANET_DB_URL;
             $intraDB = new DBI($INTRANET_DB_URL);
             $msgObj = new Message($intraDB);
             $msg_id  = $msgObj->addMessage($event_title, mktime(0,0,0, $eventMonth, $eventDay, $eventYear), $event_desc, mktime(), $this->getUID(), 0);
             $msgObj->addViewer($msg_id, array($this->getUID()));
             $msgObj->addViewer($msg_id, $viewers);
         }
         
         $eventObj = new Event($this->dbi);
         
         $params = array(
                         'EVENT_ID'          =>   'null',
                         'USER_ID'           =>   $this->getUID(),
                         'EVENT_TITLE'       =>   $event_title,
                         'EVENT_DATE'        =>   $event_date,
                         'EVENT_DESC'        =>   $event_desc,
                         'REMINDER_ID'       =>   $rem ? $msg_id : 0,
                         'FLAG'              =>   $this->getUID().$flag
                        );
                        
         $status = $eventObj->addEvent($params);
         if ($status)
         {
             $eventObj->addViewer($status, $viewers);
             if (!empty($rep_mode))
             {
                switch($rep_mode)
                {
                   case 0:
                      $eventObj->addRepeatMode($status, date("D", mktime()));
                      break;
                   case 1:
                      $eventObj->addRepeatMode($status, date("d", mktime()));
                      break;
                   case 2:
                      $eventObj->addRepeatMode($status, date("m-d", mktime()));
                      break;	
                }
             }
         }
         if ($rem && !$status)
         {
             $msgObj->deleteMessage($msg_id);
             $msgObj->deleteViewers($msg_id);
         }
         
         
         $this->showContents($this->getMessage($status ? 'EVENT_ADDED' : 'EVENT_NOT_ADDED'));
     }                 
                        
     function showContents($contents)
     {
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;         
         global $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         global $REL_APP_PATH, $TEMPLATE_DIR;

         $themeTemplate = new Template($THEME_TEMPLATE_DIR);
         $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
         $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
         $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
         
         $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
         $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
         $themeTemplate->set_var('PHOTO', $photo);
         $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
         $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
         $template = new Template($TEMPLATE_DIR);
         $template->set_file('fh1', STATUS_TEMPLATE);
         $template->set_block('fh1','mainBlock','mblock');

         $template->set_var('CALENDAR_MNGR', $REL_APP_PATH.'/'.CALENDAR_MNGR);

         $template->set_var('STATUS_MESSAGE', $contents);
         $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));
         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');	
     }
   }//class
   
   $thisApp = new calendarEventMngr(array
                                           ('app_name'             =>  $APPLICATION_NAME,
                                            'app_version'           => '1.0.0',
                                            'app_type'              => 'WEB',
                                            'app_auto_connect'      => TRUE,
                                            'app_auto_authorize'    => TRUE,
                                            'app_auto_chk_session'  => TRUE,
                                            'app_debugger'          => $OFF,
                                            'app_db_url'            => $CALENDAR_DB_URL,
                                           )
                                     );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
