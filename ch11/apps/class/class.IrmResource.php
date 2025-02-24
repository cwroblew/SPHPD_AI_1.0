<?php

   class IrmResource
   {
      function IrmResource($dbi = null)
      {
         global $IRM_RESOURCE_TBL, $IRM_RESOURCE_VISITOR, $IRM_RESOURCE_KEYWORD_TBL;
         
         $this->resource_tbl         = $IRM_RESOURCE_TBL;
         
         $this->resource_track_tbl   = $IRM_RESOURCE_VISITOR;
         
         $this->resource_keyword_tbl = $IRM_RESOURCE_KEYWORD_TBL;
         
         $this->dbi = $dbi;         
         
         $this->std_map_fields = array( 
                                         'RESOURCE_TITLE'        => 'text',
                                         'RESOURCE_LOCATION'     => 'text',
                                         'RESOURCE_CATEGORY'     => 'number',
                                         'RESOURCE_RATING'       => 'number',
                                         'RESOURCE_DESCRIPTION'  => 'text',
                                         'RESOURCE_ADDED_BY'     => 'number',
                                         'CREATE_TS'             => 'number',  
                                         'FLAG'                  => 'number',  
                                      );

         $this->fields = implode(',', array_keys($this->std_map_fields));
         
         $this->resource_track_map_fields = array(
                                                  'RESOURCE_ID'  => 'number',
                                                  'VISITOR_ID'   => 'number',
                                                  'VISIT_TS'    => 'number',
                                                 );
                                             
         $this->resource_track_fields = implode(',', array_keys($this->resource_track_map_fields));
      }
      
      
      function addResource($params = null)
      {         
         $fieldsArr = $this->std_map_fields;

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

         $statement = "INSERT INTO $this->resource_tbl ($this->fields) VALUES ( $values )";
         
         $result = $this->dbi->query($statement);

         if($result != DB_OK)
         {
            return false;
         } 
         
         $stmt = "SELECT RESOURCE_ID FROM $this->resource_tbl WHERE FLAG = $params[FLAG]";
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         $row = $result->fetchRow();
         
         return $row->RESOURCE_ID;
      }
      
      
      function addKeywords($keywordArray = null, $rid = null)
      {
      	 foreach($keywordArray as  $key=>$value)
      	 {
      	    $value = strtoupper($value);
           
            $statement = "INSERT INTO $this->resource_keyword_tbl (RESOURCE_ID, KEYWORD) VALUES ($rid, '$value')";
            
            $result = $this->dbi->query($statement);
         }
         return ($result == DB_OK) ? TRUE : FALSE; 
      }
      
      
      function deleteKeywords($rid = null)
      {
         $stmt = "DELETE FROM $this->resource_keyword_tbl WHERE RESOURCE_ID = $rid";
      	 
         $result = $this->dbi->query($stmt);
      	 
         return ($result == DB_OK) ? TRUE : FALSE;       
      }
      

      function getKeywords($rid = null)
      { 
         $stmt = "SELECT KEYWORD FROM $this->resource_keyword_tbl WHERE RESOURCE_ID = $rid";
        
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0) {
         	
            return null;	
         } 
            
         $retArr = array();
         
         while ($row = $result->fetchRow())
         {
            $retArr[] = $row->KEYWORD;
         }
         return $retArr;
      }
      
      
      function modifyResource($params = null, $resource_id = null)
      {
         $fieldsArr = $this->std_map_fields;

      	 $keyValues = array();

      	 while(list($k, $v) = each ($fieldsArr))
      	 {
            if (!strcmp($v, 'text'))
            {
               $v = $k .'='. $this->dbi->quote(addslashes($params[$k]));
           
            } else {
            
               $v = $k .'='. $params[$k];
            }
            
            $valueList[] = $v;
         }

         $values = implode(',', $valueList);

         $statement = "UPDATE $this->resource_tbl SET $values WHERE RESOURCE_ID = $resource_id";
 
         $result = $this->dbi->query($statement);

         return ($result == DB_OK) ? TRUE : FALSE;
      }
      
      
      function trackResourceVisit($params = null)
      {
         $fieldsArr = $this->resource_track_map_fields;

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

         $statement = "INSERT INTO $this->resource_track_tbl ($this->resource_track_fields) VALUES ( $values )";

         $result = $this->dbi->query($statement);

         return ($result == DB_OK) ? TRUE : FALSE;
      }
      
      
      function getResourceUrl($rid = null)
      {
         $stmt = "SELECT RESOURCE_LOCATION  FROM $this->resource_tbl WHERE   RESOURCE_ID = $rid";
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         $row = $result->fetchRow();
         
         return $row->RESOURCE_LOCATION;
      }

      
      function getResourceByCategory($catID = null)
      {
         $stmt = "SELECT RESOURCE_ID, RESOURCE_TITLE  FROM $this->resource_tbl WHERE   RESOURCE_CATEGORY = $catID";
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         while($row = $result->fetchRow())
         {
            $listArr[$row->RESOURCE_ID] = stripslashes($row->RESOURCE_TITLE);
         }

         return $listArr;
      }
      
      
      function getResourceInfo($rid = null)
      {
         $stmt = "SELECT $this->fields  FROM $this->resource_tbl WHERE RESOURCE_ID = $rid";
  
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         else {
            
          return  $row = $result->fetchRow();
         }
      }
      
      
      function deleteResource($rid = null)
      {
         $stmt = "DELETE FROM $this->resource_tbl WHERE RESOURCE_ID = $rid";
      	 
         $result = $this->dbi->query($stmt);
      	 
         return ($result == DB_OK) ? TRUE : FALSE;
      }
      
      
      function getNumOfResourceInCat($scatID = null)
      {
         $stmt = "SELECT Count(RESOURCE_ID) as NUM FROM $this->resource_tbl WHERE (RESOURCE_CATEGORY = $scatID)";
         
         $result = $this->dbi->query($stmt);
         
         return $row = $result->fetchRow();
      }
      
      
      function getTotalResourceNum()
      {
         $stmt = "SELECT Count(RESOURCE_ID) as NUM FROM $this->resource_tbl";
         
         $result = $this->dbi->query($stmt);
         
         return $row = $result->fetchRow();
      }
      
      
      function getNewResource($scatID = null, $timeLimit = null)
      {
         $stmt = "SELECT Count(RESOURCE_ID) as NUM FROM $this->resource_tbl WHERE $this->resource_tbl.CREATE_TS > $timeLimit AND $this->resource_tbl.RESOURCE_CATEGORY =$scatID";
         
         $result = $this->dbi->query($stmt);
         
         return $row = $result->fetchRow();
      
      }
      
      
      function getTopRankingList($rating = null)
      {
         $stmt = "SELECT RESOURCE_ID, RESOURCE_TITLE, RESOURCE_RATING, RESOURCE_ADDED_BY FROM $this->resource_tbl WHERE RESOURCE_RATING >= $rating";
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         $listArr = array();
         
         while($row = $result->fetchRow())
         {
            $listArr[] = $row;
         }
         return $listArr;    
      }
      
      
      function searchResource($params)
      {

         $tableList = array();

         $tableList[] = $this->resource_tbl;

         if( ($params['RESOURCE_CATEGORY']) != 0)
      	 {  
      	    $catObj = new IrmCategory($this->dbi);
      	    
      	    $subCatList = $catObj->getSubCategoryList($params['RESOURCE_CATEGORY']);
      	    
            $category = "($this->resource_tbl.RESOURCE_CATEGORY = $params[RESOURCE_CATEGORY]";
            
            if(!empty($subCatList))
            {
               foreach($subCatList as $key=>$value)
               {
                  $category = $category." OR $this->resource_tbl.RESOURCE_CATEGORY = $key";
               }
            }

            $category = $category.")";
         }

         if(($params['RESOURCE_RATING'])!=0)
         {
            $rating = " $this->resource_tbl.RESOURCE_RATING = $params[RESOURCE_RATING]";
         }
         
         if(!empty($params['RESOURCE_ADDED_BY']) !=0)
         {
            $addedBy = " $this->resource_tbl.RESOURCE_ADDED_BY = $params[RESOURCE_ADDED_BY]";
         }
         
         if( !empty($params['VISITOR_ID']) != 0)
         {
            $tableList[] = $this->resource_track_tbl;

            $visitedBy = " $this->resource_track_tbl.VISITOR_ID = $params[VISITOR_ID]" .
                         " AND $this->resource_track_tbl.RESOURCE_ID = $this->resource_tbl.RESOURCE_ID ";
         }
      
         if(!empty($params['KEYS']))
         {
            $tableList[] = $this->resource_keyword_tbl;

            $keywords = strtoupper($params['KEYS']);
         
            $keywordArray = explode(" ", $keywords);
         
            $keys = NULL;
            foreach($keywordArray as $key=>$value)
            {
               if( strcmp($value, 'AND') &&  strcmp($value, 'OR'))
               {
                  $keys  = $keys." $this->resource_keyword_tbl.KEYWORD = '$value' OR";
               }
            }

            $len = strlen($keys);
         
            $key = substr($keys,0, $len-2); 
            
            $finalKeyWord = "( $key  AND $this->resource_keyword_tbl.RESOURCE_ID = $this->resource_tbl.RESOURCE_ID )";

         }
    

         $tableStr = implode(',',  $tableList);

         $conditions = array(isset($finalKeyWord) ? $finalKeyWord : NULL, isset($category) ? $category : NULL, isset($rating) ? $rating : NULL, isset($addedBy) ? $addedBy : NULL, isset($visitedBy) ? $visitedBy : NULL);
         foreach($conditions as $cond)
         {
           if(!empty($cond)) {
              $condList[] = $cond;
           }
         }
         if (!isset($condList) || $condList == NULL){
            return isset($listAttr) ? $listAttr : NULL;
         }

         $condStr = implode(' and ' , $condList);

         $stmt = "SELECT distinct $this->resource_tbl.RESOURCE_ID,$this->resource_tbl.RESOURCE_TITLE, " .
                 "$this->resource_tbl.RESOURCE_RATING, $this->resource_tbl.RESOURCE_ADDED_BY " .
                 "FROM $tableStr WHERE $condStr";

         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         while($row = $result->fetchRow())
         {
            $listArr[] = $row;
         }
         return $listArr;

      }
      
      
      function getMostVisitedResource($limit = null)
      {
         $stmt = "SELECT RESOURCE_ID, count(RESOURCE_ID) AS ID FROM $this->resource_track_tbl GROUP BY RESOURCE_ID ORDER BY ID DESC LIMIT $limit";
      
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         
         $listArr = array();
         
         while($row = $result->fetchRow())
         {
            $listArr[] = $row;
         }
         return $listArr;    
      }
      
   }

?>
