<?php


/*
* CVS ID: $Id$
*/

   class Calendar 
   {

      function Calendar()
      {
      }

      function getMonths()
      {
         return array(
                     '1' => 'January',
                     '2' => 'February',
                     '3' => 'March',
                     '4' => 'April',
                     '5' => 'May',
                     '6' => 'June',
                     '7' => 'July',
                     '8' => 'August',
                     '9' => 'September',
                     '10' => 'October',
                     '11' => 'November',
                     '12' => 'December'
                     
                     );
      }
      
      function getDays()
      {
      	 for ($i=1;$i<=31;$i++) 
         {
      	    $daysarr[$i]=$i;
         }

      	 return $daysarr;	
      }
      
      function getYears()
      {
         return array(
                     '2002' => '2002',
                     '2003' => '2003',
                     '2004' => '2004',
                     '2005' => '2005',
                     '2006' => '2006',
                     '2007' => '2007',
                     '2008' => '2008',
                     '2009' => '2009',
                     '2010' => '2010',
                     '2011' => '2011'
                     );
      		
      }
      
   
      function getMonth($givenName = null)
      {

         $givenName = trim(ucwords($givenName));

         $months = $this->getMonths();
         while (list($id, $name) = each ($months))
         {
            if (strcmp($name, $givenName))
            {
               return $id;
            }
         }

         return 0;
      }
   }

?>
