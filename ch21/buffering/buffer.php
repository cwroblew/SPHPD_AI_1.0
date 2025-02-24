<?php

   define(MAX, 1024 * 10);


  // If you have installed PEAR packages in a different
  // directory than %DocumentRoot%/pear change the setting below.
  $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

  $PATH = $PEAR_DIR;
  ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));
  require_once 'Benchmark/Timer.php';

  $timer = new Benchmark_Timer();


  $kb = MAX / 1024;

  // No output buffering
  $timer->start();
  doSomething();
  $timer->stop();
  printf("Buffer: OFF Size: %d KB Time elapsed: %.3f<br>" ,$kb , $timer->timeElapsed());

  // Enable output buffering
  ob_start(); 
  $timer->start();
  doSomething();
  $timer->stop();
  printf("Buffer : ON Size: %d KB Time elapsed: %.3f<br>" , $kb , $timer->timeElapsed());

  exit;

  function doSomething()
  {
     $output = '';

     for($i=0;$i<=MAX;$i++)
     {
         echo 'x';
     }

  }
?>
