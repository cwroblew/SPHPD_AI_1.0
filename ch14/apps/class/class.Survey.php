<?php

   class Survey
   {
      function Survey($dbi = null, $sid = null)
      {

         global $SURVEY_TBL, $SURVEY_EXECUTION_TBL, $SURVEY_RESPONSE_TBL, $SURVEY_RESPONSE_RECORD_TBL;

         $this->survey_tbl = $SURVEY_TBL;

         $this->survey_execution_tbl = $SURVEY_EXECUTION_TBL;

         $this->response_tbl = $SURVEY_RESPONSE_TBL;

         $this->response_rec_tbl = $SURVEY_RESPONSE_RECORD_TBL;

         $this->dbi = $dbi;

         $this->SURVEY_ID  = $sid;

      }

      function getSurveyID()
      {
         return $this->SURVEY_ID;
      }

      function setSurveyID($sid = null)
      {
         if (! empty($sid))
         {
            $this->SURVEY_ID = $sid;
         }

         return $this->SURVEY_ID;
      }

      function JUNK_setSurveyTargetID($tid = null)
      {
         if (! empty($tid))
         {
            $this->LIST_ID = $tid;
         }

         return $this->LIST_ID;
      }

      function getStatus($sid = null)
      {
          $this->setSurveyID($sid);

          $stmt = "SELECT STATUS from $this->survey_tbl " .
                  "WHERE SURVEY_ID = $this->SURVEY_ID";

          $result = $this->dbi->query($stmt);

          if ($result != null)
          {
             $row = $result->fetchRow();

             return $row->STATUS;
          }

          return null;
      }


      function setStatus($status = null, $sid = null)
      {
          $this->setSurveyID($sid);

          $stmt = "UPDATE $this->survey_tbl SET STATUS = $status ".
                  "WHERE SURVEY_ID = $this->SURVEY_ID";

          $result = $this->dbi->query($stmt);

          if ($result == DB_OK)
          {
              return TRUE;
          }

          return FALSE;
      }

      function getSurveyInfo($sid = null)
      {

         $fields   = array('SURVEY_ID',
                           'LIST_ID',
                           'FORM_ID',
                           'CREATE_TS',
                           'CREATOR_ID');

         $fieldStr = implode(',', $fields);

         $this->setSurveyID($sid);

         $stmt   = "SELECT $fieldStr FROM $this->survey_tbl " .
                   "WHERE SURVEY_ID = $this->SURVEY_ID";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             $this->SURVEY_ID  = $row->SURVEY_ID;             
             $this->FORM_ID    = $row->FORM_ID;
             $this->LIST_ID    = $row->LIST_ID;
             $this->CREATE_TS  = $row->CREATE_TS;
             $this->CREATOR_ID = $row->CREATOR_ID;

             return TRUE;
         }

         return FALSE;
      }

      function getListID($survey_id = null)
      {
         $this->setSurveyID($survey_id);
         return $this->LIST_ID;
      }

      function getFormID($survey_id = null)
      {
         $this->setSurveyID($survey_id);
         return $this->FORM_ID;
      }


      function addSurvey($name = null,
                         $list_id = null,
                         $form_id = null,
                         $creator_id = null)
      {

          $fields = implode(',',  array('LIST_ID',
                                      'FORM_ID',
                                      'NAME',
                                      'CREATE_TS',
                                      'CREATOR_ID'
                                     )
                           );

          $values = implode(',', array($list_id,
                                      $form_id,
                                      $this->dbi->quote(addslashes($name)),
                                      time(),
                                      $creator_id
                                     )
                            );

          $stmt = "INSERT INTO $this->survey_tbl " .
                  "($fields) VALUES($values)";

          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);

      }

      function deleteSurvey($survey_id = null)
      {

         $this->setSurveyID($survey_id);

         if (empty($survey_id))
         {
             return FALSE;
         }

         $stmt = "DELETE from $this->survey_tbl " .
                 "WHERE SURVEY_ID = $this->SURVEY_ID";

         $result = $this->dbi->query($stmt);

         $status =$this->getReturnValue($result);
         if ($status)
         {
            // Now find all EXEC_ID for this SURVEY_ID
            $execIDHash = $this->getExecutinRecordList($this->SURVEY_ID);
            $execIDList = array_keys($execIDHash);
            foreach ($execIDList as $execID)
            {
               $status2 = $this->deleteResponsesByExecID($execID);
            }

            $status3 = $this->deleteExecutionRecords($this->SURVEY_ID);

         }

         return TRUE;
      }


      function deleteExecutionRecords($survey_id = null)
      {
         $this->setSurveyID($survey_id);

         if (empty($survey_id))
         {
             return FALSE;
         }

         $stmt = "DELETE FROM $this->survey_execution_tbl " .
                 "WHERE SURVEY_ID = $this->SURVEY_ID";
         $result = $this->dbi->query($stmt);
         $status =$this->getReturnValue($result);

         return TRUE;
      }


      function deleteResponsesByExecID($exec_id = null)
      {
         if (empty($exec_id))
         {
                 return FALSE;
         }

         $stmt = "DELETE FROM $this->response_tbl " .
                 "WHERE EXEC_ID = $exec_id";

         $result = $this->dbi->query($stmt);
         $status =$this->getReturnValue($result);

         $stmt = "DELETE FROM $this->response_rec_tbl ".
                 "WHERE EXEC_ID = $exec_id";

         $result = $this->dbi->query($stmt);
         $status =$this->getReturnValue($result);

         return TRUE;
      }

      function getExecutinRecordList($survey_id = null)
      {
          if (! $survey_id)
          {
             $WHERE = null;
          } else {
             $WHERE = "WHERE SURVEY_ID = $survey_id";
          }

          $stmt = "SELECT EXEC_ID, SURVEY_TS, SURVEY_ID " .
                  "FROM $this->survey_execution_tbl $WHERE";


          $result = $this->dbi->query($stmt);

          $retArray = array();

          if ($result == null)
          {
              return $retArray;
          }

          while($row = $result->fetchRow())
          {

              $retArray[$row->EXEC_ID] = $row;
          }

          return $retArray;
      }

      function addExecutionRecord($survey_id = null, $now = null)
      {

         $this->setSurveyID($survey_id);

         $stmt = "INSERT INTO $this->survey_execution_tbl ".
                 "(SURVEY_ID, SURVEY_TS)" .
                 "VALUES($this->SURVEY_ID, $now)";

         $result = $this->dbi->query($stmt);

         if ($result == DB_OK)
         {

             $stmt = "SELECT EXEC_ID FROM $this->survey_execution_tbl ".
                     "WHERE SURVEY_ID = $this->SURVEY_ID AND SURVEY_TS = $now";

             $result = $this->dbi->query($stmt);

             $row = $result->fetchRow();

             return $row->EXEC_ID;

         } else {

             return null;

         }
      }

      function getAvailableSurveys()
      {
         $fieldList = array('SURVEY_ID', 'NAME');
         $fieldStr  = implode(',', $fieldList);

         $stmt = "SELECT $fieldStr FROM $this->survey_tbl";

         $result = $this->dbi->query($stmt);

         $retArray  = array();

         if ($result == null)
         {
             return $retArray;
         }

         while($row = $result->fetchRow())
         {
            $retArray[$row->SURVEY_ID] = stripslashes($row->NAME);
         }

         return $retArray;
      }

      function getReturnValue($r = null)
      {
          if ($r == DB_OK)
          {
              return TRUE;

          } else {

              return FALSE;
          }
      }
   }
?>
