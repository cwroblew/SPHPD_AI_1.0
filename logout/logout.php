<?php

global $APPLICATION_NAME, $ON;
require_once "logout.conf.php";


   class logoutApp extends PHPApplication {

      function run()
      {
         global $HOME_URL;

         if ($this->is_authenticated())
         {
             $this->logout();
             header("Location: $HOME_URL");

         } else {
             $this->alert($this->getMessage('LOGOUT_NOT_LOGGED_IN'));
         }
      }

      function logout()
      {

         // Log user activity
         $thisUser = new User($this->dbi, $this->getUID());
         $thisUser->logActivity(LOGOUT);

         session_unset();
         session_destroy();

      }

      function is_authenticated()
      {
         
         $SESS_UNAME = $this->getSessionField("SESSION_USERNAME");
         if (!empty($SESS_UNAME))
         {
             return 1;

         } else {

             return 0;
         }
      }
   }

   /* Session variables must be defined before session_start() method is called */
   $count            = 0;
   $SESSION_USERNAME = null;
   $SESSION_PASSWORD = null;
   $SESSION_USER_ID = null;

   global $AUTH_DB_URL;

   $thisApp = new logoutApp(
   			   array( 'app_name' 	         => $APPLICATION_NAME,
                                  'app_version'          => '1.0.0',
                                  'app_type' 	         => 'WEB',
                                  'app_db_url'           => $AUTH_DB_URL,
                                  'app_auto_connect'     => TRUE,
                                  'app_auto_authorize'   => FALSE,
                                  'app_auto_chk_session' => FALSE,
                                  'app_debugger'         => $ON
                                 )
                          );


   $thisApp->buffer_debugging();
   $thisApp->debug("This is $thisApp->app_name application");
   $thisApp->run();
   $thisApp->dump_debuginfo();


