<?php

// Turn on all error reporting
error_reporting(E_ALL);

// If you have installed framewirk directory in
// a different directory than
// %DocumentRoot%/framework, change the setting below.
$APP_FRAMEWORK_DIR=$_SERVER['DOCUMENT_ROOT'] . '/framework';

// Insert the path in the PHP include_path so that PHP
// looks for our PEAR, PHPLIB and application framework
// classes in these directories
ini_set( 'include_path', ':' .
         $APP_FRAMEWORK_DIR . ':' .
         ini_get('include_path'));

// Now load our Debugger class from application framework
require_once('class.PHPApplication.php');
require_once('class.DBI.php');
require_once('class.Debugger.php');
require_once('class.ErrorHandler.php');

require_once 'DB.php';

class testPHPApp extends PHPApplication {

   function run()
   {
      $fieldType = array('mm'  => 'month',
                         'dd'  => 'day',
                         'yy'=> 'year'
                       );
               
      reset($fieldType);
      
      $errCode = array();
      
      while (list($k, $v) = each($fieldType))
      {
         $fields{$k} = (! empty($_REQUEST[$k])) ? $_REQUEST[$k] : null;
      
         $errCode{$k} = 'MISSING_' . strtoupper($k) ;
      }
      
      // Check required fields
      $err = $this->checkRequiredFields($fieldType, $fields, $errCode);

      $this->dump_array($err);

   }

      
}//class 

   define('APPLICATION_NAME', 'Test Application');
   define('FORM_DB_URL', 'mysql://root:foobar@localhost/auth');
   $OFF = FALSE;

   $thisApp = new testPHPApp(
                                    array('app_name'              => APPLICATION_NAME,
                                          'app_version'           => '1.0.0',
                                          'app_type'              => 'WEB',
                                          'app_auto_connect'      => TRUE,
                                          'app_auto_authorize'    => FALSE,
                                          'app_auto_chk_session'  => FALSE,
                                          'app_debugger'          => $OFF,
                                          'app_db_url'            => FORM_DB_URL,
                                         )
                                    );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>

