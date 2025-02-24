<?php

   class ACL {

     function ACL($params = null)
     {
         $this->_IP    = $params['current_ip'];
         $this->_ALLOW = $params['allow_from'];
         $this->_DENY  = $params['deny_from'];
         
     }

     function isAllowed()
     {
        // check if the current IP is denied.
        // Both deny and allow from list can contain
        // fully qualified IP address or network addresses.
      
        if (strlen($this->_DENY) > 0)
        { 
            $denied = explode(',', $this->_DENY);

            foreach ($denied as $badIP)
            {
               // If the current IP is a node of the network ($badIP)
               // currently being evaluated then return FALSE
               //
               // OR, if the current IP is exactly same as the
               // the current bad IP from deny list then return FALSE
               
               if( ( $this->isNetworkAddr($badIP)  &&  $this->isNodeOf($badIP) )   ||  !strcmp($badIP, $this->_IP) )
               {
               	   return FALSE;
               }
            }
        }

        return TRUE;
     }

     function isDenied()
     {
     	return ($this->isAllowed()) ? FALSE : TRUE;
     }

     function isNodeOf($net = null)
     {
        // See if current IP is a node of the given network

        $currentOctets = explode('.', $this->_IP);
        $networkOctets = explode('.', $net);

        // Remove the last octet from the network address if octet
        // count is 4 (e.g 192.168.1.0 becomes 192.168.1 since .0 or .x
        // are DONT CARE octets
        if (count($networkOctets) == 4)
        {
            array_pop($networkOctets);
        }

        // Always remove the last octet from current IP to match network
        array_pop($currentOctets);

        // Now we have a 3, 2, or 1 octet network address to match
        // with given IP minus the last octet

        $matchCount = 0;
        for($i=0; $i<3;$i++)
        {
          if (!empty($networkOctets[$i]) && !empty($currentOctets[$i]) && $networkOctets[$i] == $currentOctets[$i])
          {
              $matchCount++ ;
          }
        }
        // If number of matches equals number of octets in network addr
        // then current IP *is* a node of the given network.
        return ($matchCount == count($networkOctets)) ? TRUE : FALSE;
     }


     function isNetworkAddr($ip = null)
     {
     	$octets = explode('.', $ip);

     	// If there are less than 4 octets in the given IP address
     	// it is a network address (e.g. 192.168)
     	// OR
        // If there are 4 octets but the last octet is .0 or .x
        // then it is a network address
        //
        // Otherwise, it is not a network address
        $len = count($octets);
     	return ( ($len < 4) ||
     	         ($octets[$len-1] == 0 || $octets[$len-1] == 'x')) ? TRUE : FALSE;

     }
   }//class

?>
