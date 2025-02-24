<?php

  error_reporting(E_ALL);

  // This bad example will only work
  // if you have register_globals = Off
  // in your php.ini.

  // This example is for educational
  // purpose only. It will not work in
  // sites with register_globals = On
   
  global $couponCode;

  if (is_coupon($couponCode))
  {
      $is_customer = isCustomer(); 

  } 

  if ($is_customer)
  {
       echo "You are a lucky customer.<br>";
       echo "You won big today!<br>";

  } else {
       echo "Sorry you did not win!<br>";
  }

  function is_coupon($code = null)
  {
      // some code to verify coupon code
      echo "Check if user given coupon is valid or not.<br>";
      return ($code % 1000 == 0) ? TRUE : FALSE;

  }

  function isCustomer()
  {
     // a function to determine if current user
     // user is a customer or not.
     // not implemented.
     echo "Check if user is a customer or not.<br>";
     return FALSE;
  }


?>
