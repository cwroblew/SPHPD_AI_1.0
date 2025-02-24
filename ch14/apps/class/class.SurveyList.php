<?php

   class SurveyList
   {
      function SurveyList($dbi = null, $lid = null)
      {
          global $SURVEY_LIST_TBL, $SURVEY_LIST_DATA_TBL;

          $this->list_tbl      = $SURVEY_LIST_TBL;
          $this->list_data_tbl = $SURVEY_LIST_DATA_TBL;

          $this->dbi = $dbi;

          $this->setSurveyListID($lid);

      }

      function setSurveyListID($lid = null)
      {
          if (!empty($lid))
          {
              $this->lid  = $lid;
          }

          return isset($this->lid) ? $this->lid : NULL;
      }


      function setReturnValue($value)
      {
         if(empty($value))
         {
            return null;

         } else {
            return $value;
         }
      }

      function addNewSurveyList($fileName,
                                $SurveyListName,
                                $uid,
                                $userfile,
                                $today,
                                $filtername,
                                $filteremail)
      {

         $checkflag  = $uid+$today;

         $stmt       = "INSERT INTO  $this->list_tbl " .
                       "VALUES('','$SurveyListName',
                                  '$userfile',
                                   0 ,
                                   $today,
                                   $uid,
                                   $checkflag)";

         $result = $this->dbi->query($stmt);

         if ($result != DB_OK) return false;

         $stmt = "SELECT LIST_ID FROM $this->list_tbl ".
                       "WHERE NAME='$SurveyListName'";

         $result    = $this->dbi->query($stmt);

         $row       = $result->fetchRow();

         $lid       = $row->LIST_ID;

         $fcontents = file ($fileName);

         $counter = 0;

         while (list ($line_num, $line) = each ($fcontents))
         {
            list($email, $fname, $lname) = explode(",",$line);

            if ($filtername == 1)
            {
                $fname = ucwords(strtolower($fname));

                $lname = ucwords(strtolower($lname));
            }

            if ($filteremail == 1)
            {

                $email = strtolower($email);
            }

            $statement = "INSERT INTO $this->list_data_tbl ".
                         "VALUES($lid, '', '$email', '$fname', '$lname')";

            $result = $this->dbi->query($statement);

            if ($result != DB_OK)
            {
                $error++;
            } else {
                $counter++;
            }
         }

         $statement = "UPDATE $this->list_tbl SET RECORDS=$counter ".
                      "WHERE LIST_ID = $lid";

         $result = $this->dbi->query($statement);

         if ($result != DB_OK) return false;

         return true;

      }

      function getTotalRecordCount($lid = null)
      {
         $this->setSurveyListID($lid);

         $stmt = "SELECT COUNT(SUID) as TOTAL from $this->list_data_tbl " .
                 "WHERE LIST_ID = $this->lid";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();
             return $row->TOTAL;
         }

         return 0;

      }

      function getAvailableLists()
      {

         $listArr = array();

         $statement = "SELECT LIST_ID, NAME FROM $this->list_tbl";

         $result =$this->dbi->query($statement);

         while($row = $result->fetchRow())
         {

            $listArr[$row->LIST_ID]     = $row->NAME;
         }
         return $listArr;
      }

      function deleteList($lid = null)
      {
          $this->setSurveyListID($lid);

          $stmt   = "DELETE from $this->list_tbl " .
                    "WHERE LIST_ID  = $this->lid";

          $result = $this->dbi->query($stmt);

          if ($result == DB_OK)
          {
              return TRUE;
          }
          return FALSE;
      }

      function getTargetData($lastRow = null, $deliverySize = null)
      {
          $this->setSurveyListID();

          if (empty($lastRow))
          {
             $lastRow = 0;
          }

          $stmt = "SELECT SUID, EMAIL, FIRST, LAST FROM $this->list_data_tbl " .
                  "WHERE LIST_ID = $this->lid AND SUID > $lastRow " .
                  "ORDER BY SUID LIMIT $deliverySize";

          $result = $this->dbi->query($stmt);

          $retArray = array();

          if ($result != null )
          {
             while($row = $result->fetchRow())
             {
                 $retArray[$row->SUID] = $row;
             }
          }

          return $retArray;
      }
   }

?>
