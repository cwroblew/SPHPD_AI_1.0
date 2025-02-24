<?php

   class Doc
   {

      function Doc($dbi = null, $nid = null)
      {
         global $LD_CATEGORY_TBL;
         global $LD_DOC_TBL;
         global $LD_RESPONSE_TBL;
         global $LD_TRACK_TBL;
         

                  
         $this->cat_tbl = $LD_CATEGORY_TBL;
         $this->doc_tbl = $LD_DOC_TBL;
         $this->resp_tbl = $LD_RESPONSE_TBL;
         $this->track_tbl = $LD_TRACK_TBL;

         $this->std_fields = array( 
                                     'DOC_ID'           =>    'number',
                                     'CAT_ID'            =>   'number',
                                     'HEADING'           =>   'text',
                                     'BODY'              =>   'text',
                                     'PUBLISH_DATE'      =>   'number',
                                     
                        );
         

         $this->fields = implode(',', array_keys($this->std_fields));

         $this->dbi = $dbi;
         $this->setDocID($nid);
      }
      
      function loadDocInfo($nid = null)
      {
          $this->setDocID($nid);
          
          $stmt = "SELECT $this->fields FROM $this->doc_tbl "
                 ."WHERE DOC_ID = $this->nid";
          
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
      
      function setDocID($nid = null)
      {
          if (!empty($nid))
          {
              $this->nid  = $nid;
          }
          else
          {
              $this->nid  = isset($this->nid) ? $this->nid : NULL;	
          }
          
          return $this->nid;
      }
      
      function getHeading($nid = null)
      {
      	  $this->loadDocInfo($nid);
      	  return $this->HEADING;
      }
      
      function getPublishDate($nid = null)
      {
      	  $this->loadDocInfo($nid);
      	  return $this->PUBLISH_DATE;
      }
      
      function getBody($nid = null)
      {
      	  $this->loadDocInfo($nid);
      	  return $this->BODY;
      }
      
      function getCategory($nid = null)
      {
      	  $this->loadDocInfo($nid);
      	  return $this->CAT_ID;
      }
      
      
      function addDoc($params = null)
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

         $statement = "INSERT INTO $this->doc_tbl ($this->fields) VALUES ( $values )";
         $result = $this->dbi->query($statement);
         
         if ($result != DB_OK)
         {
            return false;	
         }
         
         $stmt = "SELECT DOC_ID FROM $this->doc_tbl WHERE HEADING = '$params[HEADING]' AND CAT_ID = $params[CAT_ID] AND PUBLISH_DATE = $params[PUBLISH_DATE]";
         
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows()< 0)
         {
            
            return null;	
         }
         $row = $result->fetchRow();
         return $row->DOC_ID;
      }
      
      function modifyDoc($params = null)
      {
      	 $fieldsArr = $this->std_fields;

      	 while(list($k, $v) = each ($fieldsArr))
      	 {
            if (!strcmp($v, 'text'))
            {
            	$params[$k] = $this->dbi->quote(addslashes($params[$k]));
            }
         }
         
         $keyValue = null;
         while(list($k, $v) = each($params))
         {
            $keyValue .= empty($keyValue) ? null : ', ';
            $keyValue .= $k.' = '.$v;  	
         }
         
         $stmt = "UPDATE $this->doc_tbl SET $keyValue WHERE DOC_ID = $params[DOC_ID]";
         //echo $stmt;
         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true :  false;
      }
      
      function deleteDoc($nid = null)
      {
         $this->setDocID($nid);
         $stmt = "DELETE FROM $this->doc_tbl WHERE DOC_ID = $this->nid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
         
         
      }
      
      function deleteResponsesByDocID($nid = null)
      {
         $this->setDocID($nid);
         $stmt = "DELETE FROM $this->resp_tbl WHERE DOC_ID = $this->nid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      function getDocesByCatID($cid = null)
      {
         $curTime = mktime();
         $stmt = "SELECT $this->fields from $this->doc_tbl WHERE CAT_ID = $cid AND PUBLISH_DATE < $curTime ORDER BY PUBLISH_DATE DESC";
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->DOC_ID] = $row; 	
         }
         return $retArr;
      }
      
      function getAllDocesByCatID($cid = null)
      {
         $stmt = "SELECT $this->fields from $this->doc_tbl WHERE CAT_ID = $cid ORDER BY PUBLISH_DATE DESC";
         
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->DOC_ID] = $row; 	
         }
         return $retArr;
      }
      
      function trackVisit($nid, $uid, $ts)
      {
         $stmt = "INSERT INTO $this->track_tbl(DOC_ID, UID, VISIT_TS) VALUES($nid, $uid, $ts)";         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;	
      }
      
      function getTrackDetails($nid)
      {
         $stmt = "SELECT UID, VISIT_TS FROM $this->track_tbl WHERE DOC_ID = $nid ORDER BY VISIT_TS DESC";
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while($row = $result->fetchRow())
         {
            $retArr[] = $row;	
         }
         return $retArr;
      }
}
