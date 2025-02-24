<?php

    class AccessControl
    {
        
        //Constructor takes the DBI object and an array as parameter. 
        function AccessControl($dbi, $ACInfo)
        {
            $this->dbi           = $dbi;
            
            //This specifies the target for the access (a message or a form)
            $this->access_obj    = isset($ACInfo['ACCESS_OBJ']) ? $ACInfo['ACCESS_OBJ'] : NULL;
            $this->allow_tbl     = isset($ACInfo['ALLOW_TBL']) ? $ACInfo['ALLOW_TBL'] : NULL;	
            $this->deny_tbl      = isset($ACInfo['DENY_TBL']) ? $ACInfo['DENY_TBL'] : NULL;
            
            //This takes the form/message ID.
            if (isset($ACInfo['AC_OBJ_ID'])) $this->setAccessObjectID($ACInfo['AC_OBJ_ID']);
            if (isset($ACInfo['REQUEST_IP'])) $this->setCurrentIP($ACInfo['REQUEST_IP']);
        }
        
        function setCurrentIP($ip)
        {
           if (!empty($ip))
           {
              $this->request_ip = $ip; 	
           }
           return empty($this->reuqest_ip) ? NULL : $this->reuqest_ip;
        }
        
        function setAccessObjectID($id)
        {
           if (!empty($id))
           {
              $this->access_obj_id = $id;	
           }	
           return $this->access_obj_id;
        }
        
        function isAccessAllowed()
        {
           $ipArr = $this->getAccessIPs();
           

           // if explicit allow list exists then see
           // if current IP is allowed access or not
           if (!empty($ipArr))
           {           
             foreach($ipArr as $ip)
             {
                if (preg_match("/^".trim($ip)."/", $this->request_ip))
                {
                   return true;	
                }
             }
           } else {
             // not explicitly allowed so implicitly allowed
             return true;
           }

           // Current IP is not in allowed list so return false
           return false;
        }
        
        function isAccessDenied()
        {
           $ipArr = $this->getDeniedIPs();
           

           // if explicit deny list exists then
           // check if the current IP is denied or not
           if (!empty($ipArr))
           {           
             foreach($ipArr as $ip)
             {
                if (preg_match("/^".trim($ip)."/", $this->request_ip))
                {
                   return true;	
                }
             }
           } 

           // current IP is not explicitly denied so return false
           return false;	
        }
        
        function getAccessIPs()
        {
           $stmt = "SELECT OWNER_IP FROM $this->allow_tbl WHERE ".$this->access_obj."_ID = $this->access_obj_id";                      
           $result = $this->dbi->query($stmt);           

           if (empty($result) || $result->numRows() <= 0)
           {                        
              return null;  
           }
           while ($row = $result->fetchRow())
           {
              $retArr[] = stripslashes($row->OWNER_IP);
           }
           return $retArr;           
        }
        
        function getDeniedIPs()
        {
           $stmt = "SELECT BANNED_IP FROM $this->deny_tbl WHERE ".$this->access_obj."_ID = $this->access_obj_id";
           
           $result = $this->dbi->query($stmt);           
           if (empty($result) || $result->numRows() <= 0)
           {                        
              return null;  
           }
           while ($row = $result->fetchRow())
           {
              $retArr[] = trim(stripslashes($row->BANNED_IP));
           }
           return $retArr;	
        }
        
        function addAccessIPs($IPArr)
        {
           foreach($IPArr as $IP)
           {
              $IP = trim($IP);

              if (strlen($IP) <=0)
              {
                 continue;
              }

              $IP = $this->dbi->quote(addslashes($IP));

              $stmt = "INSERT INTO $this->allow_tbl VALUES($this->access_obj_id, $IP)";
           	
              $result = $this->dbi->query($stmt);
           }	
        }
        
        function deleteAccessIP($objID = null)
        {
           $this->setAccessObjectID($objID);
           $stmt = "DELETE FROM $this->allow_tbl WHERE ".$this->access_obj."_ID = $this->access_obj_id";
           $result = $this->dbi->query($stmt);
           return ($result == DB_OK) ? TRUE : FALSE;
        }
        
        function addDeniedIPs($IPArr)
        {
           foreach($IPArr as $IP)
           {
               $IP = trim($IP);

               if (strlen($IP) <=0)
               {
                   continue;
               }

              $IP = $this->dbi->quote(addslashes($IP));
              $stmt = "INSERT INTO $this->deny_tbl VALUES($this->access_obj_id, $IP)";
           	
              $result = $this->dbi->query($stmt);
           }	
        }
        
        function deleteDeniedIP($objID = null)
        {
           $this->setAccessObjectID($objID);
           $stmt = "DELETE FROM $this->deny_tbl WHERE ".$this->access_obj."_ID = $this->access_obj_id";
           $result = $this->dbi->query($stmt);	
           return ($result == DB_OK) ? TRUE : FALSE;
        }
    }

?>
