<?php


  // If you have installed PEAR packages in a different
  // directory than %DocumentRoot%/pear change the setting below.
  $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

  $PATH = $PEAR_DIR;
  ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));

  require_once 'Cache/Output.php';

  $cacheDir = '/tmp/pear_cache';
  
  $cache = new Cache_Output('file', 
                            array('cache_dir' => $cacheDir) 
                           );

  // If user does not want to view cached version
  // she has to give ?nocache=anyvalue to view fresh contents
  if (empty($_REQUEST['nocache']))
  {
    // Create a unique cache identifier based on
    // the requet + cookie information
  
     $cache_id = $cache->generateID(array('url' => $REQUEST_URI,
                                         'post' => $HTTP_POST_VARS, 
                                         'cookies' => $HTTP_COOKIE_VARS) 
                                   );
  } else {
     
     // User wants fresh contents so set cache ID to null
     $cache_id = null;
     
  }

  // See if cached contents is available for the cache ID
  if ($content = $cache->start($cache_id)) 
  {
      // Cache has contents, display and terminate
      echo $content;
      die();
  }

  // Cache does not have contents
  // Generate content and write cache

  echo "This is the contents<P>";
  echo "Time is " . date('M-d-Y H:i:s A', time()) . "<BR>";
 
  // write contents to cache file 
  echo $cache->end();
  
?>
