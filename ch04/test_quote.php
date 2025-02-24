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
require_once('class.DBI.php');

// Setup the database URL
$DB_URL = 'mysql://root:foobar@localhost/foobar';

// Create a DBI object that connects to the
// database URL
$dbi = new DBI($DB_URL);

if (! $dbi->isConnected())
{
    echo "Connection failed for $DB_URL<br>";
    exit;
}

$id = 100;
$name = "Joe Gunchy";

$name = $dbi->quote($name);

$statement = "INSERT INTO PROD_TBL (ID,NAME) " .
             "VALUES($id, $name)";

$result = $dbi->query($statement);

if ($result == NULL)
{
    echo "Database error:" . $dbi->getError() . "<BR>\n"; 

} else {
 
      echo "Added $name in database.<BR>\n";
 } 

?>

