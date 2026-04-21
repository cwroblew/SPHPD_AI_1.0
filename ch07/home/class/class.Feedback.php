<?php


   class Feedback
   {
      function Feedback($dbi = null, $feedback_id = null)
      {

          global $FEEDBACK_TBL;

          $this->dbi = $dbi;

          $this->feedback_tbl = $FEEDBACK_TBL;

          if (!empty($feedback_id))
          {
             $this->feedback_id = $feedback_id;
          }
      }

      function setFeedbackID($id = null)
      {
          if (!empty($id))
          {
              $this->feedback_id  = $id;
          }

          return $this->feedback_id;
      }

      function add($data = null)
      {
      	 $status = FALSE;

         $fields = array( 'USER_ID'    => 'number',
                          'COURSE_ID'  => 'text',
                          'LESSON_ID'  => 'number',
                          'RATING'     => 'number',
                          'OPTIN'      => 'number',
                          'ISSUE'      => 'text',
                          'ISSUE_TYPE' => 'number',
                          'ISSUE_DATE' => 'number',
                          'COMMENTS'   => 'text'
                        );

         $fieldList = array();

         while(list($k, $v) = each($fields))
         {
            if (!empty($data[$k]))
            {
               $fieldList[] = $k;
               if (!strcmp($v, 'number'))
               {
                  $values[] = $data[$k];

               } else {
            	  $values[] = $this->dbi->quote(addslashes($data[$k]));
               }
            }
         }


         $valueList = implode(',',$values);
         $haveFIelds = implode(',',$fieldList);
         $statement = "INSERT INTO $this->feedback_tbl ($haveFIelds) VALUES ($valueList)";
         $result = $this->dbi->query($statement);

         if ($result != null)
         {
            return TRUE;
         }
      	 return FALSE;
      }
   }


?>
