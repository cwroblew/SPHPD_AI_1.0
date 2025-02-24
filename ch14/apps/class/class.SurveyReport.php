<?php


    class SurveyReport
    {
       function SurveyReport($dbi = null, $execid = null)
       {

          global $SURVEY_RESPONSE_TBL,
                 $SURVEY_EXECUTION_TBL,
                 $SURVEY_TBL,
                 $SURVEY_FORM_FIELD_LBL_TBL,
                 $SURVEY_RESPONSE_RECORD_TBL;

          $this->dbi           = $dbi;
          $this->execid        = $execid;
          $this->response_tbl  = $SURVEY_RESPONSE_TBL;
          $this->execution_tbl = $SURVEY_EXECUTION_TBL;
          $this->survey_tbl    = $SURVEY_TBL;
          $this->survey_response_rec_tbl = $SURVEY_RESPONSE_RECORD_TBL;
          $this->form_field_tbl      = $SURVEY_FORM_FIELD_LBL_TBL;
       }

       function setSurveyExecID($execid = null)
       {
          if (!empty($execid))
          {
              $this->execid  = $execid;
          }

          return $this->execid;
       }

       function getSurveyResponse($execid = null,
                                  $ordercriteria = null,
                                  $desc = null)
       {

          $this->setSurveyExecID($execid);
          if (empty($ordercriteria) ) $ordercriteria = 'FIELD_ID';
          $stmt = "SELECT COUNT(DISTINCT SUID) AS CNT, FIELD_ID, VALUE " .
                  "FROM $this->response_tbl ".
                  "WHERE EXEC_ID = $this->execid ".
                  "GROUP BY VALUE ".
                  "ORDER BY $ordercriteria $desc";

          $result = $this->dbi->query($stmt);;
          $responseArr = array();
          if ($result != null)
          {
             while ($row = $result->fetchRow())
             {
                $arrkey = $row->FIELD_ID. ':' .$row->VALUE;
                $responseArr[$arrkey] = $row->CNT;
             }
             return $responseArr;
          }
          else return null;

       }

       function getResponseDateRange($execid = null)
       {

          $this->setSurveyExecID($execid);

          $stmt = "SELECT MAX(SUBMIT_TS) AS LASTDATE, MIN(SUBMIT_TS) " .
                  "AS STARTDATE FROM $this->survey_response_rec_tbl ".
                  "WHERE EXEC_ID = $this->execid ";

          $result = $this->dbi->query($stmt);;

          $retArray = array();

          if ($result != null)
          {
             $row                   = $result->fetchRow();
             $retArray['STARTDATE'] = $row->STARTDATE;
             $retArray['LASTDATE']  = $row->LASTDATE;

          }
          return $retArray;
       }

       function getTotalResponseCount($execid = null)
       {

          $this->setSurveyExecID($execid);

          $stmt = "SELECT COUNT(DISTINCT SUID) AS CNT " .
                  "FROM $this->response_tbl ".
                  "WHERE EXEC_ID = $this->execid ";

          $result = $this->dbi->query($stmt);;

          if ($result != null)
          {
             $row = $result->fetchRow();
             return $row->CNT;
          }

          return 0;
       }

       function getLabelsbyFieldAndExecID($fieldid, $execid)
       {
         $stmt = "SELECT $this->form_field_tbl.LABEL FROM " .
                 "$this->execution_tbl, " .
                 "$this->survey_tbl, " .
                 "$this->form_field_tbl " .
                 "WHERE $this->form_field_tbl.FIELD_ID = $fieldid AND " .
                 "$this->form_field_tbl.FORM_ID        =  $this->survey_tbl.FORM_ID " .
                 "AND $this->survey_tbl.SURVEY_ID      = $this->execution_tbl.SURVEY_ID " .
                 "AND $this->execution_tbl.EXEC_ID     = $execid";

         $result = $this->dbi->query($stmt);

         if ($result!=null)
         {
            $row = $result->fetchRow();
            return $row->LABEL;
         }
         else return null;
       }
    }

?>
