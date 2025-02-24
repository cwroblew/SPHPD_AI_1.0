<?php
   class SurveyForm
   {
      function SurveyForm($dbi = null, $fid=null)
      {
          global $SURVEY_FORM_TBL;

          global $SURVEY_FORM_FIELD_LBL_TBL;

          $this->survey_form_tbl = $SURVEY_FORM_TBL;

          $this->survey_form_field_tbl = $SURVEY_FORM_FIELD_LBL_TBL;

          $this->dbi = $dbi;
          if (!empty($fid))
          {
             $this->fid = $fid;
          }
      }

      function setSurveyFormID($fid = null)
      {
          if (!empty($fid))
          {
              $this->fid  = $fid;
          }

          return $this->fid;
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


      function addNewSurveyForm($params = null, $textFields  = null)
      {

         $params['CHECKFLAG'] = $params['CREATOR_ID'] + $params['CREATE_TS'];

         while(list($k, $v) = each ($params))
         {
             $fieldList[] = $k;

             if (!empty($textFields[$k]))
             {
                 $valueList[] = $this->dbi->quote($v);

             } else {
                 $valueList[] = $v;
             }
         }

         $fields = implode(',', $fieldList);
         $values = implode(',', $valueList);

         $stmt = "INSERT INTO $this->survey_form_tbl " .
                 "($fields) VALUES($values)";

         $result = $this->dbi->query($stmt);


         $stmt   = "SELECT FORM_ID FROM $this->survey_form_tbl " .
                   "WHERE NAME='$params[NAME]'";

         $result = $this->dbi->query($stmt);

         if ($result == null)
         {
            return FALSE;
         }

         $row = $result->fetchRow();

         return $row->FORM_ID;
      }


      function getAvailableForms()
      {
         $formArr = array();

         $statement = "SELECT FORM_ID, NAME FROM $this->survey_form_tbl";

         $result = $this->dbi->query($statement);

         while($row = $result->fetchRow())
         {

            $formArr[$row->FORM_ID]     = $row->NAME;
         }

         return $formArr;

      }

      function deleteForm($fid = null)
      {
         $this->setSurveyFormID($fid);

         $statement = "DELETE FROM $this->survey_form_tbl " .
                      "WHERE FORM_ID = $this->fid";

         $result  = $this->dbi->query($statement);

         if ($result == DB_OK)
         {
             return TRUE;

         } else {

             return FALSE;
         }
      }

      function addLabel($formid, $fieldid, $label)
      {

         $label = addslashes($label);

         $statement = "INSERT INTO $this->survey_form_field_tbl ".
                      "VALUES($formid, $fieldid, '$label')";
         echo $statement;             

         $result = $this->dbi->query($statement);

         if ($result == DB_OK)
         {
             return TRUE;

         } else {

             return FALSE;
         }
      }

      function getTemplate($formid = null)
      {
          $this->setSurveyFormID($formid);

          $stmt = "SELECT TEMPLATE FROM $this->survey_form_tbl ".
                  "WHERE FORM_ID= $this->fid";

          $result = $this->dbi->query($stmt);

          if ($result  != null)
          {
                $row = $result->fetchRow();

                return $row->TEMPLATE;
          }

          else return FALSE;
      }

      function getFormInfo($formid = null)
      {
          $this->setSurveyFormID($formid);

          $fields  = array('NAME' => 'text',
                          'TEMPLATE' => 'text',
                          'MAILFROM' => 'text',
                          'SUBJECT' => 'text',
                          'CREATE_TS' => 'number',
                          'CREATOR_ID' => 'number'
                         );

          $fieldList = implode(',' , array_keys($fields));

          $stmt = "SELECT $fieldList FROM $this->survey_form_tbl " .
                  "WHERE FORM_ID= $this->fid";

          $result = $this->dbi->query($stmt);

          if ($result  != null)
          {
              return $result->fetchRow();

          }

          else return FALSE;
      }
   }
?>
