#!/usr/bin/php -q
<?php

   $name = prompt('Enter your name: ');

   echo "Hello $name\n";

   exit;

   function prompt($label = null)
   {
      echo $label;
      return getSTDIN(); 
   }

   function getSTDIN()
   {

     $STDIN =fopen("/dev/stdin","r");

     $keyboardBuffer = null;

     if ($STDIN)
     {
        while(($ch = fgetc($STDIN)) != "\n")
        {   
           $keyboardBuffer .= $ch;
        }

        fclose($STDIN);
     }

     return $keyboardBuffer;
 
   }

?>
