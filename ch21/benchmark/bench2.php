<?php

  // If you have installed PEAR packages in a different
  // directory than %DocumentRoot%/pear change the setting below.
  $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

  $PATH = $PEAR_DIR;
  ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));
  require_once 'Benchmark/Timer.php';

  $timer = new Benchmark_Timer();

  $timer->start();
  $timer->setMarker('start_myFunction');

  for($i=0; $i<10; $i++)
  {
     myFunction($argument);
  }

  $timer->setMarker('end_myFunction');
  $timer->stop();
  $profiling = $timer->getProfiling();

  echo "<p>Time elapsed: " . $timer->timeElapsed('start_myFunction', 'end_myFunction') . "</p>";

  echo "<pre>";
  print_r($profiling);
  echo "</pre>";

  exit;

  function myFunction($var) {

    static $counter = 0;
    // do something
    echo $counter++ . ' ';
  }

?>
