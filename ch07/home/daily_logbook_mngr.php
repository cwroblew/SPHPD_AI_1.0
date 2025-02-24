<?php

   require_once "home.conf";

   require_once $INTRANET_USER_CLASS;
   require_once $ACTIVITY_ANALYZER_CLASS;


   class dailyLogbookMngrApp extends PHPApplication {

      function run()
      {
          $useID = $this->getRequestField('useID');
          
          if (! $this->authorize())
          {
             $this->alert('UNAUTHORIZED_ACCESS');
             exit;
          }
          if (!empty($useID) && $this->isAdmin)
          {
             $this->userID = $useID;
          }
          else
          {
             $this->userID = $this->getUID();
          }
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
          global $LOG_DETAIL_TEMPLATE, $USER_DB_URL;
          
          $startTS = $this->getRequestField('startTS');
          
          $officeStartTS = $startTS + (OFFICE_START_TIME * SECONDS_PER_HOUR);
          $officeEndTS = $startTS + (OFFICE_END_TIME * SECONDS_PER_HOUR);
          $lunchStartTS = $startTS + (LUNCH_START_TIME * SECONDS_PER_HOUR);
          $lunchEndTS = $startTS + (LUNCH_END_TIME * SECONDS_PER_HOUR);
          $dayEndTS = $startTS + SECONDS_PER_DAY;
          
          $params = array(
              				'OFFICE_START' => $officeStartTS,
              				'OFFICE_END'   => $officeEndTS,
              				'LUNCH_START'  => $lunchStartTS,
              				'LUNCH_END'    => $lunchEndTS,
              				'DAY_START'    => $startTS,
              				'DAY_END'      => $dayEndTS,
              				'USER_ID'      => $this->userID
                             );
                             
          $analyzerObj = new ActivityAnalyzer($this->dbi);
          
          $log = $analyzerObj->getDailyLog($params);
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $LOG_DETAIL_TEMPLATE); 
          $template->set_block('fh', 'mainBlock', 'mblock');
          $template->set_block('mainBlock', 'logBlock', 'lblock');
          
          
          
          if (!empty($log))
          {
          
              foreach ($log as $logDetail)
              {
                 	$template->set_var('LOGIN', $logDetail['login']);
                 	$template->set_var('LOGOUT',$logDetail['logout']);
                 	$template->set_var('OFF_HR', $this->convert($logDetail['office']));
                 	$template->set_var('EXT_HR', $this->convert($logDetail['extra']));
                 	$template->parse('lblock', 'logBlock', true);
              }
          
          }
          else
          {
             $template->set_var('lblock', null);	
          }
          $template->set_var('DATE',date("M-d-Y", $startTS));
          $user_dbi = new DBI($USER_DB_URL);
          $userObj = new User($user_dbi, $this->userID);
          list($uname, $host) = explode('@', $userObj->getEmail());
          $template->set_var('USERNAME', ucwords($uname));
          $template->parse('mblock', 'mainBlock', false);
          $template->pparse('output', 'fh');
          
          
          
      }
      
      function convert($sec)
      {
		list($hr,) = explode('.', ($sec / 3600));
		list($min,) = explode('.', ($sec % 3600) / 60);
		$second = (($sec % 3600) / 60) % 60;
		return $hr.' hrs '.$min.' mins';

      }    
   }//class


   /* Session variables must be defined before session_start() method is called */
   $SESSION_USERNAME = null;
   $SESSION_USER_ID = null;
   $SESSION_PASSWORD = null;

   global $INTRANET_DB_URL;
   $thisApp = new dailyLogbookMngrApp(
                            array( 'app_name'             => $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_db_url'            => $INTRANET_DB_URL,
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_chk_session'  => FALSE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_debugger'          => $OFF
                                 )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
