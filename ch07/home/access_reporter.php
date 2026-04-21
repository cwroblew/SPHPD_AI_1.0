<?php

   require_once "home.conf";

   require_once $INTRANET_USER_CLASS;
   require_once $ACTIVITY_ANALYZER_CLASS;


   class IntranetAccessReporterApp extends PHPApplication {

      function run()
      {
          
          $uid = $this->getRequestField('uid');
          $cmd = $this->getRequestField('cmd');
          
          if (! $this->authorize())
          {
             $this->alert('UNAUTHORIZED_ACCESS');
             exit;
          }
          if (!empty($uid) && $this->isAdmin)
          {
             $this->userID = $uid;
          }
          else
          {
             $this->userID = $this->getUID();
          }
          if (strcmp($cmd, 'Force Logout') && strcmp($cmd, 'Force Login'))
          {
             $this->reportDriver();
          }
          else
          {
             if (!strcmp($cmd, 'Force Logout'))
             {
                $this->logUserOut();	
             }
             else if (strcmp($cmd, 'Force Logout'))
             {
                $this->logUserIn();	
             }   
          }

     }
     
     function logUserOut()
     {
         
         $lomin = $this->getRequestField('lomin');
         $lohr = $this->getRequestField('lohr');
         $lomon = $this->getRequestField('lomon');
         $loday = $this->getRequestField('loday');
         $loyr = $this->getRequestField('loyr');
         
         
         $logoutTime = mktime($lohr, $lomin, 0, $lomon, $loday, $loyr);
         if (!checkdate($lomon, $loday, $loyr))
         {
            $this->alert('DATE_ERROR');	
            exit;
         }
         if ($logoutTime > mktime())
         {
            $this->alert('PREV_DATE_REQUIRED');	
            exit;
         }
         
         
         $analyzer = new ActivityAnalyzer($this->dbi);
         
         $analyzer->logUserOut($this->userID, $logoutTime);
         $this->reportDriver();
     }
     
     function logUserIn()
     {
         $limin = $this->getrequestField('limin');
         $lihr = $this->getrequestField('lihr');
         $limon = $this->getrequestField('limon');
         $liday = $this->getrequestField('liday');
         $liyr = $this->getrequestField('liyr');
         
         
         
         $loginTime = mktime($lihr, $limin, 0, $limon, $liday, $liyr);
         if (!checkdate($limon, $liday, $liyr))
         {
            $this->alert('DATE_ERROR');	
            exit;
         }
         if ($loginTime > mktime())
         {
            $this->alert('PREV_DATE_REQUIRED');	
            exit;
         }
         
         
         $analyzer = new ActivityAnalyzer($this->dbi);
         
         $analyzer->logUserIn($this->userID, $loginTime);
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

          return TRUE;
      }

      function reportDriver()
      {
          //$this->debug("Display IntranetUser home for USER_ID $this->uid");

          global $ACCESS_REPORT_TEMPLATE;


          $rpt = $this->getRequestField('rpt');

          if (empty($rpt)) {
             $rpt = DEFAULT_REPORT_TYPE;
          }

          if ($rpt == WEEKLY)
          {
             $report = $this->generateWeeklyReport($ACCESS_REPORT_TEMPLATE);

          } else if ($rpt == MONTHLY) {

             $report = $this->generateMonthlyReport($ACCESS_REPORT_TEMPLATE);

          } else {

             $report = $this->generateDailyReport($ACCESS_REPORT_TEMPLATE);
          }

          $this->displayReport($report);
      }

      function generateDailyReport($templateFile = null)
      {
              global $USER_DB_URL, $ACCESS_REPORTER, $LOG_BOOK_MNGR;
              $cmd = $this->getRequestField('cmd');
              $indicator = $this->getRequestField('indicator');
              $uid = $this->getRequestField('uid');
              $i = 0;
              
              $template = new Template($this->getTemplateDir());
              $template->set_file('fh1', $templateFile);
              $template->set_block('fh1', 'mainBlock', 'mblock');
              $template->set_block('mainBlock', 'reportBlock', 'rblock');
              $template->set_block('mainBlock', 'adminBlock', 'ablock');
              $template->set_block('adminBlock', 'userBlock', 'ublock');
              $template->set_block('adminBlock', 'hrBlock', 'hr');
              $template->set_block('adminBlock', 'lihrBlock', 'lihr');
              $template->set_block('adminBlock', 'minBlock', 'min');
              $template->set_block('adminBlock', 'liminBlock', 'limin');
              $template->set_block('adminBlock', 'monBlock', 'mon');
              $template->set_block('adminBlock', 'limonBlock', 'limon');
              $template->set_block('adminBlock', 'dayBlock', 'day');
              $template->set_block('adminBlock', 'lidayBlock', 'liday');
              $template->set_block('adminBlock', 'yrBlock', 'yr');
              $template->set_block('adminBlock', 'liyrBlock', 'liyr');

              if (!$this->isAdmin)
              {
                 $template->set_var('ublock', null);
                 $template->set_var('ablock', null);
              }
              else
              {
                 global $USER_DB_URL, $ADMIN_ACCESS_REPORTER;
                 $user_dbi = new DBI($USER_DB_URL);
                 $userObj = new User($user_dbi, $this->getUID());

                 $userArr = array();
                 $userArr = $userObj->getUserList();
                 asort($userArr);
                 reset($userArr);

                 while (list($uID, $email) = each($userArr))
                 {
                    list($name, $host) = explode('@', $email);
                    $template->set_var('USER_ID', $uID);
                    $template->set_var('USER_NAME', ucwords($name));
                    if ($uID == $uid)
                    {
                       $template->set_var('UIDSELECTED', 'selected');
                    }
                    else
                    {
                       $template->set_var('UIDSELECTED', '');
                    }
                    $template->parse('ublock', 'userBlock', true);
                 }
                 $curTime = getdate(mktime());
              
              $curMin = $curTime['minutes'];
              $curHr = $curTime['hours'];
              $curMon = $curTime['mon'];
              $curDay = $curTime['mday'];
              $curYear = $curTime['year'];
              
              for ($i=0; $i<=59; $i++)
              {
                 $template->set_var('MIN', $i);
                 $template->set_var('LIMIN', $i);
                 $template->set_var('MIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 $template->set_var('LIMIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 if ($curMin == $i)
                 {
                    $template->set_var('LIMINSEL', 'selected');	
                    $template->set_var('MINSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MINSEL', null);		
                    $template->set_var('LIMINSEL', null);		
                 }
                 $template->parse('limin', 'liminBlock', true);
                 $template->parse('min', 'minBlock', true);
                 
              }
              
              for ($i=0; $i<=23; $i++)
              {
                 $template->set_var('HR', $i);
                 $template->set_var('LIHR', $i);
                 
                 $template->set_var('HR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 $template->set_var('LIHR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 
                 if ($curHr == $i)
                 {
                    $template->set_var('HRSEL', 'selected');	
                    $template->set_var('LIHRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('HRSEL', null);		
                    $template->set_var('LIHRSEL', null);		
                 }
                 $template->parse('hr', 'hrBlock', true);
                 $template->parse('lihr', 'lihrBlock', true);
              }
              for ($i=1; $i<=12; $i++)
              {
                 $template->set_var('MON', $i);
                 $template->set_var('LIMON', $i);
                 $template->set_var('MON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 $template->set_var('LIMON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 if ($curMon == $i)
                 {
                    $template->set_var('MONSEL', 'selected');	
                    $template->set_var('LIMONSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MONSEL', null);		
                    $template->set_var('LIMONSEL', null);		
                 }
                 $template->parse('mon', 'monBlock', true);
                 $template->parse('limon', 'limonBlock', true);
              }
              for ($i=1; $i<=31; $i++)
              {
                 $template->set_var('DAY', $i);
                 $template->set_var('LIDAY', $i);
                 if ($curDay == $i)
                 {
                    $template->set_var('DAYSEL', 'selected');	
                    $template->set_var('LIDAYSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('DAYSEL', null);		
                    $template->set_var('LIDAYSEL', null);		
                 }
                 $template->parse('day', 'dayBlock', true);
                 $template->parse('liday', 'lidayBlock', true);
              }
              
              for ($i=2002; $i<=MAX_YEAR; $i++)
              {
                 $template->set_var('YR', $i);
                 $template->set_var('LIYR', $i);
                 if ($curYear == $i)
                 {
                    $template->set_var('YRSEL', 'selected');	
                    $template->set_var('LIYRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('YRSEL', null);		
                    $template->set_var('LIYRSEL', null);		
                 }
                 $template->parse('yr', 'yrBlock', true);
                 $template->parse('liyr', 'liyrBlock', true);
              }
                 $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);
                 $template->parse('ablock','adminBlock',false);
              }

              if (!strcmp($cmd, '<<'))
              {
                 $indicator = $indicator - (SECONDS_PER_DAY);
              }
              else if (!strcmp($cmd, '>>'))
              {
                 $indicator = $indicator + (SECONDS_PER_DAY);
              }
              else
              {
                 $indicator = $this->now();
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

              $start = $analyzerObj->getDailyStartTS($dayStartTS, $dayEndTS, $this->userID);
              $end = $analyzerObj->getDailyEndTS($dayStartTS, $dayEndTS, $this->userID);

              $params = array(
              				'OFFICE_START' => $officeStartTS,
              				'OFFICE_END'   => $officeEndTS,
              				'LUNCH_START'  => $lunchStartTS,
              				'LUNCH_END'    => $lunchEndTS,
              				'DAY_START'    => $dayStartTS,
              				'DAY_END'      => $dayEndTS,
              				'USER_ID'      => $this->userID
                             );
              $hours = $analyzerObj->analyzeDailyActivity($params);
              if (isset($total_office)) $total_office += $hours['OFFICE'];
              else $total_office = $hours['OFFICE'];
              if (isset($total_extra)) $total_extra += $hours['EXTRA'];
              else $total_extra = $hours['EXTRA'];

              $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
              $template->set_var('WEEK_DAY', $day);
              $template->set_var('ROW_COLOR', $rowColor);
              $template->set_var('START_TS', empty($start) ? 'Not Available' : date("g:i a", $start));
              $template->set_var('END_TS', empty($end) ? 'Not Available' : date("g:i a", $end));
              $template->set_var('DAY_START', $dayStartTS);
              $template->set_var('DAY_END', $dayEndTS);
              $template->set_var('LOG_BOOK', $LOG_BOOK_MNGR);
              $template->set_var('USEID', $this->userID);


              $template->set_var('DATE', date("M-d-Y", $dayStartTS));
              //$template->set_var('USER_ID', $this->userID);
              $template->set_var('TOTAL_OFFICE_HOURS', $this->convert($hours['OFFICE']));
	      $template->set_var('TOTAL_EXTRA_HOURS', $this->convert($hours['EXTRA']));

              global $WEEKEND;
              if ($hours['OFFICE'] < ((EXPECTED_OFFICE_HRS * SECONDS_PER_HOUR) - GRACE) && !in_array(date('D', $dayStartTS) , $WEEKEND))
	          {
                   $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_ABNORMAL);
	          }
	          else
	          {
                   $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_NORMAL);
	          }

	          if ($dayStartTS == $today)
	          {
	               $template->set_var('CURRENT_DAY_MARKER', '*');
	          }
	          else
	          {
	               $template->set_var('CURRENT_DAY_MARKER', '&nbsp;');
	          }


              $template->parse('rblock', 'reportBlock', true);

           $template->set_var('SUM_OFFICE_HOURS', $this->convert($total_office));
           $template->set_var('SUM_EXTRA_HOURS', $this->convert($total_extra));
           $template->set_var('AVRG_MAN_DAY', round((($total_office/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS),2));
           $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert($total_office));
           $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert($total_extra));

           $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);

           return $template->parse('mblock', 'mainBlock');
      }


      function generateMonthlyReport($templateFile = null)
      {
           global $USER_DB_URL, $ACCESS_REPORTER, $LOG_BOOK_MNGR;
           $cmd = $this->getRequestField('cmd');
           $indicator = $this->getRequestField('indicator');
           $uid = $this->getRequestField('uid');
           
           $template = new Template($this->getTemplateDir());
           $template->set_file('fh1', $templateFile);
    	   $template->set_block('fh1', 'mainBlock', 'mblock');
           $template->set_block('mainBlock', 'reportBlock', 'rblock');
           $template->set_block('mainBlock', 'adminBlock', 'ablock');
           $template->set_block('adminBlock', 'userBlock', 'ublock');
           $template->set_block('adminBlock', 'hrBlock', 'hr');
           $template->set_block('adminBlock', 'lihrBlock', 'lihr');
           $template->set_block('adminBlock', 'minBlock', 'min');
           $template->set_block('adminBlock', 'liminBlock', 'limin');
           $template->set_block('adminBlock', 'monBlock', 'mon');
           $template->set_block('adminBlock', 'limonBlock', 'limon');
           $template->set_block('adminBlock', 'dayBlock', 'day');
           $template->set_block('adminBlock', 'lidayBlock', 'liday');
           $template->set_block('adminBlock', 'yrBlock', 'yr');
           $template->set_block('adminBlock', 'liyrBlock', 'liyr');


           if (!$this->isAdmin)
           {
              $template->set_var('ublock', null);
              $template->set_var('ablock', null);
           }
           else
           {

              global $USER_DB_URL, $ADMIN_ACCESS_REPORTER;
              $user_dbi = new DBI($USER_DB_URL);
              $userObj = new User($user_dbi, $this->getUID());

              $userArr = array();
              $userArr = $userObj->getUserList();
              asort($userArr);
              reset($userArr);

              while (list($uID, $email) = each($userArr))
              {
                 list($name, $host) = explode('@', $email);
                 $template->set_var('USER_ID', $uID);
                 $template->set_var('USER_NAME', ucwords($name));
                 if ($uID == $uid)
                 {
                    $template->set_var('UIDSELECTED', 'selected');
                 }
                 else
                 {
                    $template->set_var('UIDSELECTED', '');
                 }
                 $template->parse('ublock', 'userBlock', true);
              }
              $curTime = getdate(mktime());
              
              $curMin = $curTime['minutes'];
              $curHr = $curTime['hours'];
              $curMon = $curTime['mon'];
              $curDay = $curTime['mday'];
              $curYear = $curTime['year'];
              
              for ($i=0; $i<=59; $i++)
              {
                 $template->set_var('MIN', $i);
                 $template->set_var('LIMIN', $i);
                 $template->set_var('MIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 $template->set_var('LIMIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 if ($curMin == $i)
                 {
                    $template->set_var('LIMINSEL', 'selected');	
                    $template->set_var('MINSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MINSEL', null);		
                    $template->set_var('LIMINSEL', null);		
                 }
                 $template->parse('limin', 'liminBlock', true);
                 $template->parse('min', 'minBlock', true);
                 
              }
              
              for ($i=0; $i<=23; $i++)
              {
                 $template->set_var('HR', $i);
                 $template->set_var('LIHR', $i);
                 
                 $template->set_var('HR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 $template->set_var('LIHR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 
                 if ($curHr == $i)
                 {
                    $template->set_var('HRSEL', 'selected');	
                    $template->set_var('LIHRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('HRSEL', null);		
                    $template->set_var('LIHRSEL', null);		
                 }
                 $template->parse('hr', 'hrBlock', true);
                 $template->parse('lihr', 'lihrBlock', true);
              }
              for ($i=1; $i<=12; $i++)
              {
                 $template->set_var('MON', $i);
                 $template->set_var('LIMON', $i);
                 $template->set_var('MON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 $template->set_var('LIMON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 if ($curMon == $i)
                 {
                    $template->set_var('MONSEL', 'selected');	
                    $template->set_var('LIMONSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MONSEL', null);		
                    $template->set_var('LIMONSEL', null);		
                 }
                 $template->parse('mon', 'monBlock', true);
                 $template->parse('limon', 'limonBlock', true);
              }
              for ($i=1; $i<=31; $i++)
              {
                 $template->set_var('DAY', $i);
                 $template->set_var('LIDAY', $i);
                 if ($curDay == $i)
                 {
                    $template->set_var('DAYSEL', 'selected');	
                    $template->set_var('LIDAYSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('DAYSEL', null);		
                    $template->set_var('LIDAYSEL', null);		
                 }
                 $template->parse('day', 'dayBlock', true);
                 $template->parse('liday', 'lidayBlock', true);
              }
              
              for ($i=2002; $i<=MAX_YEAR; $i++)
              {
                 $template->set_var('YR', $i);
                 $template->set_var('LIYR', $i);
                 if ($curYear == $i)
                 {
                    $template->set_var('YRSEL', 'selected');	
                    $template->set_var('LIYRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('YRSEL', null);		
                    $template->set_var('LIYRSEL', null);		
                 }
                 $template->parse('yr', 'yrBlock', true);
                 $template->parse('liyr', 'liyrBlock', true);
              }
              $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);
              $template->parse('ablock','adminBlock',false);
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
           else
           {
              $indicator = $this->now();
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
           $dayStartTS = $startTS;
           $user_dbi = new DBI($USER_DB_URL);
           $analyzerObj = new ActivityAnalyzer($user_dbi);

           while ($dayStartTS <= $endTS)
           {
              global $WEEKEND;
              if (!in_array(date('D', $dayStartTS) , $WEEKEND))
              {
                 if (isset($monthDayCounter)) $monthDayCounter++;
                 else $monthDayCounter = 1;
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
              				'USER_ID'      => $this->userID
                             );
              $hours = $analyzerObj->analyzeDailyActivity($params);
              
              if (isset($total_office)) $total_office += $hours['OFFICE'];
              else $total_office = $hours['OFFICE'];
              if (isset($total_extra)) $total_extra += $hours['EXTRA'];
              else $total_extra = $hours['EXTRA'];             
              

              $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
              $template->set_var('WEEK_DAY', $day);
              $template->set_var('ROW_COLOR', $rowColor);
              $template->set_var('START_TS', empty($start) ? 'Not Available' : date("g:i a", $start));
              $template->set_var('END_TS', empty($end) ? 'Not Available' : date("g:i a", $end));
              $template->set_var('DAY_START', $dayStartTS);
              $template->set_var('DAY_END', $dayEndTS);
              $template->set_var('LOG_BOOK', $LOG_BOOK_MNGR);
              $template->set_var('USEID', $this->userID);
              $template->set_var('DATE', date("M-d-Y", $dayStartTS));
              //$template->set_var('USER_ID', $this->userID);
              $template->set_var('TOTAL_OFFICE_HOURS', $this->convert($hours['OFFICE']));
	      $template->set_var('TOTAL_EXTRA_HOURS', $this->convert($hours['EXTRA']));
              
              if ($hours['OFFICE'] < ((EXPECTED_OFFICE_HRS * SECONDS_PER_HOUR) - GRACE)  && !in_array(date('D', $dayStartTS) , $WEEKEND))
	          {
                   $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_ABNORMAL);
	          }
	          else
	          {
                   $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_NORMAL);
	          }
	          if ($dayStartTS == $today)
	          {
	               $template->set_var('CURRENT_DAY_MARKER', '*');
	          }
	          else
	          {
	               $template->set_var('CURRENT_DAY_MARKER', '&nbsp;');
	          }


              $template->parse('rblock', 'reportBlock', true);
              $dayStartTS = $dayEndTS;
           }
           $template->set_var('SUM_OFFICE_HOURS', $this->convert($total_office));
           $template->set_var('SUM_EXTRA_HOURS', $this->convert($total_extra));
           $template->set_var('AVRG_MAN_DAY', round((($total_office/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS),2));
           $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert($total_office/$monthDayCounter));
           $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert($total_extra/$monthDayCounter));
           $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);

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
           global $USER_DB_URL, $ACCESS_REPORTER, $LOG_BOOK_MNGR;
           $cmd = $this->getRequestField('cmd');
           $indicator = $this->getRequestField('indicator');
           $uid = $this->getRequestField('uid');
           
           $template = new Template($this->getTemplateDir());
           $template->set_file('fh1', $templateFile);
    	   $template->set_block('fh1', 'mainBlock', 'mblock');
           $template->set_block('mainBlock', 'reportBlock', 'rblock');
           $template->set_block('mainBlock', 'adminBlock', 'ablock');
           $template->set_block('adminBlock', 'userBlock', 'ublock');
           $template->set_block('adminBlock', 'hrBlock', 'hr');
           $template->set_block('adminBlock', 'lihrBlock', 'lihr');
           $template->set_block('adminBlock', 'minBlock', 'min');
           $template->set_block('adminBlock', 'liminBlock', 'limin');
           $template->set_block('adminBlock', 'monBlock', 'mon');
           $template->set_block('adminBlock', 'limonBlock', 'limon');
           $template->set_block('adminBlock', 'dayBlock', 'day');
           $template->set_block('adminBlock', 'lidayBlock', 'liday');
           $template->set_block('adminBlock', 'yrBlock', 'yr');
           $template->set_block('adminBlock', 'liyrBlock', 'liyr');

           if (!$this->isAdmin)
           {
              $template->set_var('ublock', null);
              $template->set_var('ablock', null);
           }
           else
           {

              global $USER_DB_URL, $ADMIN_ACCESS_REPORTER;
              $user_dbi = new DBI($USER_DB_URL);
              $userObj = new User($user_dbi, $this->getUID());

              $userArr = array();
              $userArr = $userObj->getUserList();
              asort($userArr);
              reset($userArr);

              while (list($uID, $email) = each($userArr))
              {
                 list($name, $host) = explode('@', $email);
                 $template->set_var('USER_ID', $uID);
                 $template->set_var('USER_NAME', ucwords($name));
                 if ($uID == $this->userID)
                 {
                    $template->set_var('UIDSELECTED', 'selected');
                 }
                 else
                 {
                    $template->set_var('UIDSELECTED', '');
                 }
                 $template->parse('ublock', 'userBlock', true);
              }
              /***************/
              $curTime = getdate(mktime());
              
              $curMin = $curTime['minutes'];
              $curHr = $curTime['hours'];
              $curMon = $curTime['mon'];
              $curDay = $curTime['mday'];
              $curYear = $curTime['year'];
              
              for ($i=0; $i<=59; $i++)
              {
                 $template->set_var('MIN', $i);
                 $template->set_var('LIMIN', $i);
                 $template->set_var('MIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 $template->set_var('LIMIN_LBL', date("i", mktime(0,$i,0,1,1,2002)));
                 if ($curMin == $i)
                 {
                    $template->set_var('LIMINSEL', 'selected');	
                    $template->set_var('MINSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MINSEL', null);		
                    $template->set_var('LIMINSEL', null);		
                 }
                 $template->parse('limin', 'liminBlock', true);
                 $template->parse('min', 'minBlock', true);
                 
              }
              
              for ($i=0; $i<=23; $i++)
              {
                 $template->set_var('HR', $i);
                 $template->set_var('LIHR', $i);
                 
                 $template->set_var('HR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 $template->set_var('LIHR_LBL', date("a h", mktime($i, 0, 0, 1, 1, 2002)));	
                 
                 if ($curHr == $i)
                 {
                    $template->set_var('HRSEL', 'selected');	
                    $template->set_var('LIHRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('HRSEL', null);		
                    $template->set_var('LIHRSEL', null);		
                 }
                 $template->parse('hr', 'hrBlock', true);
                 $template->parse('lihr', 'lihrBlock', true);
              }
              for ($i=1; $i<=12; $i++)
              {
                 $template->set_var('MON', $i);
                 $template->set_var('LIMON', $i);
                 $template->set_var('MON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 $template->set_var('LIMON_LBL', date("M", mktime(0,0,0,$i,1,2002)));
                 if ($curMon == $i)
                 {
                    $template->set_var('MONSEL', 'selected');	
                    $template->set_var('LIMONSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('MONSEL', null);		
                    $template->set_var('LIMONSEL', null);		
                 }
                 $template->parse('mon', 'monBlock', true);
                 $template->parse('limon', 'limonBlock', true);
              }
              for ($i=1; $i<=31; $i++)
              {
                 $template->set_var('DAY', $i);
                 $template->set_var('LIDAY', $i);
                 if ($curDay == $i)
                 {
                    $template->set_var('DAYSEL', 'selected');	
                    $template->set_var('LIDAYSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('DAYSEL', null);		
                    $template->set_var('LIDAYSEL', null);		
                 }
                 $template->parse('day', 'dayBlock', true);
                 $template->parse('liday', 'lidayBlock', true);
              }
              
              for ($i=2002; $i<=MAX_YEAR; $i++)
              {
                 $template->set_var('YR', $i);
                 $template->set_var('LIYR', $i);
                 if ($curYear == $i)
                 {
                    $template->set_var('YRSEL', 'selected');	
                    $template->set_var('LIYRSEL', 'selected');	
                 }
                 else
                 {
                    $template->set_var('YRSEL', null);		
                    $template->set_var('LIYRSEL', null);		
                 }
                 $template->parse('yr', 'yrBlock', true);
                 $template->parse('liyr', 'liyrBlock', true);
              }
              /****************/
              
              $template->set_var('ADMIN_ACCESS_RPT_MNGR', $ADMIN_ACCESS_REPORTER);
              $template->parse('ablock','adminBlock',false);
           }

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

           if (!strcmp($cmd, '<<'))
           {
              $indicator = $indicator - (7 * SECONDS_PER_DAY);

           }
           else if (!strcmp($cmd, '>>'))
           {
              $indicator = $indicator + (7 * SECONDS_PER_DAY);
           }
           else
           {
              $indicator = $this->now();
           }
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
           $dayStartTS = $startTS;
           $user_dbi = new DBI($USER_DB_URL);
           $analyzerObj = new ActivityAnalyzer($user_dbi);

           foreach ($weekDayNames as $day)
           {
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
              				'USER_ID'      => $this->userID
                             );
              $hours = $analyzerObj->analyzeDailyActivity($params);
              
              if (isset($total_office)) $total_office += $hours['OFFICE'];
              else $total_office = $hours['OFFICE'];
              if (isset($total_extra)) $total_extra += $hours['EXTRA'];
              else $total_extra = $hours['EXTRA'];
              

              $rowColor = ($i++ % 2 ) ? ACCESS_REPORT_EVEN_ROW_COLOR : ACCESS_REPORT_ODD_ROW_COLOR;
              $template->set_var('WEEK_DAY', $day);
              $template->set_var('ROW_COLOR', $rowColor);
              $template->set_var('START_TS', empty($start) ? 'Not Available' : date("g:i a", $start));
              $template->set_var('END_TS', empty($end) ? 'Not Available' : date("g:i a", $end));
              $template->set_var('DAY_START', $dayStartTS);
              $template->set_var('DAY_END', $dayEndTS);
              $template->set_var('LOG_BOOK', $LOG_BOOK_MNGR);
              $template->set_var('USEID', $this->userID);
              $template->set_var('DATE', date("M-d-Y", $dayStartTS));
              //$template->set_var('USER_ID', $this->userID);
              $template->set_var('TOTAL_OFFICE_HOURS', $this->convert($hours['OFFICE']));
	      $template->set_var('TOTAL_EXTRA_HOURS', $this->convert($hours['EXTRA']));
              
              global $WEEKEND;
              if ($hours['OFFICE'] < ((EXPECTED_OFFICE_HRS * SECONDS_PER_HOUR) - GRACE) && !in_array(date('D', $dayStartTS) , $WEEKEND))
	      {
                 $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_ABNORMAL);
	      }
	      else
	      {
                 $template->set_var('TOTAL_OFFICE_HRS_COLOR' ,ACCESS_RPT_OFFICE_HR_TEXT_COLOR_NORMAL);
	      }
	      if ($dayStartTS == $today)
	      {
	         $template->set_var('CURRENT_DAY_MARKER', '*');
	      }
	      else
	      {
	         $template->set_var('CURRENT_DAY_MARKER', '&nbsp;');
	      }

              $template->parse('rblock', 'reportBlock', true);

              $dayStartTS = $dayEndTS;
           }
           $template->set_var('SUM_OFFICE_HOURS', $this->convert($total_office));
           $template->set_var('SUM_EXTRA_HOURS', $this->convert($total_extra));
           $template->set_var('AVRG_MAN_DAY', round((($total_office/SECONDS_PER_HOUR)/EXPECTED_OFFICE_HRS),2));
           $template->set_var('AVRG_OFFICE_HRS_PER_DAY', $this->convert($total_office/6));
           $template->set_var('AVRG_EXTRA_HRS_PER_DAY', $this->convert($total_extra/6));
           $template->set_var('ACCESS_RPT_MNGR', $ACCESS_REPORTER);

          return $template->parse('mblock', 'mainBlock');
      }

      function convert($sec)
      {
		list($hr, ) = explode('.', ($sec / 3600));
		list($min, ) = explode('.', ($sec % 3600) / 60);
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

   }//class


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