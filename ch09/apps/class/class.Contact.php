<?php

   class Contact
   {

      function Contact($dbi = null, $cid = null)
      {
         global $CONTACT_CATEGORY_TBL;
         global $CONTACT_INFO_TBL;
         global $CONTACT_KEYWORD_TBL; 
         global $CONTACT_REMINDER_TBL;
         global $CONTACT_MAIL_TBL;

         $this->dbi = $dbi;
                  
         $this->cat_tbl       = $CONTACT_CATEGORY_TBL;
         $this->contact_tbl   = $CONTACT_INFO_TBL;
         $this->keyword_tbl   = $CONTACT_KEYWORD_TBL;
         $this->reminder_tbl  = $CONTACT_REMINDER_TBL;
         $this->mail_tbl      = $CONTACT_MAIL_TBL;
         
         $this->std_fields = array( 
                                     'CONTACT_ID'        =>    'number', 
                                     'CAT_ID'            =>    'number', 
                                     'CONTACT_FIRST'     =>    'text', 
                                     'CONTACT_INITIAL'   =>    'text', 
                                     'CONTACT_LAST'      =>    'text', 
                                     'EMAIL'             =>    'text', 
                                     'PHONE'             =>    'text', 
                                     'FAX'               =>    'text', 
                                     'URL'               =>    'text', 
                                     'COMPANY_NAME'      =>    'text', 
                                     'COMPANY_ADDRESS'   =>    'text', 
                                     'HOME_ADDRESS'      =>    'text', 
                                     'SOURCE'            =>    'text', 
                                     'REFERENCE'         =>    'text',
                                     'FLAG'              =>    'number' 
                        );
                        
         $this->fields = implode(',', array_keys($this->std_fields));
         $this->setContactID($cid);
      }
      
      function loadContactInfo($cid = null)
      {
          $this->setContactID($cid);
          
          $stmt = "SELECT $this->fields FROM $this->contact_tbl "
                 ."WHERE CONTACT_ID = $this->cid";
          //echo $stmt;       
          $result = $this->dbi->query($stmt);
          if ($result->numRows() > 0)
          {
              $row = $result->fetchRow();
              reset($this->std_fields);
              while(list($fieldName, $fieldType) = each($this->std_fields))
              {                  
                  if (!strcmp($fieldType, 'text'))
                  {
                      $this->$fieldName = stripslashes($row->$fieldName);
                  }
                  else
                  {
                      $this->$fieldName = $row->$fieldName;
                  }
              }
          }
      }
      
      function addKeywords($cid, $keyword)
      {
      	$keyWords = explode(',' , $keyword);
      	if (!empty($keyWords))
      	{
      	  foreach ($keyWords as $key)
      	  {
       	    $key = $this->dbi->quote(addslashes(trim($key)));
       	    $stmt = "INSERT INTO $this->keyword_tbl(CONTACT_ID, KEYWORD) values($cid, $key)";
       	    //echo $stmt;
       	    $this->dbi->query($stmt);
       	  }  
       	}  
      }
      
      function searchContact($criteria, $keyword_exists)
      {
         if ($keyword_exists)
         {
            $stmt = "SELECT * FROM $this->contact_tbl, $this->keyword_tbl WHERE $criteria AND CONTACT_INFO.CONTACT_ID = CONTACT_KEYWORD.CONTACT_ID ORDER BY CONTACT_FIRST";	
         }
         else
         {
           $stmt =  "SELECT * FROM $this->contact_tbl WHERE $criteria ORDER BY CONTACT_FIRST";	
         }
         
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         while ($row = $result->fetchRow())
         {
            $retArr[] = $row;	
         }
         return $retArr;
      }
      
      function deleteKeywords($cid)
      {
      	 $stmt = "DELETE FROM $this->keyword_tbl WHERE CONTACT_ID = $cid";
       	 $this->dbi->query($stmt);       	  
      }
      
      function modifyKeywords($cid, $keyword)
      {
         $this->deleteKeywords($cid);
         $this->addKeywords($cid, $keyword);	
      }
      
      function getKeywords($cid = null)
      {
      	 $this->setContactID($cid);
      	 $stmt = "SELECT KEYWORD FROM $this->keyword_tbl WHERE CONTACT_ID = $this->cid";
      	 
      	 $result = $this->dbi->query($stmt);
      	 if ($result->numRows() <= 0)
      	 {
      	    return null;	
      	 }
      	 while($row = $result->fetchRow())
      	 {
      	    $retArr[] = stripslashes($row->KEYWORD);
      	 }
      	 return $retArr;
      }
      
      
      
      function setContactID($cid = null)
      {
          if (!empty($cid))
          {
              $this->cid  = $cid;
          }
          else
          {
              $this->cid = isset($this->cid) ? $this->cid : null;	
          }
          return $this->cid;
      }
      
      function getColumnValue($col)
      {
      	  $this->loadContactInfo();
      	  return stripslashes($this->$col);
      	  
      }
      
      
      
      
      function addContact($params = null)
      {
      	 $fieldsArr = $this->std_fields;

      	 $keyValues = array();

      	 while(list($k, $v) = each ($fieldsArr))
      	 {
            if (!strcmp($v, 'text'))
            {
            	$v = $this->dbi->quote(addslashes($params[$k]));

            } else {

                $v = $params[$k];
            }

            $valueList[] = $v;
         }

         $values = implode(',', $valueList);

         $statement = "INSERT INTO $this->contact_tbl ($this->fields) VALUES ( $values )";
         //echo $statement;
         $result = $this->dbi->query($statement);
         
         if ($result != DB_OK)
         {
            return false;	
         }
         
         $stmt = "SELECT CONTACT_ID FROM $this->contact_tbl WHERE FLAG = $params[FLAG]";
         
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows()< 0)
         {
            
            return null;	
         }
         $row = $result->fetchRow();
         return $row->CONTACT_ID;
      }
      
      function modifyContact($params = null)
      {
      	 $fieldsArr = $this->std_fields;

      	 while(list($k, $v) = each ($fieldsArr))
      	 {
            if (!strcmp($v, 'text'))
            {
            	$params[$k] = $this->dbi->quote(addslashes($params[$k]));
            }
         }
         
         $keyValue = null;
         while(list($k, $v) = each($params))
         {
            $keyValue .= empty($keyValue) ? null : ', ';
            $keyValue .= $k.' = '.$v;  	
         }
         
         $stmt = "UPDATE $this->contact_tbl SET $keyValue WHERE CONTACT_ID = $params[CONTACT_ID]";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true :  false;
      }
      
      function deleteContact($cid = null)
      {
         $this->setContactID($cid);
         $stmt = "DELETE FROM $this->contact_tbl WHERE CONTACT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         if ($result == DB_OK)
         {
           $this->deleteKeywordsByContactID($this->cid);
           $this->deleteRemindersByContactID($this->cid);
           return true;
         }
      }
      
      function deleteRemindersByContactID($cid = null)
      {
      	 $this->setContactID($cid);
         $stmt = "DELETE FROM $this->reminder_tbl WHERE CONTACT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false; 
      }
      
      function deleteKeywordsByContactID($cid = null)
      {
         $this->setContactID($cid);
         $stmt = "DELETE FROM $this->keyword_tbl WHERE CONTACT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      function getContactsByCatID($cid = null)
      {
         $curTime = mktime();
         $stmt = "SELECT $this->fields from $this->contact_tbl WHERE CAT_ID = $cid ORDER BY CONTACT_FIRST";
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->CONTACT_ID] = $row; 	
         }
         return $retArr;
      }
      
      function getAllContactesByCatID($cid = null)
      {
         $stmt = "SELECT $this->fields from $this->contact_tbl WHERE CAT_ID = $cid ORDER BY PUBLISH_DATE DESC";
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->CONTACT_ID] = $row; 	
         }
         return $retArr;
      }
      
      
      
      function addReminder($cid, $auth, $about, $date, $msgID)
      {
         $about = $this->dbi->quote(addslashes($about));
         $stmt = "INSERT INTO $this->reminder_tbl(CONTACT_ID, CREATED_BY, REMIND_ABOUT, REMIND_DATE, MOTD_ID) VALUES($cid, $auth, $about, $date, $msgID)";
         $result = $this->dbi->query($stmt);
         
         return ($result == DB_OK) ? true : false;
      }
      
      function getRelatedMOTDs($cid = null)
      {
         $this->setContactID($cid);
         $stmt = "SELECT MOTD_ID FROM $this->reminder_tbl WHERE CONTACT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         while ($row = $result->fetchRow())
         {
            $retArr[] = $row->MOTD_ID;
         }
         return $retArr;
      }
      
      function getReminders($cid = null)
      {
      	 $this->setContactID($cid);
      	 $stmt = "SELECT CREATED_BY, REMIND_ABOUT, REMIND_DATE, MOTD_ID FROM "
      	        ."$this->reminder_tbl WHERE CONTACT_ID = $this->cid";
      	 $result = $this->dbi->query($stmt);
      	 if ($result->numRows() <= 0)
      	 {
      	    return null;	
      	 }
      	 while($row = $result->fetchRow())
      	 {
      	    $retArr[] = $row;	
      	 }
      	 return $retArr;
      }
      
      function storeMail($cid, $cc, $sub, $body, $sendTS, $flag)
      {
         $cc = $this->dbi->quote(addslashes($cc));
         $sub = $this->dbi->quote(addslashes($sub));
         $body = $this->dbi->quote(addslashes($body));
         $stmt = "INSERT INTO $this->mail_tbl(CONTACT_ID, CC_TO, SUBJECT, BODY, SEND_TS, CHECK_FLAG) VALUES($cid, $cc, $sub, $body, $sendTS, $flag)";
         //echo $stmt;
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? TRUE : FALSE;
      }
      
      function replaceCategory($old, $new)
      {
        $stmt = "UPDATE $this->contact_tbl SET CAT_ID = $new WHERE CAT_ID = $old";
        $result = $this->dbi->query($stmt);
        return ($result == DB_OK) ? true : false;
      }
      
      function getMails($cid = null)
      {
        $this->setContactID($cid);
        $stmt = "SELECT MAIL_ID, CONTACT_ID, CC_TO, SUBJECT, BODY, SEND_TS FROM $this->mail_tbl WHERE CONTACT_ID = $this->cid";
        $result = $this->dbi->query($stmt);
      	if ($result->numRows() <= 0)
      	{
      	   return null;	
      	}
      	while($row = $result->fetchRow())
      	{
      	   $retArr[] = $row;	
      	}
      	return $retArr;
      }
      
      function getMailDetails($mid)
      {
      	$stmt = "SELECT MAIL_ID, CONTACT_ID, CC_TO, SUBJECT, BODY, SEND_TS FROM $this->mail_tbl WHERE MAIL_ID = $mid";
        $result = $this->dbi->query($stmt);
      	if ($result->numRows() <= 0)
      	{
      	   return null;	
      	}
      	$row = $result->fetchRow();
      	
      	return $row;
      }
      
}