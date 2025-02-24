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
$DB_URL = 'mysql://root:foobar@localhost/products';

// Create a DBI object that connects to the
// database URL
$dbi = new DBI($DB_URL);

if (! $dbi->isConnected())
{
    echo "Connection failed for $DB_URL<br>";
    exit;
}

// Create a SQL statement to fetch data
$statement = 'SELECT ID, NAME FROM PROD_TBL';

// Execute the statement using DBI query method
$result = $dbi->query($statement);

// If the result of query is NULL then show
// database error message
if ($result == NULL)
{
    echo "Database error:" . $dbi->getError() . "\n"; 

// Else check if there are no data available or not
} else if (! $result->numRows()){

     echo "No rows found.";

// Now data is available so fetch and print data 
} else {

    echo "<pre>ID\tNAME<br>";

    while ($row = $result->fetchRow())
    {
         echo $row->ID, "\t", $row->NAME, "<br>";
    }
    echo "</pre>";
} 

?>
