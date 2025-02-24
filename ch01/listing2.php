<?php

   error_reporting(E_ALL);

  // Set PHPLIB path
   $PHPLIB_DIR         = $_SERVER['DOCUMENT_ROOT'] . '/phplib';

  // Add PHPLIB path to PHP's include path
   ini_set( 'include_path', ':' . $PHPLIB_DIR . ':' . ini_get('include_path'));

  // Include the PHPLIB template class
  include('template.inc');

  // Setup this application's template directory path
  $TEMPLATE_DIR = $_SERVER['DOCUMENT_ROOT'] . '/ch1/templates';

  // Setup the output template filename
  $OUT_TEMPLATE = 'listing2out.html';

  // Get name from GET or POST request
  $name = (! empty($_REQUEST['name'])) ? $_REQUEST['name'] : null;


  // Create a new template object
  $t = new Template($TEMPLATE_DIR);

  // Set the template file for this object to application's template
  $t->set_file("page", $OUT_TEMPLATE);

  // Setup the template block
  $t->set_block("page", "mainBlock" , "main");

  // Set the template variable = value
  $t->set_var("NAME", $name);

  // Parse the template block with all predefined key=values
  $t->parse("main", "mainBlock", false);

  // Parse the entire template and print the output
  $t->pparse("OUT", "page");

?>
