<?php
 
    // If you have installed PEAR packages in a different
   // directory than %DocumentRoot%/pear change the setting below.
   $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;
  
   $PATH = $PEAR_DIR;
   

   ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));

   require_once 'Benchmark/Iterate.php';
   
   $benchmark = new Benchmark_Iterate;
 
  function myFunction($var) {
      // do something
      echo 'x ';
  }
 
  $benchmark->run(10, 'myFunction', $argument);

  $result = $benchmark->get();

  echo "<pre>";
  print_r($result);
  echo "</pre>";
?>
