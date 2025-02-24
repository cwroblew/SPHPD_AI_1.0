<?php


   class Message
   {
      function Message($dbi = null, $msg_id = null)
      {

          global $MESSAGE_TBL, $MSG_TRACK_TBL, $MSG_VIEWER_TBL;

          $this->dbi = $dbi;

          $this->msg_tbl = $MESSAGE_TBL;
          
          $this->msg_track_tbl = $MSG_TRACK_TBL;
          
          $this->msg_view_tbl = $MSG_VIEWER_TBL;

          $this->setMessageID($msg_id);
          
          
          
          $this->fields = array( 'MSG_ID'       => 'number',
                                 'MSG_TITLE'    => 'text',
                                 'MSG_CONTENTS' => 'text',
                                 'MSG_DATE'     => 'number',
                                 'AUTHOR_ID'    => 'number',
                                 'MSG_TYPE'     => 'number',
                                 'FLAG'         => 'number'
                        );
                        
          $this->setMessageID($msg_id);

      }
      
      function loadMessageInfo($msg_id = null)
      {
          
          $this->setMessageID($msg_id);
          
          
          $fieldStr = implode(",", array_keys($this->fields));
          $stmt = "SELECT $fieldStr FROM $this->msg_tbl "
                 ."WHERE MSG_ID = $this->msg_id";
          
          $result = $this->dbi->query($stmt);
          if ($result->numRows() > 0)
          {
              $row = $result->fetchRow();
              reset($this->fields);
              while(list($fieldName, $fieldType) = each($this->fields))
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
      
      function getMessageContents($msg_id = null)
      {
         $this->loadMessageInfo($msg_id);
         return $this->MSG_CONTENTS;
      }
      
      function getMessageTitle($msg_id = null)
      {
         $this->loadMessageInfo($msg_id);
         return $this->MSG_TITLE;
      }

      function getMessagePublishDate($msg_id = null)
      {
         $this->loadMessageInfo($msg_id);
         return $this->MSG_DATE;
      }


      function setMessageID($id = null)
      {
          return $this->msg_id = (!empty($id)) ? $id : (isset($this->msg_id) ? $this->msg_id : NULL);
      }
      
      function getMessages($uid = null, $lastDate = null)
      {
         $fields = implode(',', array_keys($this->fields));
         
         
         $lastDate = empty($lastDate) ? mktime() : $lastDate;
         
         $stmt = "SELECT $fields FROM $this->msg_tbl WHERE MSG_DATE <= $lastDate ORDER BY MSG_TYPE DESC, MSG_DATE DESC";
         //echo '<pre>First '. $stmt.'</pre>';
                 
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            /*echo '<pre>';
            print_r($row);
            echo '</pre>';*/
            $stmt = "SELECT MSG_ID FROM $this->msg_track_tbl "
                   ."WHERE MSG_ID = $row->MSG_ID "
                   ."AND USER_ID = $uid";	
            //echo '<pre>Second '.$stmt.'</pre>';
                   
            $finResult = $this->dbi->query($stmt);
            if ($finResult->numRows() <= 0)
            {
               $retArr[] = $row;	
            }
         }
         return $retArr;
                   	
      }
      
      function updateTrack($uid, $mid)
      {
         $currentTS = mktime();
         $stmt = "INSERT INTO $this->msg_track_tbl(USER_ID, MSG_ID, READ_TS) VALUES($uid, $mid, $currentTS)";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;	
      }
      
      function getAllMessages()
      {
         $fields = implode(',', array_keys($this->fields));
         $stmt = "SELECT $fields FROM $this->msg_tbl ORDER BY MSG_TITLE";
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while($row = $result->fetchRow())
         {
            $retArr[$row->MSG_ID] = $row;	
         }
         return $retArr;
      }
      
      function addMessage($title, $date, $msg, $flag, $auth, $type)
      {
         $fields = implode(',', array_keys($this->fields));
         $title = $this->dbi->quote(addslashes($title));
         $msg = $this->dbi->quote(addslashes($msg));
         $stmt = "INSERT INTO $this->msg_tbl($fields) VALUES(null, $title, $msg, $date, $auth, $type, $flag)";
         //echo $stmt;

         $result = $this->dbi->query($stmt);
         
         if ($result != DB_OK)
         {
            return false;
         }
         $stmt = "SELECT MSG_ID FROM $this->msg_tbl WHERE FLAG = $flag";
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $row = $result->fetchRow();
         return $row->MSG_ID;
         
      }
      
      function deleteMessage($mid)
      {
         $stmt = "DELETE FROM $this->msg_tbl WHERE MSG_ID = $mid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      function modifyMessage($mid, $title, $date, $msg, $flag)
      {
         $title = $this->dbi->quote(addslashes($title));
         $msg = $this->dbi->quote(addslashes($msg));
         $stmt = "UPDATE $this->msg_tbl SET MSG_TITLE = $title, MSG_CONTENTS = $msg, MSG_DATE = $date WHERE MSG_ID = $this->msg_id";
         //echo $stmt;
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
         
      }
      
      function isRead($mid = null)
      {
         $this->setMessageID($mid);
         $stmt = "SELECT MSG_ID FROM $this->msg_track_tbl WHERE MSG_ID = $this->msg_id";	
         $result = $this->dbi->query($stmt);         
         return $result->numRows();
      }
      
      function getViewers($mid = null)
      {
         
         $this->setMessageID($mid);
         $stmt = "SELECT VIEWER_ID FROM $this->msg_view_tbl WHERE MSG_ID = $this->msg_id";	
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() < 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[] = $row->VIEWER_ID;
         }
         return $retArr;
      }
      
      function addViewer($mid, $views)
      {
         $this->setMessageID($mid);
         
         foreach($views as $view_id)
         {
           $stmt = "INSERT INTO $this->msg_view_tbl(MSG_ID, VIEWER_ID) VALUES($this->msg_id, $view_id)";           
           //echo $stmt;
           $result = $this->dbi->query($stmt);	
         }
 	
      }
      
      function deleteViewers($mid = null)
      {
      	$this->setMessageID($mid);
        $stmt = "DELETE FROM $this->msg_view_tbl WHERE MSG_ID = $this->msg_id";
        $result = $this->dbi->query($stmt);
      }
      
      function isViewable($mid = null, $vid)
      {
        $this->setMessageID($mid);
        $stmt = "SELECT VIEWER_ID FROM $this->msg_view_tbl WHERE MSG_ID = $this->msg_id AND VIEWER_ID = $vid OR MSG_ID = $this->msg_id AND VIEWER_ID = 0";
        //echo $stmt;
        $result = $this->dbi->query($stmt);
        return $result->numRows();
      }
      
      function getMsgIDbyMessageTitle($title)
      {
        $title = $this->dbi->quote(addslashes($title));
        $stmt = "SELECT MSG_ID FROM $this->msg_tbl WHERE MSG_TITLE = $title";
        //echo $stmt;
        $result = $this->dbi->query($stmt);
        if ($result->numRows() <= 0)
        {
           return null;	
        }
        $row = $result->fetchRow();
        return $row->MSG_ID;
      }

   }


?>
