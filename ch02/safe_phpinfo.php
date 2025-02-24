<?php

   // Enable all error reporting
   error_reporting(E_ALL);


   // Create a list of valid IP addresses that can access
   // this script
   $validIPList = array('192.168.1.1', '192.168.1.2');

   // If current remote IP address is not in our valid list of IP
   // addresses, do not allow access
   if (! in_array($_SERVER['REMOTE_ADDR'], $validIPList))
   {
      echo "You do not access to this script.";
      exit; 
   }

   // OK, we have a valid IP address requesting this script
   // so show page
   phpinfo();

?>
