<?php

   require_once "home.conf";   
   
   class IntranetUserTipApp extends PHPApplication {

      function run()
      {
          global $TIP_URL, $MAX_AVAILABLE_TIP;
          
          
          
          $TIP_URL = sprintf("%s/%d.html", $TIP_URL, rand(1,$MAX_AVAILABLE_TIP));
          header("Location: $TIP_URL");
     }

      function authorize()
      {
          return TRUE;
      }

   }//class

   global $INTRANET_DB_URL;

   $thisApp = new IntranetUserTipApp(
                            array( 'app_name'           => $APPLICATION_NAME,
                                  'app_version'         => '1.0.0',
                                  'app_type'            => 'WEB',
                                  'app_db_url'          => $INTRANET_DB_URL,
                                  'app_auto_connect'    => FALSE,
                                  'app_auto_authorize'  => FALSE,
                                  'app_auto_chk_session' => FALSE,
                                  'app_debugger'        => $OFF
                                 )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
