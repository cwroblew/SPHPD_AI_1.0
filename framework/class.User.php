<?php

   class User
   {
      function User($dbi = null, $uid = null)
      {

         global $AUTH_DB_TBL,
                $MIN_USERNAME_SIZE,
                $MIN_PASSWORD_SIZE,
                $ACTIVITY_LOG_TBL;

         
         $this->user_tbl          = $AUTH_DB_TBL;
         $this->user_activity_log = $ACTIVITY_LOG_TBL;
         $this->dbi               = $dbi;
		 
         $this->minmum_username_size = $MIN_USERNAME_SIZE;
         $this->minmum_pasword_size  = $MIN_PASSWORD_SIZE;

         $this->USER_ID  = ($uid != null) ? $uid : null;

         //$this->debugger = $debugger;

         $this->user_tbl_fields = array('USERNAME'    => 'text',
		 								'EMAIL'    => 'text',
                                        'PASSWORD' => 'text',
                                        'TYPE'     => 'number',
                                        'ACTIVE'   => 'number'
                                        );
//echo $this->USER_ID;
        if (isset($this->USER_ID))
        {
            $this->is_user = $this->getUserInfo();
        } else {
            $this->is_user = FALSE;
        }
      }

      function isUser()
      {
          return $this->is_user;
      }

      function getUserID()
      {
         return $this->USER_ID;
      }

      function setUserID($uid = null)
      {
         if (! empty($uid))
         {
            $this->USER_ID = $uid;
         }

         return $this->USER_ID;
      }

      function getUserIDByName($name = null)
      {

         if (! $name ) return null;

         $stmt   = "SELECT USER_ID FROM $this->user_tbl WHERE EMAIL = '$name'";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->USER_ID;
         }

         return null;

      }

      function getUserTypeList()
      {
         global $USER_TYPE;
         
         return $USER_TYPE;

      }

      function getUID()
      {
         return (isset($this->USER_ID)) ? $this->USER_ID : NULL;
      }

      function getEMAIL()
      {
         return (isset($this->EMAIL)) ?  $this->EMAIL : NULL;
      }

      function getUSERNAME()
      {
         return (isset($this->USERNAME)) ?  $this->USERNAME : NULL;
      }

      function getPASSWORD()
      {
         return (isset($this->PASSWORD)) ? $this->PASSWORD : NULL;
      }

      function getACTIVE()
      {
         return (isset($this->ACTIVE)) ? $this->ACTIVE : NULL;
      }

      function getTYPE()
      {  
         return (isset($this->TYPE)) ? $this->TYPE : NULL;
      }

      function getUserFieldList()
      {
         return array('USER_ID', 'EMAIL', 'USERNAME', 'PASSWORD', 'ACTIVE', 'TYPE');
      }

      function getUserInfo($uid = null)
      {
         $fields   = $this->getUserFieldList();

         $fieldStr = implode(',', $fields);

         $this->setUserID($uid);

		 if ($this->USER_ID == null)
		 {
		 	return FALSE;
		 }
         $stmt   = "SELECT $fieldStr FROM $this->user_tbl " .
                   "WHERE USER_ID = $this->USER_ID";

         //echo "$stmt <P>"; 

         $result = $this->dbi->query($stmt);
         

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             foreach($fields as $f)
             {
//echo "<p>row: $f: " .  $row->$f;
               $this->$f  = $row->$f;
             }
             
             return TRUE;
             
         }

         return FALSE;
      }



      function getUserIDbyEmail($email = null)            // needed for EIS
      {
         $stmt   = "SELECT id FROM $this->user_tbl " .
                   "WHERE EMAIL = '$email'";

         $result = $this->dbi->query($stmt);
         
         if($result->numRows() > 0)
         {
            $row = $result->fetchRow();
            
            return $row->USER_ID;
            
         } else {
          
            return 0;
         }
      }



      function getUserList()
      {

         $stmt   = "SELECT USER_ID, EMAIL FROM $this->user_tbl";


         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->USER_ID] = $row->EMAIL;
             }
         }

         return $retArray;

      }

      function makeUpdateKeyValuePairs($fields = null, $data = null)
      {
          $setValues = array();

          while(list($k, $v) = each($fields))
          {

             if (isset($data[$k]))
             {
                  echo "DATA $k = $data[$k] <br>";

                  if (! strcmp($v, 'text'))
                  {
                     $v = $this->dbi->quote(addslashes($data[$k]));

                     $setValues[] = "$k = $v";

                  } else {

                     $setValues[] = "$k = '$data[$k]'";
                  }
             }
          }

          return implode(', ', $setValues);
      }


      function updateUser($data = null)
      {

          $this->setUserID();

          $fieldList = $this->user_tbl_fields;

          $keyVal = $this->makeUpdateKeyValuePairs($this->user_tbl_fields, $data);

          $stmt = "UPDATE $this->user_tbl SET $keyVal WHERE USER_ID = $this->USER_ID";
echo $stmt . "<p>";
          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);

      }

      function addUser($data = null)
      {

          $fieldList = $this->user_tbl_fields;
          $valueList = array();

          while(list($k, $v) = each($fieldList))
          {
             if (!strcmp($v, 'text'))
             {
                $valueList[] = $this->dbi->quote(addslashes($data[$k]));
             } else {
                $valueList[] = $data[$k];
             }
          }

          $fields = implode(',', array_keys($fieldList));
          $values = implode(',', $valueList);

          $stmt   = "INSERT INTO $this->user_tbl ($fields) VALUES($values)";
          echo $stmt;
          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);

      }

      function deleteUser($uid = null)
      {

         $this->setUserID($uid);


         $stmt = "DELETE from $this->user_tbl " .
                 "WHERE USER_ID = $this->USER_ID";

         $result = $this->dbi->query($stmt);

         return $this->getReturnValue($result);
      }


      function getReturnValue($r = null)
      {
          return ($r == DB_OK) ? TRUE : FALSE;

      }

     function logActivity($action = null)
     {

     	$now = time();

     	$stmt = "INSERT INTO  $this->user_activity_log SET " .
     	        "USER_ID     = $this->USER_ID, ".
     	        "ACTION_TYPE = $action, " .
     	        "ACTION_TS = $now";

        // echo "$stmt <P>";

        $result = $this->dbi->query($stmt);

        return $this->getReturnValue($result);
     }
   }
?>
