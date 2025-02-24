<?php

   error_reporting(E_ALL);

   $name = (! empty($_REQUEST['field1'])) ? $_REQUEST['field1'] : "Friend";

   showMessage("Hello $name");

   exit;
   
   function showMessage($outTextMessageData = null)
   {
      echo $outTextMessageData;
   }
?>   
