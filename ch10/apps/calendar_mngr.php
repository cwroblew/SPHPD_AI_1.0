<?php
   
   require_once "calendar.conf";
   require_once $THEME_CLASS;
   require_once $EVENT_CLASS;
   
   class calendarMngr extends PHPApplication {

     function run()
     {    
        $themeObj = new Theme($this->dbi,null,'calendar');
        $this->themeObj = $themeObj;
        $this->theme = $themeObj->getUserTheme($this->getUID());
        
        $this->displayCalendar();
     }
     
     function authorize()
     {
        return true;
     }
     
     function displayCalendar()
     {
         global $REL_APP_PATH;
         $ts = $this->getRequestField('ts');
         
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', CALENDAR_HOME_TEMPLATE);
         $template->set_block('fh', 'mainBlock', 'main');
         $template->set_block('mainBlock', 'weekBlock', 'week');
         
         $ts = $ts ? $ts : mktime();
         
         $date = getDate($ts);
         
         $weekDays = array(
                             'Sun' => 0,
                             'Mon' => 1,
                             'Tue' => 2,
                             'Wed' => 3,
                             'Thu' => 4,
                             'Fri' => 5,
                             'Sat' => 6
                          );
                          
         $dayCounter = 1;
         while($dayCounter <= date("t", $ts))
         {
            $alt = array();
            global $WEEKEND, $HOLIDAYS, $GLOBAL_EVENTS;
            reset($HOLIDAYS);
            reset($GLOBAL_EVENTS);
            reset($WEEKEND);
            
            $day = date('D', mktime(0,0,0, $date['mon'], $dayCounter, $date['year']));
            $curDayTS = mktime(0,0,0, $date['mon'], $dayCounter, $date['year']);
            
            $curDayString = date("m-d-Y", $curDayTS);
            $curMMDDStr = date("m-d", $curDayTS);
            
            $template->set_var(strtoupper($day).'_CELL_COLOR', 'white');	
            $eventObj = new Event($this->dbi); 
            $events = $eventObj->getEvents($this->getUID(), date("m-d-Y", $curDayTS));
              
            
            if (!empty($events))
            {
            	$template->set_var(strtoupper($day).'_CELL_COLOR', PERSONAL_EVENT_COLOR);
            	foreach ($events as $event)
            	{
            	   $alt[] = stripslashes($event);	
            	}
            }
            if (in_array($curMMDDStr, $GLOBAL_EVENTS))
            {
               $template->set_var(strtoupper($day).'_CELL_COLOR', GLOBAL_EVENT_COLOR);
               while (list($event, $dateStr) = each($GLOBAL_EVENTS))
               {
                  if (!strcmp($dateStr, date("m-d", $curDayTS)))
                  {
                    $alt[] = $event;	
                  }	
               }	
            }


            if (in_array($curMMDDStr, $HOLIDAYS))
            {
               $template->set_var(strtoupper($day).'_CELL_COLOR', HOLIDAY_COLOR);
               while (list($event, $dateStr) = each($HOLIDAYS))
               {
                  if (!strcmp($dateStr, date("m-d", $curDayTS)))
                  {
                     $alt[] = $event;
                  }	
               }                 
            }
            if (in_array($day, $WEEKEND))
            {
               $template->set_var(strtoupper($day).'_CELL_COLOR', WEEKEND_COLOR);
               $alt[] = $this->getMessage('WEEKEND');
            }
            if (!strcmp($curDayString, date('m-d-Y', mktime())))
            {
            	$template->set_var(strtoupper($day).'_CELL_COLOR', TODAY_COLOR);
            	$alt[] = "Today";
            }
            
            
            for($i=1;$i<=4;$i++)
            {
               	$template->set_var(strtoupper($day).''.($i), null);
            }
            
            if (!empty($alt))
            {
               	if (count($alt) <= 4)
               	{
               	   while(list($key, $eventTitle) = each($alt))
               	   {
               	      $template->set_var(strtoupper($day).''.($key+1), $eventTitle);	
               	   }
               	}
               	else
               	{
               	   for($i=0;$i<=2;$i++)
               	   {
               	      $eventTitle = $alt[$i];
               	      $template->set_var(strtoupper($day).''.($i+1), $eventTitle);	
               	   }
               	   $template->set_var(strtoupper($day).'4', "<a href=javascript:showMore(".$alt.")>more</a>");
               	}   
            }
            
            if (empty($alt))
            {
              $alt[] = $this->getMessage('EVENT_NOT_FOUND');
            }
            
            $alt = implode(' | ', $alt);
            $template->set_var('CALENDAR_EVENT_MNGR', $REL_APP_PATH.'/'.CALENDAR_EVENT_MNGR);
            $template->set_var(strtoupper($day).'_DATE', $curDayString);
            $template->set_var(strtoupper($day).'_EVENTS', $alt);
            $template->set_var(strtoupper($day).'_DAY', $dayCounter);
            
            if ($dayCounter == 1 && strcmp($day, 'Sun'))
            {
                reset($weekDays);
                while(list($weekDay, $order) = each($weekDays))
                {  
                   if (strcmp($day, $weekDay) && $order < $weekDays[$day])
                   {
                      for($i=1;$i<=4;$i++)
                      {
                         $template->set_var(strtoupper($weekDay).''.($i), null);
                      }   
                      $template->set_var(strtoupper($weekDay).'_DAY', null);
                      $template->set_var(strtoupper($weekDay).'_CELL_COLOR', 'white');
                      $template->set_var(strtoupper($weekDay).'_EVENTS', '');
                   }	
                }
            }
            if ($dayCounter == date("t", $ts) && strcmp($day, 'Sat'))
            {
                reset($weekDays);
                while(list($weekDay, $order) = each($weekDays))
                {
                   if (strcmp($day, $weekDay) && $order > $weekDays[$day])
                   {
                      for($i=1;$i<=4;$i++)
                      {
                         $template->set_var(strtoupper($weekDay).''.($i), null);
                      }   
                      $template->set_var(strtoupper($weekDay).'_DAY', null);
                      $template->set_var(strtoupper($weekDay).'_CELL_COLOR', 'white');
                      $template->set_var(strtoupper($weekDay).'_EVENTS', '');
                   }	
                }
            }
            if (!strcmp($day, 'Sat') || $dayCounter == date("t", $ts))
            {
            	$template->parse('week', 'weekBlock', true);
            }
            $dayCounter++;            
         }
         
         $template->set_var(
                            array(
                                  'TODAY_COLOR'      =>  TODAY_COLOR,
                                  'WEEKEND_COLOR'    =>  WEEKEND_COLOR,
                                  'HOLIDAY_COLOR'    =>  HOLIDAY_COLOR,
                                  'GLOBAL_COLOR'     =>  GLOBAL_EVENT_COLOR,
                                  'PERSONAL_COLOR'   =>  PERSONAL_EVENT_COLOR
                                 )
                           );
         
         $template->set_var('PREV_TS', mktime(0,0,0, $date['mon']-1, 1, $date['year']));
         $template->set_var('NEXT_TS', mktime(0,0,0, $date['mon']+1, 1, $date['year']));
         $template->set_var('MONTH', $date['month'].', '.$date['year']);
         $template->set_var('CALENDAR_MNGR', $REL_APP_PATH.'/'.CALENDAR_MNGR);
         $this->showContents($template->parse('main', 'mainBlock', false));
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
        $template->set_var('STATUS_MESSAGE', $contents);
        $template->set_var('CALENDAR_MNGR', $REL_APP_PATH.'/'.CALENDAR_MNGR);
        $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));
        $themeTemplate->set_var('SERVER_NAME', $this->get_server());
        $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
        $themeTemplate->parse('cnblock', 'contentBlock');
        $themeTemplate->parse('mmblock', 'mmainBlock');
        $themeTemplate->pparse('output', 'fh');	
     }
   }//class
   
   $thisApp = new calendarMngr(array
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
