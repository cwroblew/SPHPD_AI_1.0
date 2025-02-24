<?php

   class Event
   {

      function Event($dbi = null, $eid = null)
      {
         global $CALENDAR_EVENT_TBL;         
         

         $this->dbi = $dbi;
                  
         $this->event_tbl = CALENDAR_EVENT_TBL;
         
         $this->event_view_tbl = CALENDAR_EVENT_VIEW_TBL;
         
         $this->event_repeat_tbl = CALENDAR_EVENT_REPEAT_TBL;
         
         $this->std_fields = array( 
                                     'EVENT_ID'          =>   'number',
                                     'USER_ID'           =>   'number',
                                     'EVENT_TITLE'       =>   'text',
                                     'EVENT_DATE'        =>   'text',
                                     'EVENT_DESC'        =>   'text',
                                     'REMINDER_ID'       =>   'number',
                                     'FLAG'              =>   'number'
                                  );         

         $this->fields = implode(',', array_keys($this->std_fields));

         $this->setEventID($eid);
      }
      
      function loadEventInfo($eid = null)
      {
          $this->setEventID($eid);
          
          $stmt = "SELECT $this->fields FROM $this->event_tbl "
                 ."WHERE EVENT_ID = $this->eid";
                 
                    
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
      
      function setEventID($eid = null)
      {
          if (!empty($eid))
          {
              $this->eid  = $eid;
          }
          else
          {
              $this->eid = isset($this->eid) ? 	$this->eid : NULL;
          }
          
          return $this->eid;
      }
      
      function getEventTitle($eid = null)
      {
      	  $this->loadEventInfo($eid);
      	  return $this->EVENT_TITLE;
      }
      
      function getEventDate($eid = null)
      {
      	  $this->loadEventInfo($eid);
      	  return $this->EVENT_DATE;
      }
      
            
      function getEventDesc($eid = null)
      {
      	  $this->loadEventInfo($eid);
      	  return $this->EVENT_DESC;
      }
      
      function getEventReminder($eid = null)
      {
      	  $this->loadEventInfo($eid);
      	  return $this->REMINDER_ID;
      }
      
      
      function addEvent($params = null)
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

         $statement = "INSERT INTO $this->event_tbl ($this->fields) VALUES ( $values )";
         
         $result = $this->dbi->query($statement);         
         
         if ($result == DB_OK)
         {
            $stmt = "SELECT EVENT_ID FROM $this->event_tbl WHERE FLAG = $params[FLAG]";
            $result = $this->dbi->query($stmt);
            if ($result->numRows() <= 0)
            {
               return false;
            }
            $row = $result->fetchRow();
            return $row->EVENT_ID;
         }
         else
         {
            return false;	
         }
      }
      
      function deleteEvent($eid = null)
      {
         $this->setEventID($eid);
         $stmt = "DELETE FROM $this->event_tbl WHERE EVENT_ID = $this->eid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      
      //CHECK THIS
      function modifyEvent($params = null)
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
         
         $stmt = "UPDATE $this->event_tbl SET $keyValue WHERE EVENT_ID = $params[EVENT_ID]";
         //echo $stmt;
         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true :  false;
      }
      
      function getEvents($uid, $date)
      {
      	 list($m, $d, $y) = explode("-", $date);
      	 $date = $this->dbi->quote(addslashes($date));
      	 
      	 $dateTime = mktime(0, 0, 0, $m, $d, $y);
      	 
      	 $weekRepeat = $this->dbi->quote(addslashes(date("D", $dateTime)));
      	 $monthRepeat = $this->dbi->quote(addslashes(date("d", $dateTime)));
      	 $yearRepeat = $this->dbi->quote(addslashes(date("m-d", $dateTime)));
      	 
      	 $stmt = "SELECT $this->event_tbl.EVENT_ID, $this->event_tbl.EVENT_TITLE "
      	        ."FROM $this->event_tbl JOIN $this->event_view_tbl JOIN $this->event_repeat_tbl "
      	        ."WHERE "
      	        ."($this->event_tbl.EVENT_DATE = $date AND ($this->event_view_tbl.VIEWER_ID = $uid OR $this->event_view_tbl.VIEWER_ID = 0) AND $this->event_tbl.EVENT_ID = $this->event_view_tbl.EVENT_ID) "
      	        ."OR (($this->event_repeat_tbl.REPEAT_MODE = $weekRepeat OR $this->event_repeat_tbl.REPEAT_MODE = $monthRepeat OR $this->event_repeat_tbl.REPEAT_MODE = $yearRepeat) "
      	        ."AND ($this->event_view_tbl.VIEWER_ID = $uid OR $this->event_view_tbl.VIEWER_ID = 0) "
      	        ."AND $this->event_repeat_tbl.EVENT_ID = $this->event_view_tbl.EVENT_ID AND $this->event_tbl.EVENT_ID = $this->event_repeat_tbl.EVENT_ID)";
      	        
      	 //echo "<p><font size=1>$stmt</font></p>";
      	 
      	 $result = $this->dbi->query($stmt);
      	 if ($result->numRows() <= 0)
      	 {
      	    return null;	
      	 }
      	 $retArr = array();
      	 while ($row = $result->fetchRow())
      	 {
      	    $retArr[$row->EVENT_ID] = $row->EVENT_TITLE;
      	 }
      	 return $retArr;
      }
      
      function getOwnEvents($uid, $date)
      {
      	 $date = $this->dbi->quote(addslashes($date));
      	 $stmt = "SELECT $this->event_tbl.EVENT_ID ,$this->event_tbl.EVENT_TITLE "
      	        ."FROM $this->event_tbl "
      	        ."WHERE $this->event_tbl.USER_ID = $uid "
      	        ."AND EVENT_DATE = $date";
      	 //echo $stmt;
      	 
      	 $result = $this->dbi->query($stmt);
      	 if ($result->numRows() <= 0)
      	 {
      	    return null;	
      	 }
      	 $retArr = array();
      	 while ($row = $result->fetchRow())
      	 {
      	    $retArr[$row->EVENT_ID] = $row->EVENT_TITLE;
      	 }
      	 
      	 return $retArr;
      }
      
      function addViewer($eid = null, $views)
      {
         $this->setEventID($eid);
         
         if (in_array(0, $views))
         {
           $views = array(0);	
         }
         
         foreach($views as $view_id)
         {
           if ($view_id >= 0)
           {   
              $stmt = "INSERT INTO $this->event_view_tbl(EVENT_ID, VIEWER_ID) VALUES($this->eid, $view_id)";           
              $result = $this->dbi->query($stmt);	
           }   
         }
      }
      
      function deleteViewers($eid = null)
      {
      	 $this->setEventID($eid);
      	 
      	 $stmt = "DELETE FROM $this->event_view_tbl WHERE EVENT_ID = $this->eid";
      	 
      	 //echo $stmt;
      	 
      	 $result = $this->dbi->query($stmt);
      	 return ($result == DB_OK) ? true : false;
      	 
      }
      
      function getViewers($eid = null)
      {
         $this->setEventID($eid);
         $stmt = "SELECT VIEWER_ID FROM $this->event_view_tbl WHERE EVENT_ID = $this->eid";
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         while ($row = $result->fetchRow())
         {
            $retArr[] = $row->VIEWER_ID;	
         }
         return $retArr;
      }
      
      function addRepeatMode($eid = null, $mode)
      {
         $this->setEventID($eid);
         $mode = $this->dbi->quote(addslashes($mode));
         $stmt = "INSERT INTO $this->event_repeat_tbl(EVENT_ID, REPEAT_MODE) VALUES($this->eid, $mode)";
         $result = $this->dbi->query($stmt);
         
         return ($result == DB_OK) ? true : false;
      }
      
      function deleteRepeatMode($eid = null)
      {
      	 $this->setEventID($eid);
      	 $stmt = "DELETE FROM $this->event_repeat_tbl WHERE EVENT_ID = $this->eid";
      	 
      	 $result = $this->dbi->query($stmt);
      	 return ($result == DB_OK) ? true : false;
      	
      }
      
      function getRepeatMode($eid = null)
      {
         $this->setEventID($eid);
         $stmt = "SELECT REPEAT_MODE FROM $this->event_repeat_tbl WHERE EVENT_ID = $this->eid";         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         $row  = $result->fetchRow();
         
         return $row->REPEAT_MODE; 
         	
      }
      
  }

?>
