<?php

// Turn on all error reporting
error_reporting(E_ALL);

// If you have installed PEAR packages in a different
// directory than %DocumentRoot%/pear change the 
// setting below.
$PEAR_DIR = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

// If you have installed PHPLIB in a different
// directory than %DocumentRoot%/phplib, change 
// the setting below.
$PHPLIB_DIR = $_SERVER['DOCUMENT_ROOT'] . '/phplib';

// If you have installed framewirk directory in 
// a different directory than 
// %DocumentRoot%/framework, change the setting below.
$APP_FRAMEWORK_DIR=$_SERVER['DOCUMENT_ROOT'] . '/framework';

// Create a path consisting of the PEAR, 
// PHPLIB and our application framework 
// path ($APP_FRAMEWORK_DIR)
$PATH = $PEAR_DIR . ':' . 
        $PHPLIB_DIR . ':' . 
        $APP_FRAMEWORK_DIR;

// Insert the path in the PHP include_path so that PHP
// looks for our PEAR, PHPLIB and application framework
// classes in these directories
ini_set( 'include_path', ':' . 
         $PATH . ':' . 
         ini_get('include_path'));

// Now load the DB.php class from PEAR
require_once 'DB.php';

// Now load our DBI class from application framework
// directory
require_once('class.DBI.php');

// Set the database URL to point to a MySQL 
// database. In this example, the database is
// pointing to a MySQL database called auth on
// the localhost server, which requires username
// (root) and password (foobar) to login
$DB_URL = 'mysql://root:foobar@localhost/auth';

// Create a DBI object using our DBI class
// Use the database URL to initialize the object
// and make connection to the database
$dbi = new DBI($DB_URL);

// Dump the contents of the DBI object to
// see what it contains.
echo "<pre>";
print_r($dbi);
echo "</pre>";

?>

