<?php

   require_once "home.conf";
   require_once $MESSAGE_CLASS;
   require_once $INTRANET_USER_CLASS;

   /* Session variables must be defined before session_start() method is called */
   $SESSION_USERNAME = null;
   $SESSION_USER_ID = null;
   $SESSION_PASSWORD = null;
   $SESSION_AUTO_TIP_SHOWN = null;


   class IntranetMsgMngrApp extends PHPApplication {

      function run()
      {
          
          global $TEMPLATE_DIR;
          $cmd = $this->getRequestField('cmd');
          

           session_register('SESSION_USER_ID');



           $this->uid = $this->getSessionField('SESSION_USER_ID');

           $themeObj = new Theme($this->dbi,null,'');

           $this->themeObj = $themeObj;

           $this->theme = $themeObj->getUserTheme($this->uid);

           $this->template_dir = $TEMPLATE_DIR;

           if (!empty($cmd))
           {
              $step = $this->getRequestField('step');
              if (!strcmp($cmd, 'add'))
              {
                 if (empty($step))
                 {
                    $this->displayMsgAddModMenu('add');
                 }
                 else if ($step == 2)
                 {
                    $this->confirmMessage();
                 }
                 else if ($step == 3)
                 {
                    $this->addMessage();
                 }
              }
              if (!strcmp($cmd, 'modify'))
              {
                 if (empty($step))
                 {
                    $this->displayMsgAddModMenu('modify');
                 }
                 else if ($step == 2)
                 {
                    $this->confirmMessage();
                 }
                 else if ($step == 3)
                 {
                    $this->modifyMessage();
                 }
              }
              else if (!strcmp($cmd, 'delete'))
              {
                 $this->deleteMessage();
              }
           }
           else
           {
              $this->displayMsgMngrMenu();
           }
     }

     function deleteMessage()
     {
        $mid = $this->getRequestField('mid');

        if (empty($mid))
        {
           $this->alert('MSG_ID_NOT_SELECTED');
           exit;
        }
        $msgObj = new Message($this->dbi);
        $status = $msgObj->deleteMessage($mid);
         global $MSG_MNGR_TEMPLATE, $LN_MSG_MNGR;
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         global $SESSION_AUTO_TIP_SHOWN;
         global $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         global $TEMPLATE_DIR;
         global $STATUS_TEMPLATE;

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

         $template->set_file('fh1', $STATUS_TEMPLATE);
         $template->set_block('fh1','mainBlock','mblock');


         if ($status)
         {
             $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_DELETED'));
             $msgObj->deleteViewers($mid);
         }
         else
         {
            $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_NOT_DELETED'));
         }

         $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');

     }

     function confirmMessage()
     {
         $title = $this->getRequestField('title'); 
         $msgDate = $this->getRequestField('msgDate');
         $msg = $this->getRequestField('msg');
         $currentTS = $this->getRequestField('currentTS');
         $mode = $this->getRequestField('mode');
         $mid = $this->getRequestField('mid');
         $viewers = $this->getRequestField('viewers');

         list($m,$d, $y) = explode('/', $msgDate);
         $date = mktime(23, 59, 59, $m, $d, $y);

         if (empty($title) || empty($msg) || empty($viewers))
         {
            $this->alert('INPUT_MISSING');
            exit;	
         }
         if(!checkdate($m, $d, $y) || $date < mktime())
         {
            $this->alert('DATE_ERROR');
            exit;
         }
         $curHr = date("H", mktime());
         $curMin = date("i", mktime());
         $curSec = date("s", mktime());
         $realDate = mktime($curHr, $curMin, $curSec, $m, $d, $y);
         
         
         /**********/
         
         global $MSG_MNGR_TEMPLATE, $LN_MSG_MNGR;
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         global $SESSION_AUTO_TIP_SHOWN;
         global $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         global $TEMPLATE_DIR;
         global $MSG_PREVIEW_TEMPLATE, $MSG_MNGR;

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

         $template->set_file('fh1', $MSG_PREVIEW_TEMPLATE);
         $template->set_block('fh1','mainBlock','mblock');
         $template->set_block('mainBlock','viewBlock', 'view');
         
         foreach($viewers as $vid)
         {
            $template->set_var('VIEWERS', $vid);
            $template->parse('view','viewBlock',true);
         }
         


         $template->set_var('MSG_DATE', date("M-d-Y", $realDate));
         $template->set_var('MSG_TITLE', stripslashes($title));
         $template->set_var('MSG_TITLE_HIDDEN', htmlentities(stripslashes($title)));
         $template->set_var('MSG_CONTENTS', stripslashes($msg));
         $template->set_var('MSG_CONTENTS_HIDDEN', htmlentities(stripslashes($msg)));
         $template->set_var('MODE', $mode);         
         $template->set_var('MSG_DATE_TS', $realDate);
         $template->set_var('MSG_MNGR', $MSG_MNGR);
         $template->set_var('CUR_TS', $currentTS);
         $template->set_var('MSG_ID', $mid);
         
         
         //$template->set_var('RETURN_URL', 'foobar');
         $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');
         
         
         
         
         /***********/
         
         	
     }
     
     function modifyMessage()
     {
         $mid =  $this->getRequestField('mid');
         $title =  $this->getRequestField('title');
         $msgDate =  $this->getRequestField('msgDate');
         $msg =  $this->getRequestField('msg');
         $currentTS =  $this->getRequestField('currentTS');
         $viewers =  $this->getRequestField('viewers');
         
         if (empty($viewers))
         {
            $this->alert('INPUT_MISSING');	
         }
         
         $msgObj = new Message($this->dbi, $mid);
         if ($msgObj->isRead())
         {
            $this->addMessage();	
         }
         else
         {
            $status = $msgObj->modifyMessage($mid, htmlentities($title), $msgDate, htmlentities($msg), $currentTS);
            
            global $MSG_MNGR_TEMPLATE, $LN_MSG_MNGR;
            global $THEME_TEMPLATE;
            global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
            global $SESSION_AUTO_TIP_SHOWN;
            global $REL_TEMPLATE_DIR;
            global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
            global $TEMPLATE_DIR;
            global $STATUS_TEMPLATE;
            
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
            
            $template->set_file('fh1', $STATUS_TEMPLATE);
            $template->set_block('fh1','mainBlock','mblock');
            
            
            if ($status)
            {
                $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_MODIFIED'));
                $msgObj->deleteViewers($mid);
                $msgObj->addViewer($mid, $viewers);
            }
            else
            {
               $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_NOT_MODIFIED'));
            }
            //$template->set_var('RETURN_URL', 'foobar');
            $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));
            
            $themeTemplate->set_var('SERVER_NAME', $this->get_server());
            $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
            
            
            $themeTemplate->parse('cnblock', 'contentBlock');
            $themeTemplate->parse('mmblock', 'mmainBlock');
            $themeTemplate->pparse('output', 'fh');
            
            }
     }
     
     
     function addMessage()
     {
         
         $title = $this->getRequestField('title');
         $msgDate  = $this->getRequestField('msgDate');
         $msg = $this->getRequestField('msg');
         $currentTS = $this->getRequestField('currentTS');
         $viewers = $this->getRequestField('viewers');
         
         
         if (empty($viewers))
         {
            $this->alert('INPUT_MISSING');	
         }
         
         $msgObj = new Message($this->dbi);
         
         $status = $msgObj->addMessage(htmlentities($title), $msgDate, htmlentities($msg), $currentTS, $this->getUID(), $this->isAdmin ? 1 : 0);

         global $MSG_MNGR_TEMPLATE, $LN_MSG_MNGR;
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         global $SESSION_AUTO_TIP_SHOWN;
         global $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         global $TEMPLATE_DIR;
         global $STATUS_TEMPLATE;

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

         $template->set_file('fh1', $STATUS_TEMPLATE);
         $template->set_block('fh1','mainBlock','mblock');


         if ($status)
         {
             $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_ADDED'));
             $msgObj->addViewer($status, $viewers);             
         }
         else
         {
            $template->set_var('STATUS_MESSAGE', $this->getMessage('MESSAGE_NOT_ADDED'));
         }
         //$template->set_var('RETURN_URL', 'foobar');
         $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');


     }

     function displayMsgMngrMenu()
     {
          global $MSG_MNGR_TEMPLATE, $LN_MSG_MNGR;
          global $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $SESSION_AUTO_TIP_SHOWN;
          global $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          global $TEMPLATE_DIR;

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

          $template->set_file('fh1', $MSG_MNGR_TEMPLATE);
          $template->set_block('fh1', 'mainBlock', 'mblock');
          $template->set_block('mainBlock', 'msgBlock', 'msg');

          $msgObj = new Message($this->dbi);
          $msgArr = array();

          $msgArr = $this->isAdmin ? $msgObj->getAllMessages() : null;


          if (!empty($msgArr))
          {
             while(list($mid, $msgInfo) = each($msgArr))
             {
               $template->set_var('MID', $mid);
               $template->set_var('MSG_TITLE', stripslashes($msgInfo->MSG_TITLE));
               $template->parse('msg', 'msgBlock', true);
             }
          }
          else
          {
             $template->set_var('msg', null);
          }

          $template->set_var('MSG_MNGR', $LN_MSG_MNGR);



          $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');



     }

     function displayMsgAddModMenu($mode = null)
     {
     	  global $MSG_ADD_TEMPLATE, $LN_MSG_MNGR;
          global $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $SESSION_AUTO_TIP_SHOWN;
          global $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          global $TEMPLATE_DIR;
          
          
          $title = $this->getRequestField('title'); 
          $msgDate = $this->getRequestField('msgDate');
          $msg = $this->getRequestField('msg');
          $mid = $this->getRequestField('mid');
          
          if (!strcmp($mode, 'modify'))
          {
             if (empty($mid))
             {
                $this->alert('MSG_ID_NOT_SELECTED');
                exit;
             }
             $msgObj = new Message($this->dbi, $mid);
             $msg = $msgObj->getMessageContents();
             $msgDate = $msgObj->getMessagePublishDate();
             $title = $msgObj->getMessageTitle();
          }

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

          $template->set_file('fh1', $MSG_ADD_TEMPLATE);
          $template->set_block('fh1', 'mainBlock', 'mblock');
          $template->set_block('mainBlock', 'viewBlock', 'view');
          
          $curDate = date("m/d/Y", mktime());
          
          $template->set_var('MSG_TITLE', empty($title) ? null : stripslashes($title));
          $template->set_var('MSG_CONTENT', empty($msg) ? null : stripslashes($msg));
          $template->set_var('MSG_DATE', empty($msgDate) ? $curDate : date("m/d/Y", $msgDate));

          $template->set_var('MSG_MNGR', $LN_MSG_MNGR);
          $template->set_var('CURRENT_TIME', mktime());
          $template->set_var('MODE', $mode);
          $template->set_var('MSG_ID', $mid);
          
          $msgObj = new Message($this->dbi);
          if (!empty($mid)) 
          {
          	$viewers = $msgObj->getViewers($mid);
          }
          if (!empty($viewers) && in_array(0, $viewers))
          {
               $template->set_var('VIEW_EVERY_CHOSEN', 'SELECTED');
               $view_every = true;
          }
          
          
          global $USER_DB_URL;
          $authDBI = new DBI($USER_DB_URL);
          $userObj = new User($authDBI);
          $users = $userObj->getUserList();
          asort($users);
          reset($users);
          
          while(list($uid, $uname) = each($users))
          {
             list($name, $host) = explode('@', $uname);
             $template->set_var('VIEW_UID', $uid);
             $template->set_var('VIEW_NAME', $name);
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
        
          
          $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');

     }
     
     function unhtmlentities ($string)
      {
	$trans_tbl = get_html_translation_table (HTML_ENTITIES);
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
      }


      function authorize()
      {
          global $USER_DB_URL;
          $cmd = $this->getRequestField('cmd');
          $user_dbi = new DBI($USER_DB_URL);
          $userObj = new User($user_dbi, $this->getUID());

          if ($userObj->getTYPE() == ADMIN_TYPE)
          {
             $this->isAdmin = true;
             return true;
          }
          else
          {
             $this->isAdmin = false;
             if (!strcmp($cmd, 'add'))
             {
                return true;	
             }
             else
             {
                return false;
             }
          }
          
      }



   }//class

   global $INTRANET_DB_URL;

   $thisApp = new IntranetMsgMngrApp(
                                      array( 'app_name'            => $APPLICATION_NAME,
                                             'app_version'         => '1.0.0',
                                             'app_type'            => 'WEB',
                                             'app_db_url'          => $INTRANET_DB_URL,
                                             'app_auto_connect'    => TRUE,
                                             'app_auto_authorize'  => TRUE,
                                             'app_auto_chk_session' => FALSE,
                                             'app_debugger'        => $OFF
                                           )
                                      );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
