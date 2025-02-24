<?php

   class Response
   {

      function Response($dbi = null, $rid = null)
      {
         global $LD_CATEGORY_TBL;
         global $LD_DOC_TBL;
         global $LD_RESPONSE_TBL;

         $this->dbi = $dbi;
                  
         $this->cat_tbl = $LD_CATEGORY_TBL;
         $this->doc_tbl = $LD_DOC_TBL;
         $this->response_tbl = $LD_RESPONSE_TBL;

         $this->std_fields = array( 
                                     'RESPONSE_ID'  =>   'number',
                                     'RESPONDER'    =>   'text',
                                     'SUBJECT'      =>   'text',
                                     'RATE'         =>   'number',
                                     'COMMENT'      =>   'text',
                                     'DOC_ID'      =>    'number',
                                     'RESPONSE_TS'  =>   'number'
                        );
         

         $this->fields = implode(',', array_keys($this->std_fields));

         $this->setResponseID($rid);
      }
      
      function loadResponseInfo($rid = null)
      {
          $this->setResponseID($rid);
          
          $stmt = "SELECT $this->fields FROM $this->response_tbl "
                 ."WHERE RESPONSE_ID = $this->rid";
          
          $result = $this->dbi->query($stmt);
          if ($result->numRows() > 0)
          {
              $row = $result->fetchRow();
              reset($this->std_fields);
              while(list($fieldName, $fieldType) = each($this->std_fields))
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
      
      function setResponseID($rid = null)
      {
          if (!empty($rid))
          {
              $this->rid  = $rid;
          }
          else
          {
              $this->rid  = isset($this->rid) ? $this->rid : NULL;	
          }
          
          return $this->rid;
      }
      
      function getResponseSubject($rid = null)
      {
      	  $this->loadResponseInfo($rid);
      	  return $this->SUBJECT;
      }
      
      function getResponseDocID($rid = null)
      {
      	  $this->loadResponseInfo($rid);
      	  return $this->DOC_ID;
      }
      
      function getResponder($rid = null)
      {
      	  $this->loadResponseInfo($rid);
      	  return $this->RESPONDER;
      }
      
      function getResponseBody($rid = null)
      {
      	  $this->loadResponseInfo($rid);
      	  return $this->COMMENT;
      }
      
      
      
      function addResponse($params = null)
      {
      	 
      	 $fieldsArr = $this->std_fields;

      	 $keyValues = array();

      	 while(list($k, $v) = each ($fieldsArr))
      	 {
            if (!strcmp($v, 'text'))
            {
            	$v = $this->dbi->quote(addslashes($params[$k]));

            } else {

                $v = $params[$k];
            }

            $valueList[] = $v;
         }

         $values = implode(',', $valueList);

         $statement = "INSERT INTO $this->response_tbl ($this->fields) VALUES ( $values )";
         
         
         $result = $this->dbi->query($statement);
         
         if ($result != DB_OK)
         {
            return false;	
         }
         
         $stmt = "SELECT RESPONSE_ID FROM $this->response_tbl WHERE RESPONDER = '$params[RESPONDER]' AND SUBJECT = '$params[SUBJECT]' "
                ."AND RATE = $params[RATE] AND DOC_ID = $params[DOC_ID] AND RESPONSE_TS = $params[RESPONSE_TS]";
         //echo $stmt;
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows()< 0)
         {
            
            return null;	
         }
         $row = $result->fetchRow();
         return $row->RESPONSE_ID;
      }
      
      function getResponsesByDocID($nid)
      {
         $stmt = "SELECT $this->fields from $this->response_tbl WHERE DOC_ID = $nid";
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->RESPONSE_ID] = $row; 	
         }
         return $retArr;
      }
      
      function getTotalResponseByDocID($nid)
      {
         $stmt = "SELECT COUNT(RESPONSE_ID) AS CNT FROM $this->response_tbl WHERE DOC_ID = $nid";
         $result = $this->dbi->query($stmt);
         $row = $result->fetchRow();
         return $row->CNT;
      }
      
      function getAvgRatingByDocID($nid)
      {
         $stmt = "SELECT AVG(RATE) AS RATING FROM $this->response_tbl WHERE DOC_ID = $nid";	
         $result = $this->dbi->query($stmt);
         $row = $result->fetchRow();
         return $row->RATING;
      }
      
      function deleteResponse($rid = null)
      {
         $this->setResponseID($rid);
         $stmt = "DELETE FROM $this->response_tbl WHERE RESPONSE_ID = $this->rid";
         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      
      
}