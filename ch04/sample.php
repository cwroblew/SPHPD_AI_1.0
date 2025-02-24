<?php
   // Turn on all error reporting
   error_reporting(E_ALL);
   
   require_once 'sample.conf';
   require_once 'sample.errors';
   require_once 'sample.messages';
   
   $thisApp = new sampleApp(
                            array(
                                  'app_name'             => 'Sample Application',
                                  'app_version'          => '1.0.0',
                                  'app_type'             => 'WEB',
                                  'app_db_url'           => $GLOBALS['SAMPLE_DB_URL'],
                                  'app_auto_authorize'   => FALSE,
                                  'app_auto_chk_session' => FALSE,
                                  'app_auto_connect'     => FALSE,
                                  'app_type'             => 'WEB',
                                  'app_debugger'         => $ON
                                 )
                          );


   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->debug("Version : " . $thisApp->get_version());
   $thisApp->dump_debuginfo();

?>
