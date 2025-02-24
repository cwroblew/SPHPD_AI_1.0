<?php

 // Enable all error reporting
  error_reporting(E_ALL);

  require_once('calc2.conf');
  require_once('calc2.errors');

  // Get inputs from GET or POST request
  $num1 = (! empty($_REQUEST['num1'])) ? $_REQUEST['num1'] : null;
  $num2 = (! empty($_REQUEST['num2'])) ? $_REQUEST['num2'] : null;

  $operator = (! empty($_REQUEST['operator'])) ? $_REQUEST['operator'] : null;

  // Set errors to null
  $errors = null;

  // If number 1 is not given, error occured
  if ($num1 == null)
  {
      $errors .= $ERRORS[LANGUAGE]['NUM1_MISSING'];
  }

  // If number 2 is not given, error occured
  if ($num2 == null) {
      $errors .= $ERRORS[LANGUAGE]['NUM2_MISSING'];
  }

  // If operator  is not given, error occured
  if (empty($operator)) {
      $errors .= $ERRORS[LANGUAGE]['OPERATOR_MISSING'];
  }

  // Set result to null
  $result = null;

  // If operation is + do addition: num1 + num2
  if (!strcmp($operator, '+'))
  {
     $result = $num1 + $num2;

  // If operation is - do subtraction: num1 - num2
  } else if(! strcmp($operator, '-')) {
     $result = $num1 - $num2;

  // If operation is * do multiplication: num1 * num2
  } else if(! strcmp($operator, '*')) {
     $result = $num1 * $num2;

  // If operation is / do division: num1 / num2
  } else if(! strcmp($operator, '/')) {

     // If second number is 0, show divide by zero exception 
     if (! $num2) {
         $errors .= $ERRORS[LANGUAGE]['DIVIDE_BY_ZERO'];
     } else {
        $result = sprintf("%.2f", $num1 / $num2);
     }
  }

  // Create a new template object
  $t = new Template($TEMPLATE_DIR);

  // Set the template file for this object to application's template
  $t->set_file("page", $OUT_TEMPLATE);

  // Setup the template block
  $t->set_block("page", "mainBlock" , "main");

  // Set the template variable = value
  $t->set_var("ERRORS", $errors);
  $t->set_var("NUM1", $num1);
  $t->set_var("NUM2", $num2);
  $t->set_var("OPERATOR", $operator);
  $t->set_var("RESULT", $result);

  // Parse the template block with all predefined key=values
  $t->parse("main", "mainBlock", false);

  // Parse the entire template and print the output
  $t->pparse("OUT", "page");

?>
