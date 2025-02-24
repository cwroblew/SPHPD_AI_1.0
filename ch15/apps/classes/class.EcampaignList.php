<?php

   class EcampaignList
   {
      function EcampaignList($dbi = null, $lid = null)
      {
          global $ECAMPAIGN_LIST_TBL,
                 $LIST_FIELD_MAP_TBL,
                 $ECAMPAIGN_ASSEMBLY_TBL,
                 $ECAMPAIGN_BOUNCED_TBL,
                 $ECAMPAIGN_UNSUB_TBL ;

          $this->list_tbl       = $ECAMPAIGN_LIST_TBL;
          $this->list_field_map = $LIST_FIELD_MAP_TBL;
          $this->assembly_tbl   = $ECAMPAIGN_ASSEMBLY_TBL;
          $this->unsub_tbl      = $ECAMPAIGN_UNSUB_TBL;
          $this->bounced_tbl    = $ECAMPAIGN_BOUNCED_TBL;

          $this->dbi = $dbi;

          
          $this->std_map_fields = array(

                     'LIST_ID' => 'number',
                          'REC_ID'  => 'text',
                          'FIRST'   => 'text',
                          'LAST'    => 'text',
                          'EMAIL'   => 'text',
                          'AGE'     =>  'text',
                          'INCOME'  =>  'text',
                          'SEX'     => 'text'
         );


          $this->setEcampaignListID($lid);

      }

      function setEcampaignListID($lid = null)
      {
          if (!empty($lid))
          {
              $this->lid  = $lid;
          }
          return isset($this->lid) ? $this->lid : NULL;
      }

      function getEcampaignListInfo($lid = null)
      {

        $statement = "SELECT * FROM $this->list_tbl WHERE LIST_ID = '$lid'";

        $result = $this->dbi->query($statement);

        return ($result != null) ? $result->fetchRow() : null;
      }


      function addNewEcampaignList($listname,
                                   $db_host,
                                   $db_user,
                                   $db_pass,
                                   $db_type,
                                   $db_name,
                                   $db_table,
                                   $today,
                                   $uid)
      {
         $fieldArr = array('NAME', 'DB_HOST', 'DB_USER', 'DB_PASSWD', 'DB_TYPE', 'DB_NAME', 'DB_TABLE', 'CREATE_TS', 'CREATOR_ID', 'CHECK_FLAG');
         $fields = implode(",", $fieldArr);
         $checkFlag = $uid . $today;
         $db_pass = base64_encode($db_pass);
         $statement = "INSERT INTO  $this->list_tbl($fields) " .
                      "VALUES('$listname', '$db_host', '$db_user', '$db_pass', '$db_type', '$db_name', '$db_table', $today, $uid, $checkFlag)";



         $result = $this->dbi->query($statement);

         //echo "$statement <P>";

         if ($result != DB_OK) {

             return false;
         }
         else
         {
            $statement = "SELECT LIST_ID FROM $this->list_tbl WHERE NAME = '$listname'";
            $result = $this->dbi->query($statement);
            $row = $result->fetchRow();
            return $row->LIST_ID;
         }
      }

      function modEcampaignList($list_id,
                                $name,
                                $db_name,
                                $db_host,
                                $db_user,
                                $db_pass,
                                $db_type,
                                $db_table)
      {
        $db_pass = base64_encode($db_pass);
        $statement = "UPDATE $this->list_tbl SET NAME = '$name', DB_HOST='$db_host',".
                          "DB_NAME = '$db_name', DB_USER = '$db_user', DB_PASSWD = '$db_pass',".
                          "DB_TYPE = '$db_type', DB_TABLE='$db_table'".
                     " WHERE LIST_ID = '$list_id'";
        //echo $statement;

        $result = $this->dbi->query($statement);

        return ($result != DB_OK) ? FALSE : $list_id;

      }

      function addMapping($list_id,
                          $custid,
                          $custfname,
                          $custlname,
                          $custeaddr,
                          $custage,
                          $custincome,
                          $custsex)
      {
        $fieldsArr = array('LIST_ID', 'REC_ID', 'FIRST', 'LAST', 'EMAIL', 'AGE', 'INCOME', 'SEX');

        $fields = implode(",", $fieldsArr);

        $statement = "INSERT INTO $this->list_field_map($fields) ".
                     "VALUES($list_id, '$custid', '$custfname', '$custlname', '$custeaddr', '$custage', '$custincome', '$custsex')";
        //echo $statement;             

        $result = $this->dbi->query($statement);

        return ($result != DB_OK) ? FALSE : TRUE;

      }

      function getAvailableLists()
      {

         $listArr = array();

         $statement = "SELECT LIST_ID, NAME FROM $this->list_tbl";


         $result = $this->dbi->query($statement);

         while($row = $result->fetchRow())
         {
            $listArr[$row->LIST_ID] = $row->NAME;
         }

         return $listArr;
      }

      function deleteList($lid = null)
      {
          $this->setEcampaignListID($lid);
          
          $del_lid = isset($this->lid) ? $this->lid : -1;

          $stmt   = "DELETE from $this->list_tbl " .
                    "WHERE LIST_ID  = $del_lid";

          $result = $this->dbi->query($stmt);

          if ($result == DB_OK)
          {

              $statement = "DELETE FROM $this->list_field_map ".
                           "WHERE LIST_ID = $del_lid";

              $result = $this->dbi->query($statement);

              if ($result == DB_OK)
              {
                 return true;
              }
              else {
                 return false;
              }


          }
          return FALSE;
      }

      function prepareLocalList($lid = null, $client_db = null, $tbl = null)
      {
          
          $this->setEcampaignListID($lid);
          $fieldArr = array();

          $allfieldsArr = array('REC_ID', 'FIRST', 'LAST', 'EMAIL', 'AGE', 'INCOME', 'SEX');

          foreach ($allfieldsArr as $k)
          {
             //echo $this->map($k)."<br>";
             $fieldArr = $this->pushMappedFields($fieldArr, $this->map($k));
          }
          $fields = implode(",", $fieldArr);

          $statement = "SELECT $fields FROM $tbl";
          
          //echo $statement;

          $clresult = $client_db->query($statement);

          $allmappedFieldsArr = array();

          foreach ($allfieldsArr as $af)
          {
            $allmappedFieldsArr[] = $this->map($af);
          }

          $allfields = implode(",",$allfieldsArr);

          $counter = 0;

          while ($row = $clresult->fetchRow())
          {
              $allmappedfieldsvalueArr = array();
              foreach ($allmappedFieldsArr as $amf)
              {
                 //BAD CODE $allmappedfieldsvalueArr[] = "'".$row->$amf."'";
                 $v = empty($row->$amf) ? NULL : $row->$amf;
                 $allmappedfieldsvalueArr[] = $this->dbi->quote(addslashes($v));
              }

              $allmappedfieldsvalue = implode(",", $allmappedfieldsvalueArr);
              
              $stmt = "INSERT INTO $this->assembly_tbl(LIST_ID, $allfields) ".
                      "VALUES($this->lid, $allmappedfieldsvalue)";

              // BAD CODE $this->dbi->query($stmt) or die('foo');        
              $result = $this->dbi->query($stmt);

              if ($result == DB_OK)
              {
                 $counter++;
              }
          }

          // ENHANCEMENT NOTES: A JOIN statement between ASSEMBLY and UNSUB
          // table could be used to reduce database IO.
          // and perhaps speed up the code.
          // Here you are getting each unsub and looping through them to
          // remove from assmebly table. Why not try:
          // delete from assembly where assembly.REC_ID = unsub.REC_ID;
          // something like that would be best and done in one shot
          // ASK SOHEL


          $stmt = "SELECT DISTINCT REC_ID FROM $this->unsub_tbl ".
                  "WHERE LIST_ID = $this->lid";
      
       $result2 = $this->dbi->query($stmt);

          if ($result2 != null)
          {
             while($row = $result2->fetchRow())
             {
                  $stmt = "DELETE FROM $this->assembly_tbl WHERE REC_ID = $row->REC_ID AND LIST_ID = $this->lid";

                  $result = $this->dbi->query($stmt);

                  if ($result == DB_OK)
                  {
                     $counter--;
                 }
             }
       }
          return $counter;
      }

      function pushMappedFields($a = null, $fieldValue = null)
      {
           if (!empty($fieldValue))
           {
              $a[] = $fieldValue;
            }
          return $a;
      }

      function map($fieldname = null)
      {

          $statement = "SELECT $fieldname FROM $this->list_field_map ".
                       "WHERE LIST_ID = $this->lid";


          $result = $this->dbi->query($statement);

          if ($result->numRows() > 0)
          {
            $row = $result->fetchRow();
            $result->free();
            $returnVal =  $row->$fieldname;

          } else {
             $returnVal =  null;
          }

          return $returnVal;
      }


      function getClientDBURL($lid = null)
      {
          $this->setEcampaignListID($lid);
          $statement = "SELECT DB_HOST, DB_USER, DB_PASSWD, DB_TYPE, DB_NAME, DB_TABLE FROM $this->list_tbl ".
                       "WHERE LIST_ID = $this->lid";

          $result = $this->dbi->query($statement);

          if ($result->numRows() == 0)
          {
             return false;
          }
          $row = $result->fetchRow();

          $clientDBURL = strtolower($row->DB_TYPE). '://' .$row->DB_USER. ':' . base64_decode($row->DB_PASSWD) . '@' .$row->DB_HOST .'/'. $row->DB_NAME;

          $Arr = array();

          $Arr[$row->DB_TABLE] = $clientDBURL;

          $result->free();

          return $Arr;
      }

      function getTargetData($lastRow = null, $deliverySize = null)
      {
          $this->setEcampaignListID();

          if (empty($lastRow))
          {
             $lastRow = 0;
          }

          $stmt = "SELECT DISTINCT REC_ID, EMAIL, FIRST, LAST, AGE, INCOME, SEX ".
                  "FROM $this->assembly_tbl " .
                  "WHERE LIST_ID = $this->lid AND REC_ID > $lastRow " .
                  "ORDER BY REC_ID LIMIT $deliverySize";
          //echo $stmt;

          $result = $this->dbi->query($stmt);

          $retArray = array();

          if ($result != null )
          {
             while($row = $result->fetchRow())
             {
                $retArray[$row->REC_ID] = $row;
             }
          }

          $delsize = $lastRow + $deliverySize;
          $stmt    = "DELETE FROM $this->assembly_tbl WHERE LIST_ID = $this->lid AND REC_ID <= $delsize";
        $this->dbi->query($stmt);
          
          return $retArray;

     }

     function addToBounced($cid ,$lid, $rid)
     {
         $stmt = "INSERT INTO $this->bounced_tbl (CAMPAIGN_ID, LIST_ID, REC_ID) VALUES($cid, $lid, $rid)";
         $this->dbi->query($stmt);
     }
      
     function modifyMapList($params = null )
     {

        $this->setEcampaignListID($params['LIST_ID']);

        $fieldsArr = $this->std_map_fields;
         
          $keyValues = array();

         while(list($k, $v) = each ($fieldsArr))
        {
            if (!strcmp($v, 'text'))
            {
               $keyValues[] = $k . '=' . $this->dbi->quote(addslashes($params[$k]));
            } else {
                $keyValues[] = $k . '=' . $params[$k];
            }
        }

        $updateKeyValues = implode(', ', $keyValues);

        $statement = "UPDATE $this->list_field_map SET $updateKeyValues ".
                     "WHERE LIST_ID = $this->lid";

        //echo $statement;
        $result = $this->dbi->query($statement);

        return ($result == DB_OK) ? TRUE : FALSE;
        
     }

     
   }

//"SET LIST_ID=$valueList[0], " .
?>
