<?php

    class Message
    {
        
        //Constructor takes the DBI object and the message ID as the parameter
        function Message($dbi = null, $mid = null)
        {
           $this->dbi = $dbi;           
           $this->msg_tbl = TAF_MSG_TBL;            
           $this->field_arr = array
                                   (
                                    'MSG_ID'     =>   'number',
                                    'MSG_NAME'   =>   'text',
                                    'BODY'       =>   'text',
                                    'MSG_FROM'   =>   'text',
                                    'REPLY_TO'   =>   'text',                                   
                                    'SUBJECT'    =>   'text'
                                   );
           $this->fields = implode(',', array_keys($this->field_arr));
           $this->setMessageID($mid);
        }
        
        function setMessageID($mid)
        {
           if (!empty($mid))
           {
              $this->mid = $mid;	
           }	
           return empty($this->mid) ? NULL : $this->mid;
        }
        
        function getMessageInfo($mid = null)
        {        	
           $this->setMessageID($mid);
           $stmt = "SELECT $this->fields FROM $this->msg_tbl WHERE MSG_ID = $this->mid";
           
           $result = $this->dbi->query($stmt);
           if ($result->numRows() <= 0)
           {
              return null;
           }
           $row = $result->fetchRow();
           reset($this->field_arr);
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {              
              $retArr[$fieldName] = stripslashes($row->$fieldName);	                
           }
           
           return $retArr;
        }
        
        function getAllMessages()
        {
           $stmt = "SELECT MSG_ID, MSG_NAME FROM $this->msg_tbl";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           while ($row = $result->fetchRow())
           {
              $retArr[$row->MSG_ID] = $row->MSG_NAME;
           }
           return $retArr;           
        }
        
        function addMessage($params)
        {
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }
           
           $paramValueStr = implode(', ', $params);
           $stmt = "INSERT INTO $this->msg_tbl($this->fields) VALUES($paramValueStr)";
           
           
           $result = $this->dbi->query($stmt);
           if ($result != DB_OK)
           {
              return false;	
           }
           
           $stmt = "SELECT MSG_ID FROM $this->msg_tbl WHERE MSG_NAME = $params[MSG_NAME]";
           $result= $this->dbi->query($stmt);
           if ($result->numRows() <= 0)
           {
              return false;	
           }
           $row = $result->fetchRow();
           return $row->MSG_ID;
        }
        
        function modifyMessage($params)
        {
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }          
           
           $keyValue = null;
           while(list($k, $v) = each($params))
           {
            $keyValue .= empty($keyValue) ? null : ', ';
            $keyValue .= $k.' = '.$v;  	
           }
         
           $stmt = "UPDATE $this->msg_tbl SET $keyValue WHERE MSG_ID = $params[MSG_ID]";
           
           
           $result = $this->dbi->query($stmt);
           return ($result == DB_OK) ? true : false;	
        }
        
        function deleteMessage($mid = null)
        {
          $this->setMessageID($mid);
          $stmt = "DELETE FROM $this->msg_tbl WHERE MSG_ID = $this->mid";
          $result = $this->dbi->query($stmt);
          return ($result == DB_OK) ? true : false; 	
        }
    }

?>
