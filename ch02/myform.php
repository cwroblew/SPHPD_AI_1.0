<?php

  error_reporting(E_ALL);

  define('DEBUG', FALSE);

  include("class.Validator.php3");

  // Create a Validator object
  $check = new Validator ();

  // Get User data
  $email = (! empty($_REQUEST['email'])) ? $_REQUEST['email'] : null;
  $state = (! empty($_REQUEST['state'])) ? $_REQUEST['state'] : null;  
  $phone = (! empty($_REQUEST['phone'])) ? $_REQUEST['phone'] : null;
  $zip =   (! empty($_REQUEST['zip']))   ? $_REQUEST['zip']   : null;
  $url =   (! empty($_REQUEST['url']))   ? $_REQUEST['url']   : null;
  
  DEBUG and print "Debug Code here \n";

  // Call validation methods

  if (!$check->is_email($email))  { echo "Invalid email format<br>\n";}
  if (!$check->is_state($state))  { echo "Invalid state code<br>\n";  }
  if (!$check->is_phone($phone))  { echo "Invalid phone format<br>\n";}
  if (!$check->is_zip($zip))      { echo "Invalid zip code<br>\n";    }
  if (!$check->is_url($url))      { echo "Invalid URL format<br>\n";  }
  
  // If form data has errors show error and exit
  if ($check->ERROR)
  { 
      echo "$check->ERROR<br>\n";       
      exit;
  }

  // Process form now
  echo "Form processing not shown here.<br>";

?>
