<?php

  // Enable all error reporting
  error_reporting(E_ALL);

  // Initialize 
  $is_customer = FALSE;

  // Get coupon code
  $couponCode = (! empty($_REQUEST['couponCode'])) ?
                 $_REQUEST['couponCode'] : null;
   
  if (is_coupon($couponCode))
  {
      $is_customer = isCustomer(); 
  } 

  if ($is_customer)
  {
       echo "You are a lucky customer\n";
       echo "You win big today!\n";

  } else {
       echo "Sorry you do not win!\n";
  }

  function is_coupon($code = null)
  {
      // some code to verify coupon code
      echo "Check if user given coupon is valid or not <br>";
      return ($code % 1000 == 0) ? TRUE : FALSE;

  }

  function isCustomer()
  {
     // a function to determine if current user
     // user is a customer or not.
     // not implemented.
     echo "Check if user is customer <br>";
     return FALSE;
  }


?>
