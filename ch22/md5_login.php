<?php

   error_reporting(E_ALL);

   define('PASSWORD', '2manysecrets');

   $user = (! empty($_REQUEST['username'])) ? $_REQUEST['username'] : null;

   $givenMD5Hash = (! empty($_REQUEST['password'])) ? $_REQUEST['password'] : null;

   // If user given MD5 of password does not match
   // with the md5(PASSWORD) then redirect
   // user to login page again

   if (strcmp($givenMD5Hash , md5(PASSWORD)))
   {
       header("Location: md5_login.html");
   
   } 

   // User knows password so login successful
   echo "Welcome to PHP.";

?>
