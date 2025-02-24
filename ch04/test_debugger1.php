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
require_once('class.Debugger.php');

$myDebugger = new Debugger(array(
                            'color'   => 'red',
                            'prefix'  => 'MAIN',
                            'buffer'  => FALSE)
                           );


$fruits = array('apple', 'orange', 'banana');

$myDebugger->debug_array($fruits);


?>

