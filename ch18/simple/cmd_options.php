#!/usr/bin/php -q
<?php


   $CMD_SHORT_OPTIONS = 'hs:k';
   $CMD_LONG_OPTIONS  = array('help', 'size=', 'king=');

   // Set this to the PEAR directory
   $PEAR_DIR           = '/evoknow/intranet/htdocs/pear' ;

   ini_set( 'include_path', ':' . $PEAR_DIR . ':' . ini_get('include_path'));

   require_once "Console/Getopt.php";

   $cmd = getCommandLineOptions(Console_Getopt::getopt($GLOBALS['argv'],
                                $CMD_SHORT_OPTIONS,
                                $CMD_LONG_OPTIONS)
                               );


   if ($cmd == null)
   {
      syntax();
   } 
   else if (isset($cmd['h']) || isset($cmd['help']))
   {
      echo "You selected help option.\n";

   }

   if (isset($cmd['s']) || isset($cmd['size']))
   {

      echo "You selected size option. Chosen size is " . $cmd['s'] . ' ' . $cmd['size'] . "\n";

   } 

   exit;

   function syntax()
   {
        $script = basename($GLOBALS['argv'][0]);

        echo<<<HELP

        Syntax $script [-h | --help] [-s bytes | --size=bytes]

        More help will be added later.



HELP;

   }

   function getCommandLineOptions($options)
   {

      $type = gettype($options);

      if (gettype($options) != "array")
      {
          // Error in command line
          echo "$options->message \n";
          return null;
      }

      $cmd = array();

      foreach ($options[0] as $argArray)
      {

        $argName = preg_replace('/[^\w]/' , '', $argArray[0]);

        $argValue = $argArray[1];

        $cmd[$argName] = ($argValue != '') ? $argValue : TRUE;
      }

      return (count($cmd) > 0) ? $cmd : null;

   }

?>
