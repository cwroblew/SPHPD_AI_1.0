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
        // Both deny and allow from list can contain
        // fully qualified IP address or network addresses.

        // check if the current IP is explicitly denied
        $denied = $this->inList($this->_DENY);

        // check if the current IP is explicitly allowed
        $allowed = $this->inList($this->_ALLOW);
  
        // If explicitly denied and not explicitly allowed
        if ($denied && ! $allowed)
        {
             return FALSE;

        // If explicitly denied but allowed (e.g. a host in a network is allowed)
        } else if ($denied && $allowed && $this->isFullAddrMatched($this->_DENY))
        {
            return FALSE;
        }
 
        // If current IP is not explicitly allowed or denied
        // assume allowed. For extreme securety change
        // this to false, if you want to only allow when explicit
        // allow condition is met by current IP
        return TRUE;
    
     }

     function isFullAddrMatched($list = null)
     {
         $ipArr = explode(',', $list);

         foreach ($ipArr as $ip)
         {
           if(!strcmp($ip, $this->_IP) )
           {
               return TRUE;
           }
         }

         return FALSE;
     } 

     function inList($list = null)
     {
        if (!empty($list))
        { 
            $ipArr = explode(',', $list);

            foreach ($ipArr as $badIP)
            {
               // If the current IP is a node of the network ($badIP)
               // currently being evaluated then return 1
               //
               // OR, if the current IP is exactly same as the
               // the current bad IP from deny list then return 1
               

               if( ( $this->isNetworkAddr($badIP)  &&  $this->isNodeOf($badIP) ) ||  
                    !strcmp($badIP, $this->_IP) )
               {
               	   return 1;
               }
            }
        }

        // Not in list, return 0;
        return 0;
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
          if (! $networkOctets[$i] || $networkOctets[$i] == $currentOctets[$i])
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
