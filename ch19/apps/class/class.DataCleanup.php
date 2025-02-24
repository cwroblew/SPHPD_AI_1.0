<?php

   class DataCleanup {

     function DataCleanup()
     {

     }

     function cleanup_none ($str) {
        return $str;
     }

     function cleanup_ucwords ($str) {
        // Uppercase first character of each word in string
        return ucwords($str);
     }

     function cleanup_ltrim ($str) {

        // remove all white spaces from left of the string
        return ltrim($str);

     }

     function cleanup_rtrim ($str) {
        // remove all white spaces from the right of the string
        return rtrim($str);
     }


     function cleanup_trim ($str) {
        // remove all white spaces from the left and right of the string
        return trim($str);
     }

     function cleanup_lower($str) {     //**** method added
     	// convert to lowercase of the given string
     	return strtolower($str);
     }
     
   }//class


?>
