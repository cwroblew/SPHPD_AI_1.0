<?php

   class EcampaignMessage
   {
      function EcampaignMessage($dbi = null, $mid = null)
      {
          global $ECAMPAIGN_MESSAGE_TBL, $MESSAGE_HDRS_TBL;

          $this->message_tbl   = $ECAMPAIGN_MESSAGE_TBL;
          $this->header_tbl    = $MESSAGE_HDRS_TBL;

          $this->dbi = $dbi;

          $this->msg_fields = array(
                                'MSG_ID'     => 'number',
                                'NAME'       => 'text',
                                'BODY'       => 'text',
                                'CREATE_TS'  => 'number',
                                'CREATOR_ID' => 'number'
                              );

          $this->hdr_fields = array(
                                'MSG_ID'     => 'number',
                                'HDR_ID'     => 'number',
                                'HDR_VALUE'  => 'text'
                              );

          $this->setEcampaignMessageID($mid);

      }

      function setEcampaignMessageID($mid = null)
      {
          if (!empty($mid))
          {
              $this->mid  = $mid;
          }
          return isset($this->mid) ?  $this->mid : NULL;
      }

      function getEcampaignMessageInfo($mid = null)
      {

           $this->setEcampaignMessageID($mid);

           $fields = implode(',', array_keys($this->msg_fields ));

           $this->setEcampaignMessageID($mid);

           $stmt = "SELECT $fields FROM $this->message_tbl " .
                   "WHERE MSG_ID = $this->mid";

           $result = $this->dbi->query($stmt);

           if ($result->numRows() == 0)
           {
              return false;
           }

           if ($result == null)
           {
               return null;
           }

           $row = $result->fetchRow();

           while(list($k, $v) = each($this->msg_fields))
           {
              if (!strcmp($v, 'text'))
              {
                 $row->$k = stripslashes($row->$k);
              }
           }

           return $row;
      }


      function getEcampaignHeaderInfo($mid = null)
      {
            $this->setEcampaignMessageID($mid);

            $fields = implode(',' , array_keys($this->hdr_fields));

            $stmt = "SELECT $fields FROM $this->header_tbl WHERE MSG_ID = $this->mid";

            $result = $this->dbi->query($stmt);

            if ($result == null)
            {
               return null;
            }

            $headerArr = array();

            while ($row = $result->fetchRow())
            {
                $headerArr[$row->HDR_ID] = stripslashes($row->HDR_VALUE);
            }

            return $headerArr;
      }

      function addNewEcampaignMessage($msgname,
                                      $msgfrom,
                                      $msgreply,
                                      $msgprio,
                                      $msgsub,
                                      $message,
                                      $today,
                                      $uid)
      {
         $fieldArr = array('NAME', 'BODY', 'CREATE_TS', 'CREATOR_ID');

         $fields = implode(",", $fieldArr);

         $stmt   = "INSERT INTO $this->message_tbl($fields) " .
                   "VALUES('$msgname', '$message', $today, $uid)";

         $result = $this->dbi->query($stmt);

         if ($result == DB_OK)
         {
             $stmt = "SELECT MSG_ID FROM $this->message_tbl WHERE NAME = '$msgname'";
             $result = $this->dbi->query($stmt);
             $row = $result->fetchRow();
             $mid = $row->MSG_ID;

             $headerArr = array($msgfrom, $msgreply, $msgprio, $msgsub);
             for ($i = 1; $i <= 4; $i++)
             {
                     $h = $i-1;
                     $headerArr[$h] = $this->dbi->quote($headerArr[$h]);
                     $stmt = "INSERT INTO $this->header_tbl VALUES($mid, $i, $headerArr[$h])";
                     $result2 = $this->dbi->query($stmt);
             }
             return $mid;
         }
         else return false;
      }

      function getAvailableMessages()
      {

         $msgArr = array();

         $stmt = "SELECT MSG_ID, NAME FROM $this->message_tbl";

         $result = $this->dbi->query($stmt);

         if ($result == null)
         {
            return null;
         }

         while($row = $result->fetchRow())
         {
            $msgArr[$row->MSG_ID] = stripslashes($row->NAME);
         }

         return $msgArr;
      }

      function deleteMessage($mid = null)
      {
          $this->setEcampaignMessageID($mid);

          $stmt   = "DELETE from $this->message_tbl " .
                    "WHERE MSG_ID  = $this->mid";

          $result = $this->dbi->query($stmt);

          if ($result == DB_OK)
          {

              $stmt = "DELETE FROM $this->header_tbl ".
                           "WHERE MSG_ID = $this->mid";

              $result = $this->dbi->query($stmt);

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

      function UpdateEcampaignMessage($params = null)
      {

          $this->setEcampaignMessageID($params['MSG_ID']);

          $fieldList = array_keys($this->msg_fields);
          $fields = implode(",", $fieldList);

          $valueList = array();


          while(list($k, $v) = each ($this->msg_fields))
          {
              if (!strcmp($v, 'text'))
              {
                  $v = $this->dbi->quote(addslashes($params[$k]));
              } else {
                  $v = $params[$k];
              }

              $valueList[] = "$k = $v";
          }

          $values = implode(',', $valueList);

          $stmt = "UPDATE $this->message_tbl SET $values WHERE MSG_ID = $this->mid";

          $result = $this->dbi->query($stmt);

          return ($result != DB_OK) ? false : true;
     }

     function UpdateEcampaignMessageHdr($mid, $msgfrom, $msgreply, $msgprio, $msgsub)
     {
        $this->setEcampaignMessageID($mid);

        $msgsub = addslashes($msgsub);

        $headerArr = array($msgfrom, $msgreply, $msgprio, $msgsub);

        for ($i = 1; $i <= 4; $i++)
        {
           $h = $i-1;

           $stmt = "UPDATE $this->header_tbl SET HDR_VALUE ='".$headerArr[$h].
                     "' WHERE MSG_ID = $this->mid AND HDR_ID = $i";

           $result2 = $this->dbi->query($stmt);
        }

        if ($result2 != DB_OK)
        {
            return false;

        }else{

            return true;
        }
     }
   }
?>