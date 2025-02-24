<?php
 
    // If you have installed PEAR packages in a different
   // directory than %DocumentRoot%/pear change the setting below.
   $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;
  
   $PATH = $PEAR_DIR;

   ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));

   require_once 'Benchmark/Iterate.php';
   
   define(MAX_RUN, 100);
   $data  = array(1, 2, 3, 4, 5);

   doBenchmark('v1', $data);
   doBenchmark('v2', $data);
   doBenchmark('v3', $data);
   doBenchmark('v4', $data);

   function doBenchmark($functionName = null, $arr = null)
   {

     reset($arr);

     $benchmark = new Benchmark_Iterate;
     $benchmark->run(MAX_RUN, $functionName, $arr);
     $result = $benchmark->get();

     echo '<br>';
     printf("%s ran %d times where average exec time %.5f ms",
            $functionName,
            $result['iterations'], 
            $result['mean'] * 1000);

   }

   function v1($myArray = null) {

     // Do bad loop
     for ($i =0; $i < sizeof($myArray); $i++)
     {
         echo '<!--' . $myArray[$i] . ' --> ';
     }

   }

   function v2($myArray = null) {

     // Do better loop

     // Get the size of array
     $max = sizeof($myArray);

     for ($i =0; $i < $max  ; $i++)
     {
         echo '<!--' . $myArray[$i] . ' --> ';
     }
   }


   function v3($myArray = null) {

     // Do much better loop

     // Get the size of array
     $max = sizeof($myArray);

     for ($i =0; $i < $max  ; $i++)
     {
         // Store the output in a string
         $output .= '<!--' . $myArray[$i] . ' --> ';
     }

     // Echo the output string
     echo $output;
   }

   function v4($myArray = null) {

     // Do much better loop

     // Get the size of array
     $max = sizeof($myArray);
 
     $output = array();

     for ($i =0; $i < $max  ; $i++)
     {
         // Store the output in a string
         array_push($output,  '<!--' . $myArray[$i] . ' --> ');
     }

     // Echo the output string
     echo implode('', $output);
   }


?>
