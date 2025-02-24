<?php

   define(MAX, 100);

   ob_start("ob_gzhandler"); 

   $output = '';

   for($i=0;$i<=MAX;$i++)
   {
       $output .= "This is line $i <br>";
   }

   echo $output;
?>
