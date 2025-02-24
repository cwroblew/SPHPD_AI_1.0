<?php

   error_reporting(E_ALL);

   $name = (! empty($_REQUEST['field1'])) ? $_REQUEST['field1'] : "Friend";

   outputDisplayMsg("Hello $name");

   exit;
   
   
   function outputDisplayMsg($outTextMsgData = null)
   {
      echo $outTextMsgData;
   }
?>   
