<?php 

   $cachetimeout=5; 

   require_once($_SERVER['DOCUMENT_ROOT'] . '/ch21/cache/jpcache/jpcache.php');


   echo "Now is : " . date('M-d-Y H:i:s', time()) . '<br>';

?>

