<?php


    class SurveyResponse
    {
       function SurveyResponse($dbi = null,
                               $sid = null,
                               $fid = null)
       {

          global $SURVEY_RESPONSE_TBL,
                 $SURVEY_RESPONSE_RECORD_TBL;

          $this->dbi              = $dbi;
          $this->survey_id        = $sid;
          $this->form_id          = $fid;
          $this->response_tbl     = $SURVEY_RESPONSE_TBL;
          $this->response_rec_tbl = $SURVEY_RESPONSE_RECORD_TBL;

       }

       function isSubmitted($suid = null,
                            $exec_id = null)
       {
          $stmt = "SELECT 1 from $this->response_rec_tbl ".
                  "WHERE SUID = $suid AND EXEC_ID = $exec_id";

          $result = $this->dbi->query($stmt);

          if ($result != null && $result->numRows() == 1)
          {
              return TRUE;
          }

          return FALSE;
       }

       function addSubmitRecord($suid = null, $exec_id = null, $submit_ts = null)
       {
           $stmt = "INSERT INTO $this->response_rec_tbl " .
                   "(SUID, EXEC_ID, SUBMIT_TS)" .
                   " VALUES($suid, $exec_id, $submit_ts)";

           $result = $this->dbi->query($stmt);

           if ($result != DB_OK)
           {
               return FALSE;
           }

           return TRUE;

       }

       function add($params = null)
       {
          $fields = array('EXEC_ID',
                          'SUID',
                          'FIELD_ID',
                          'VALUE');

          $fieldList = implode(',', $fields);

          $params['VALUE'] = $this->dbi->quote(addslashes($params['VALUE']));

          foreach ($fields as $key)
          {
             $valueList[] = $params[$key];
          }

          $values = implode(',', $valueList);

          $stmt = "INSERT INTO $this->response_tbl " .
                  "($fieldList) VALUES($values)";

          $result = $this->dbi->query($stmt);


          if ($result != DB_OK)
          {
             return FALSE;
          }

          return TRUE;
       }
    }



?>
