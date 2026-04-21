#!/usr/bin/php -q
<?php

   require_once("netgeo.php");

   // Get a list of hosts/ip from command line
   $hostList = getHostList();

   // if no host/ip was given show syntax msg
   if (count($hostList) < 1)
   {
       echo "Syntax: " . basename($GLOBALS['argv'][0])  ." host | ip_address\n";
       exit;
   }

   // For each host/ip find geo location
   foreach ($hostList as $host)
   {
      //findLocation($host);
      findLocation($host);
      echo "-------------------------\n";
   }
   
   exit;

function findLocation($hostname = null)
{

   // Create a netgeo class object
   $netgeo=new netgeo_class;

   // Find location for the given host/ip
   if($netgeo->GetAddressLocation($hostname,$location))
   {
      
      // Set longitude and latitude from retrieved data
      $longitude=doubleval($location["LONG"]);
      $latitude=doubleval($location["LAT"]);

      // Show output
      echo "Your approximate location:\n";

      if(IsSet($location["CITY"]) || IsSet($location["STATE"]) || IsSet($location["COUNTRY"]))
      {
         if(IsSet($location['CITY']))    echo "City    : " . $location['CITY'] . "\n";
         if(IsSet($location['STATE']))   echo "State   :" . $location['STATE'] . "\n";
         if(IsSet($location['COUNTRY'])) echo "Country :".  $location['COUNTRY'] . "\n";
      }

      echo "Longitude:".($longitude>=0.0 ? $longitude."degree East" : (-$longitude)."degree West")."\n";
      echo "Latitude:".($latitude>=0.0 ? $latitude."degree North" : (-$latitude)."degree South")."\n";
   }
   else
   {
      echo "Cannot find location.\n";
      echo "Error: ".$netgeo->error."\n";
   }
}
   function getHostList()
   {
      $arr = array();
      
      // Except for the first argument in the command
      // line, insert all in a list as host/ip 
      // Note: first argument is the name of the script.
      foreach($GLOBALS['argv'] as $key => $value)
      {
         if ($key) array_push($arr, $value);
      }

      return $arr;
   }
?>
