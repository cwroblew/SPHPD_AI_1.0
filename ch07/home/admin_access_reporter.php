<?php

   require_once "home.conf";

   require_once $INTRANET_USER_CLASS;
   require_once $ACTIVITY_ANALYZER_CLASS;


   class IntranetAccessReporterApp extends PHPApplication {

      function run()
      {

          if (! $this->authorize())
          {
             $this->alert('UNAUTHORIZED_ACCESS');
             exit;
          }
          $this->userID = $this->getUID();
          $this->reportDriver();

     }


     function authorize()
     {
          global $USER_DB_URL;
          $user_dbi = new DBI($USER_DB_URL);
          $userObj = new User($user_dbi, $this->getUID());


          if ($userObj->getTYPE() == ADMIN_TYPE)
          {
             $this->isAdmin = true;
          }
          else
          {
             $this->isAdmin = false;
          }
          return $this->isAdmin;
    }

    function reportDriver()
    {
          //$this->debug("Display IntranetUser home for USER_ID $this->uid");

          global $ADMIN_ACCESS_REPORT_TEMPLATE;


          $rpt = $this->getRequestField('rpt');

          if (empty($rpt)) {
             $rpt = DEFAULT_REPORT_TYPE;
          }

          if ($rpt == WEEKLY)
          {
             $report = $this->generateWeeklyReport($ADMIN_ACCESS_REPORT_TEMPLATE);

          } else if ($rpt == MONTHLY) {

             $report = $this->generateMonthlyReport($ADMIN_ACCESS_REPORT_TEMPLATE);

          } else {

             $report = $this->generateDailyReport($ADMIN_ACCESS_REPORT_TEMPLATE);
          }

          $this->displayReport($report);

    }

    function generateDailyReport($templateFile = null)
    {
            global $USER_DB_URL, $ADMIN_ACCESS_REPORTER, $ACCESS_REPORTER;
            
            $cmd = $this->getRequestField('cmd');
            $indicator = $this->getRequestField('indicator');
            $template = new Template($this->getTemplateDir());
            $template->set_file('fh1', $templateFile);
            $template->set_block('fh1', 'mainBlock', 'mblock');
            $template->set_block('mainBlock', 'reportBlock', 'rblock');

            if (empty($indicator))
            {
               $indicator = $this->now();
            }
            
            
            if (!strcmp($cmd, '<<'))
            {
               $indicator = $indicator - (SECONDS_PER_DAY);
            }
            else if (!strcmp($cmd, '>>'))
            {
               $indicator = $indicator + (SECONDS_PER_DAY);
            }
            
            $template->set_var('INDICATOR', $indicator);

            $today = $this->now();
            $dayStartTS = $indicator;
            $currentDate = date('M-d-Y', $today);
            $day = date('D', $dayStartTS);

            $template->set_var(array(
                                  'CHOSEN_WEEKLY_REPORT' => '',
                                  'CHOSEN_MONTHLY_REPORT' => '',
                                  'CHOSEN_DAILY_REPORT' => 'selected',
                                  'RPT' => 'Daily',
                                  'CURRENT_DATE' => $currentDate,
                                  'START_DATE' => date('M-d-Y', $dayStartTS),
                                  'END_DATE' => date('M-d-Y', $dayStartTS)
                                  )
                            );
            $officeStartTS = $dayStartTS + (OFFICE_START_TIME * SECONDS_PER_HOUR);
            $officeEndTS = $dayStartTS + (OFFICE_END_TIME * SECONDS_PER_HOUR);
            $lunchStartTS = $dayStartTS + (LUNCH_START_TIME * SECONDS_PER_HOUR);
            $lunchEndTS = $dayStartTS + (LUNCH_END_TIME * SECONDS_PER_HOUR);
            $dayEndTS = $dayStartTS + SECONDS_PER_DAY;
            $user_dbi = new DBI($USER_DB_URL);
            $analyzerObj = new ActivityAnalyzer($user_dbi);


            /******************/
            global $USER_DB_URL;
            $user_dbi = new DBI($USER_DB_URL);
            $userObj = new User($user_dbi);

            $userArr = array();
            $userArr = $userObj->getUserList();
            
            while(list($uid, $email) = each($userArr))
            {
                 list($name, $host) = explode('@', $email);
                 $template->set_var('UID', $uid);
                 $template->set_var('UNAME', ucwords($name));
                 $start = $analyzerObj->getDailyStartTS($dayStartTS, $dayEndTS, $uid);
                 $end = $analyzerObj->getDailyEndTS($dayStartTS, $dayEndTS, $uid);

                 $params = array(
                 				'OFFICE_START' => $officeStartTS,
                 				'OFFICE_END'   => $officeEndTS,
                 				'LUNCH_START'  => $lunchStartTS,
                 				'LUNCH_END'    => $lunchEndTS,
                 				'DAY_START'    => $dayStartTS,
                 				'DAY_END'      => $dayEndTS,
                 				'USER_ID'      => $uid
                                );
                 $hours = $analyzerObj->analyzeDailyActivity($params);

                 if (isset($total_office)) $total_office += $hours['OFFICE'];
                 else $total_office = $hours['OFFICE'];
                 if (isset($total_extra)) $total_extra += $hours['EXTRA'];
                 else $total_extra = $hours['EXTRA'];
                 
                 
                 $hash[$name] = array(
	                               'uname' => ucwords($name),
	                               'uid'   => $uid,
	                               'office_hr' => $hours['OFFICE'],
	                               'extra_hr' => $hours['EXTRA'],
	                               
	                              );
                 
            }
           
           
           $sort = $this->getRequestField('sort');
           $utog = $this->getRequestField('utog');
           $otog = $this->getRequestField('otog');
           $etog = $this->getRequestField('etog');
           
           $template->set_var('UTOG', $this->toggleSortCriteria($utog));
           $template->set_var('OTOG', $this->toggleSortCriteria($otog));
           $template->set_var('ETOG', $this->toggleSortCriteria($etog));
           
           if (!empty($sort))
           { 
           	$tog = substr(strtolower($sort), 0, 1).'tog';
           	$togVal = $$tog;           	
           	usort($hash, $togVal."sortBy".$sort);
           }
           else
           {
                if (empty($utog))
                {
                   ksort($hash);	
                }
                else
                {
                   krsort($hash);	
                }               
           }
           
           
           reset($hash);
           
           $i = 0; 
           foreach($hash as $user)
           {
           	$template->set_var('UNAME', $user['uname']);
                $template->set_var('UID', $user['uid']);
                $template->set_var('OFF_HR', $this->convert($user['office_hr']));
	        $template->set_var('EXT_HR', $this->convert($user['extra_hr']));
	        $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
                $template->set_var('ROW_COLOR', $rowColor);

                $template->parse('rblock', 'reportBlock', true);   

           }
            
         
         $template->set_var('SUM_OFF_HR', $this->convert($total_office));
         $template->set_var('SUM_EXT_HR', $this->convert($total_extra));
         $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert($total_office/count($userArr)));
         $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert($total_extra/count($userArr)));
         $template->set_var('AVRG_MAN_DAYS_PER_EMP', round((($total_office/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS)/count($userArr), 2));
         $template->set_var('TOTAL_MAN_DAYS', round((($total_office/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS), 2));
         $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);
         $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);

         return $template->parse('mblock', 'mainBlock');


    /*************/
    }


    function generateMonthlyReport($templateFile = null)
    {
         global $USER_DB_URL, $ACCESS_REPORTER, $indicator, $cmd, $uid, $ACCESS_REPORTER, $ADMIN_ACCESS_REPORTER;
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh1', $templateFile);
         $template->set_block('fh1', 'mainBlock', 'mblock');
         $template->set_block('mainBlock', 'reportBlock', 'rblock');

         
         if (empty($indicator))
         {
               $indicator = $this->now();
         }
         
         $indicatorMonth = date("m", $indicator);
         $indicatorYear = date("Y", $indicator);
         if (!strcmp($cmd, '<<'))
         {
            $indicatorMonth = $indicatorMonth - 1;
            $indicator = mktime(0,0,0,$indicatorMonth,1,$indicatorYear);
         }
         else if (!strcmp($cmd, '>>'))
         {
            $indicatorMonth = $indicatorMonth + 1;
            $indicator = mktime(0,0,0,$indicatorMonth,1,$indicatorYear);

         }
         
         $template->set_var('INDICATOR', $indicator);
         $today = $this->now();
         // Get start TS for this weeek
         // Get end TS for this week
         // Do Select

         $currentDate = date('M-d-Y', $today);
         list($startTS, $endTS) = $this->getMonthlyTSRange($indicator);



         $template->set_var(array(
                                  'CHOSEN_WEEKLY_REPORT' => '',
                                  'CHOSEN_MONTHLY_REPORT' => 'selected',
                                  'RPT'                  => 'Monthly',
                                  'CHOSEN_DAILY_REPORT' => '',
                                  'CURRENT_DATE' => $currentDate,
                                  'START_DATE' => date('M-d-Y', $startTS),
                                  'END_DATE' => date('M-d-Y', $endTS)
                                  )
                            );

         $i = 0;

         $user_dbi = new DBI($USER_DB_URL);
         $analyzerObj = new ActivityAnalyzer($user_dbi);

         global $USER_DB_URL;
         $user_dbi = new DBI($USER_DB_URL);
         $userObj = new User($user_dbi);

         $userArr = array();
         $userArr = $userObj->getUserList();
         
         while(list($uid, $email) = each($userArr))
         {
            $dayStartTS = $startTS;
            $total_office = $total_extra = 0;
            $monthDayCounter = 0;
            while ($dayStartTS <= $endTS)
            {
               global $WEEKEND;
               if(!in_array(date('D', $dayStartTS) , $WEEKEND))
               {
                 $monthDayCounter++;
               }  
               $day = date('D', $dayStartTS);
               $officeStartTS = $dayStartTS + (OFFICE_START_TIME * SECONDS_PER_HOUR);
               $officeEndTS = $dayStartTS + (OFFICE_END_TIME * SECONDS_PER_HOUR);
               $lunchStartTS = $dayStartTS + (LUNCH_START_TIME * SECONDS_PER_HOUR);
               $lunchEndTS = $dayStartTS + (LUNCH_END_TIME * SECONDS_PER_HOUR);
               $dayEndTS = $dayStartTS + SECONDS_PER_DAY;

               $start = $analyzerObj->getDailyStartTS($dayStartTS, $dayEndTS, $this->userID);
               $end = $analyzerObj->getDailyEndTS($dayStartTS, $dayEndTS, $this->userID);

               $params = array(
               				'OFFICE_START' => $officeStartTS,
               				'OFFICE_END'   => $officeEndTS,
               				'LUNCH_START'  => $lunchStartTS,
               				'LUNCH_END'    => $lunchEndTS,
               				'DAY_START'    => $dayStartTS,
               				'DAY_END'      => $dayEndTS,
               				'USER_ID'      => $uid
                              );
               $hours = $analyzerObj->analyzeDailyActivity($params);
               $total_office += $hours['OFFICE'];
               $total_extra += $hours['EXTRA'];
               $dayStartTS = $dayEndTS;
            }//day ends
            
            
            list($name, $host) = explode('@', $email);
            $hash[$name] = array(
	                               'uname' => ucwords($name),
	                               'uid'   => $uid,
	                               'office_hr' => $total_office,
	                               'extra_hr' => $total_extra,
	                              );


            if (isset($sum_off)) $sum_off = $sum_off + $total_office;
                   else $sum_off = $total_office;
                   
            if (isset($sum_ext)) $sum_ext = $sum_ext + $total_extra;
                   else $sum_ext = $total_extra;
            //$template->set_var('ROW_COLOR', $rowColor);
            //$template->set_var('UID', $uid);
            
            $template->set_var('UNAME', ucwords($name));
            //$template->set_var('OFF_HR', $this->convert($total_office));
	    //$template->set_var('EXT_HR', $this->convert($total_extra));
            //$template->parse('rblock', 'reportBlock', true);
         }//month ends

           
           $sort = $this->getRequestField('sort');
           $utog = $this->getRequestField('utog');
           $otog = $this->getRequestField('otog');
           $etog = $this->getRequestField('etog');
           
           $template->set_var('UTOG', $this->toggleSortCriteria($utog));
           $template->set_var('OTOG', $this->toggleSortCriteria($otog));
           $template->set_var('ETOG', $this->toggleSortCriteria($etog));
           
           if (!empty($sort))
           { 
           	$tog = substr(strtolower($sort), 0, 1).'tog';
           	$togVal = $$tog;           	
           	usort($hash, $togVal."sortBy".$sort);
           }
           else
           {
                if (empty($utog))
                {
                   ksort($hash);	
                }
                else
                {
                   krsort($hash);	
                }               
           }
           
           
           reset($hash);
           
           foreach($hash as $user)
           {
           	$template->set_var('UNAME', $user['uname']);
                $template->set_var('UID', $user['uid']);
                $template->set_var('OFF_HR', $this->convert($user['office_hr']));
	        $template->set_var('EXT_HR', $this->convert($user['extra_hr']));
	        $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
                $template->set_var('ROW_COLOR', $rowColor);

                $template->parse('rblock', 'reportBlock', true);   

           }

         $template->set_var('SUM_OFF_HR', $this->convert($sum_off));
         $template->set_var('SUM_EXT_HR', $this->convert($sum_ext));
         $template->set_var('AVRG_MAN_DAYS_PER_EMP', round((($sum_off/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS)/count($userArr), 2));
         $template->set_var('TOTAL_MAN_DAYS', round((($sum_off/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS), 2));
         
         $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert(($sum_off/$monthDayCounter)/count($userArr)));
         $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert(($sum_ext/$monthDayCounter)/count($userArr)));
         $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);
         $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);

        return $template->parse('mblock', 'mainBlock');
    }

      function displayReport($report = null)
      {
          global $THEME_TEMPLATE, $THEME_TEMPLATE_DIR, $USER_DB_URL;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR, $REL_TEMPLATE_DIR;

          $user_dbi = new DBI($USER_DB_URL);
          $themeObj = new Theme($this->dbi, null,'report');

          $userObj = new User($user_dbi, $this->userID);


          $this->theme = $themeObj->getUserTheme($this->userID);

          //$this->theme = 1;

          $this->themeObj = $themeObj;
          $themeTemplate = new Template($THEME_TEMPLATE_DIR);
          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
          


          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
          

          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);


          $themeTemplate->set_var('CONTENT_BLOCK', $report);
          list($uname, $host) = explode('@', $userObj->getEmail());
          $themeTemplate->set_var('USERNAME', ucwords($uname));
          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');

      }
      function generateWeeklyReport($templateFile = null)
      {
           global $USER_DB_URL, $ADMIN_ACCESS_REPORTER, $cmd, $indicator, $ACCESS_REPORTER;
           $template = new Template($this->getTemplateDir());
           $template->set_file('fh1', $templateFile);
    	   $template->set_block('fh1', 'mainBlock', 'mblock');
           $template->set_block('mainBlock', 'reportBlock', 'rblock');


           $weekDays = array('MON' => 1,
                             'TUE' => 2,
                             'WED' => 3,
                             'THU' => 4,
                             'FRI' => 5,
                             'SAT' => 6,
                             'SUN' => 7
                            );


           $today = $this->now();
           // Get start TS for this weeek
           // Get end TS for this week
           // Do Select

           if (empty($indicator))
           {
               $indicator = $this->now();
           }
           
           if (!strcmp($cmd, '<<'))
           {
              $indicator = $indicator - (7 * SECONDS_PER_DAY);
           }
           else if (!strcmp($cmd, '>>'))
           {
              $indicator = $indicator + (7 * SECONDS_PER_DAY);
           }
           
           //echo 'Indicator '.date("M-d-Y", $indicator);
           $template->set_var('INDICATOR', $indicator);

           $currentDate = date('M-d-Y', $today);
           list($startTS, $endTS) = $this->getWeeklyTSRange($indicator);

           $weekDayNames = array_keys($weekDays);

           $template->set_var(array(
                                    'CHOSEN_WEEKLY_REPORT' => 'selected',
                                    'RPT'                  => 'Weekly',
                                    'CHOSEN_MONTHLY_REPORT' => '',
                                    'CHOSEN_DAILY_REPORT' => '',
                                    'CURRENT_DATE' => $currentDate,
                                    'START_DATE' => date('M-d-Y', $startTS),
                                    'END_DATE' => date('M-d-Y', $endTS)
                                   )
                              );

           $i = 0;
           global $USER_DB_URL;
           $user_dbi = new DBI($USER_DB_URL);
           $userObj = new User($user_dbi);

           $userArr = array();
           $userArr = $userObj->getUserList();
           //asort($userArr);
           //reset($userArr);
           while(list($uid, $email) = each($userArr))
           {
                   $total_office = $total_extra = 0;
                   $dayStartTS = $startTS;
                   $analyzerObj = new ActivityAnalyzer($user_dbi);


                   foreach ($weekDayNames as $day)
                   {
                      $officeStartTS = $dayStartTS + (OFFICE_START_TIME * SECONDS_PER_HOUR);
                      $officeEndTS = $dayStartTS + (OFFICE_END_TIME * SECONDS_PER_HOUR);
                      $lunchStartTS = $dayStartTS + (LUNCH_START_TIME * SECONDS_PER_HOUR);
                      $lunchEndTS = $dayStartTS + (LUNCH_END_TIME * SECONDS_PER_HOUR);
                      $dayEndTS = $dayStartTS + SECONDS_PER_DAY;

                      $start = $analyzerObj->getDailyStartTS($dayStartTS, $dayEndTS, $uid);
                      $end = $analyzerObj->getDailyEndTS($dayStartTS, $dayEndTS, $uid);

                      $params = array(
                      				'OFFICE_START' => $officeStartTS,
                      				'OFFICE_END'   => $officeEndTS,
                      				'LUNCH_START'  => $lunchStartTS,
                      				'LUNCH_END'    => $lunchEndTS,
                      				'DAY_START'    => $dayStartTS,
                      				'DAY_END'      => $dayEndTS,
                      				'USER_ID'      => $uid
                                     );
                      $hours = $analyzerObj->analyzeDailyActivity($params);
                      $total_office += $hours['OFFICE'];
                      $total_extra += $hours['EXTRA'];
                      $dayStartTS = $dayEndTS;
                   }

                   list($name, $host) = explode('@', $email);
                   
                   if (isset($sum_off)) $sum_off = $sum_off + $total_office;
                   else $sum_off = $total_office;
                   
                   if (isset($sum_ext)) $sum_ext = $sum_ext + $total_extra;
                   else $sum_ext = $total_extra;
	           
	           
	           $hash[$name] = array(
	                               'uname' => ucwords($name),
	                               'uid'   => $uid,
	                               'office_hr' => ($total_office),
	                               'extra_hr' => ($total_extra),
	                               
	                              );

                   
           }
           
           //$this->dump_array($hash);
           
           
           
           $sort = $this->getRequestField('sort');
           $utog = $this->getRequestField('utog');
           $otog = $this->getRequestField('otog');
           $etog = $this->getRequestField('etog');
           
           $template->set_var('UTOG', $this->toggleSortCriteria($utog));
           $template->set_var('OTOG', $this->toggleSortCriteria($otog));
           $template->set_var('ETOG', $this->toggleSortCriteria($etog));
           
           if (!empty($sort))
           { 
           	$tog = substr(strtolower($sort), 0, 1).'tog';
           	$togVal = $$tog;           	
           	usort($hash, $togVal."sortBy".$sort);
           }
           else
           {
                if (empty($utog))
                {
                   ksort($hash);	
                }
                else
                {
                   krsort($hash);	
                }               
           }
           reset($hash);
           foreach($hash as $user)
           {
           	$template->set_var('UNAME', $user['uname']);
                $template->set_var('UID', $user['uid']);
                $template->set_var('OFF_HR', $this->convert($user['office_hr']));
	        $template->set_var('EXT_HR', $this->convert($user['extra_hr']));
	        $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
                $template->set_var('ROW_COLOR', $rowColor);

                $template->parse('rblock', 'reportBlock', true);   

           }
           
           
           
           /**********/
           $template->set_var('SUM_OFF_HR', $this->convert($sum_off));
           $template->set_var('SUM_EXT_HR', $this->convert($sum_ext));
           $template->set_var('AVRG_MAN_DAYS_PER_EMP', round((($sum_off/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS)/count($userArr), 2));
           $template->set_var('TOTAL_MAN_DAYS', round((($sum_off/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS), 2));
           $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert(($sum_off/count($userArr))/6));
           $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert(($sum_ext/count($userArr))/6));
           $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);
           $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);

          return $template->parse('mblock', 'mainBlock');
      }
      
      
      
      
      function convert($sec)
      {
		list($hr,) = explode('.', ($sec / 3600));
		list($min,) = explode('.', ($sec % 3600) / 60);
		$second = (($sec % 3600) / 60) % 60;
		return $hr.' hrs '.$min.' mins';

      }

      function getWeeklyTSRange($today)
      {

        $now = getdate($today);

        $wday = $now['wday'];
        $wday = ($wday == 0) ? 7 : $wday;

        $startTS =$today - (SECONDS_PER_DAY * ($wday - 1));
        $endTS = $today + (SECONDS_PER_DAY * (7 - $wday));

        return (array($startTS, $endTS));
      }
      function getMonthlyTSRange($today)
      {
         $now = getdate($today);
         $startTS = mktime(0,0,0, $now['mon'], 1, $now['year']);
         $endTS = mktime(0,0,0, $now['mon']+1, 0, $now['year']);
         return (array($startTS, $endTS));

      }

      function now()
      {
         $now = getdate();
         $today = mktime(0,0,0, $now['mon'], $now['mday'], $now['year']);
         return $today;
      }
      
      function toggleSortCriteria($field = null)
      {
         return (empty($field)) ? 'reverse' : null;
      }

   }//class
   
   function sortByExtra($a,$b)
   {
       return ($a['extra_hr'] < $b['extra_hr']) ? 1 : -1;
   }
           
   function sortByOffice($a,$b)
   {
       return ($a['office_hr'] < $b['office_hr']) ? 1 : -1;
   }
   
   function reversesortByExtra($a,$b)
   {
       return ($a['extra_hr'] > $b['extra_hr']) ? 1 : -1;
   }
           
   function reversesortByOffice($a,$b)
   {
       return ($a['office_hr'] > $b['office_hr']) ? 1 : -1;
   }


   /* Session variables must be defined before session_start() method is called */
   $SESSION_USERNAME = null;
   $SESSION_USER_ID = null;
   $SESSION_PASSWORD = null;

   global $INTRANET_DB_URL;
   $thisApp = new IntranetAccessReporterApp(
                            array( 'app_name'             => $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_db_url'            => $INTRANET_DB_URL,
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_auto_chk_session'  => FALSE,
                                  'app_debugger'          => $OFF
                                 )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
