<?php


  // If you have installed PEAR packages in a different
  // directory than %DocumentRoot%/pear change the setting below.
  $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

  $PATH = $PEAR_DIR;
  ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));

  require_once 'Cache/Function.php';

  $cacheDir = '/tmp/pear_cache/';

  $cache = new Cache_Function('file', 
                               array('cache_dir' => $cacheDir) 
                              );

  $arr = array('apple', 'orange');

  $cache->call('slowFunction', $arr);

  echo '<p>';

  $arr = array('banana', 'grapes');
  slowFunction($arr);
  
  function slowFunction($arr = null)
  {
     echo "Very slow function <br>";
     echo "Time is " . date('M-d-Y H:i:s A', time()) . '<br>';
     foreach ($arr as $fruit)
     {
        echo "Got $fruit <br>";
     }
  }
  
  
  
?>
