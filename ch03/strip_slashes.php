<?php

   error_reporting(E_ALL);

   $orig = "A\B\C ";
   $email = $orig;
   #$email = addslashes($orig);
   $withStripSlashes = stripslashes($email);

   echo " 	   Original: $orig\n
           After addslashes: $email\n
         After stripslashes: $withStripSlashes\n";

   $dbl = mysql_connect('localhost', 'root', 'foobar');

   print_r($dbl);

   mysql_select_db('auth', $dbl); 

   $sql = "insert into users (EMAIL) values('$email')";

   echo $sql . "\n";

   $rs = mysql_query($sql, $dbl);

   print_r($rs);
   
?>
