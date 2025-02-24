<?php

  // Enable all error reporting
  error_reporting(E_ALL);

  require_once('app_name.conf');

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
