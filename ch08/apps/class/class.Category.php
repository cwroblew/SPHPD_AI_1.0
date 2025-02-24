<?php

   class Category
   {

      function Category($dbi = null, $cid = null)
      {
         global $LD_CATEGORY_TBL;
         global $LD_DOC_TBL;
         global $LD_CAT_PUB_TBL;
         global $LD_CAT_VIEW_TBL;

         $this->dbi = $dbi;
                  
         $this->cat_tbl = $LD_CATEGORY_TBL;
         $this->doc_tbl = $LD_DOC_TBL;
         $this->cat_pub_tbl = $LD_CAT_PUB_TBL;
         $this->cat_view_tbl = $LD_CAT_VIEW_TBL;

         $this->std_fields = array( 
                                     'CAT_ID'         =>   'number',
                                     'CAT_NAME'       =>   'text',
                                     'CAT_DESC'       =>   'text',
                                     'CAT_ORDER'      =>   'number',
                        );
         

         $this->fields = implode(',', array_keys($this->std_fields));

         $this->setCatID($cid);
      }
      
      function loadCatInfo($cid = null)
      {
          $this->setCatID($cid);
          
          $stmt = "SELECT $this->fields FROM $this->cat_tbl "
                 ."WHERE CAT_ID = $this->cid";
                 
          
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
      
      function getCategoryIDbyName($name)
      {
         $name = $this->dbi->quote(addslashes($name));
         $stmt = "SELECT CAT_ID FROM $this->cat_tbl WHERE CAT_NAME = $name";
         
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $row = $result->fetchRow();
         return $row->CAT_ID;
         
      }
      
      function setCatID($cid = null)
      {
          if (!empty($cid))
          {
              $this->cid  = $cid;
          }
          else
          {
              $this->cid = isset($this->cid) ? $this->cid : NULL;	
          }
          
          return $this->cid;
      }
      
      function getCategoryName($cid = null)
      {
      	  $this->loadCatInfo($cid);
      	  return $this->CAT_NAME;
      }
      
      function getCategoryOrder($cid = null)
      {
      	  $this->loadCatInfo($cid);
      	  return $this->CAT_ORDER;
      }
      
      function getCategoryDesc($cid = null)
      {
      	  $this->loadCatInfo($cid);
      	  return $this->CAT_DESC;
      }
      
      function addCategory($params = null)
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

         $statement = "INSERT INTO $this->cat_tbl ($this->fields) VALUES ( $values )";
         
         $result = $this->dbi->query($statement);
         
         if ($result != DB_OK)
         {
            return false;	
         }
         $catName = $this->dbi->quote(addslashes($params['CAT_NAME']));
         
         $stmt = "SELECT CAT_ID FROM $this->cat_tbl WHERE CAT_NAME = $catName AND CAT_ORDER = $params[CAT_ORDER]";
         //echo $stmt;
         
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows()< 0)
         {
            
            return null;	
         }
         $row = $result->fetchRow();
         return $row->CAT_ID;
      }
      
      function getCategories()
      {
         $stmt = "SELECT $this->fields from $this->cat_tbl ORDER BY CAT_ORDER DESC";
         $result = $this->dbi->query($stmt);
         if ($result->numRows() <= 0)
         {
            return null;	
         }
         $retArr = array();
         while ($row = $result->fetchRow())
         {
            $retArr[$row->CAT_ID] = $row->CAT_NAME; 	
         }
         return $retArr;
      }
      
      function deleteCategory($cid = null)
      {
         $this->setCatID($cid);
         $stmt = "DELETE FROM $this->cat_tbl WHERE CAT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      function deleteDocesByCatID($cid = null)
      {
         $this->setCatID($cid);
         $stmt = "DELETE FROM $this->doc_tbl WHERE CAT_ID = $this->cid";
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true : false;
      }
      
      function modifyCategory($params = null)
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
         
         $stmt = "UPDATE $this->cat_tbl SET $keyValue WHERE CAT_ID = $params[CAT_ID]";
         //echo $stmt;
         
         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true :  false;
      }
      
      function getPublishers($cid = null)
      {
      	$this->setCatID($cid);
      	$stmt = "SELECT PUBLISHER_ID FROM $this->cat_pub_tbl WHERE CAT_ID = $this->cid";
      	//echo $stmt;
      	$result = $this->dbi->query($stmt);
      	if ($result->numRows() <= 0)
      	{
      	    return null;	
      	}
      	$retArr = array();
      	while($row = $result->fetchRow())
      	{
      	   $retArr[] = $row->PUBLISHER_ID;	
      	}
      	return $retArr;
      }
      
      function getViewers($cid = null)
      {
      	$this->setCatID($cid);
      	$stmt = "SELECT VIEWER_ID FROM $this->cat_view_tbl WHERE CAT_ID = $this->cid";
      	//echo $stmt;
      	$result = $this->dbi->query($stmt);
      	if ($result->numRows() <= 0)
      	{
      	    return null;	
      	}
      	$retArr = array();
      	while($row = $result->fetchRow())
      	{
      	   $retArr[] = $row->VIEWER_ID;	
      	}
      	return $retArr;
      }
      
      function addCategoryPublishers($cid = null, $publishers)
      {
        $this->setCatID($cid);
        foreach($publishers as $pub_id)
        {
           $stmt = "INSERT INTO $this->cat_pub_tbl(CAT_ID, PUBLISHER_ID) VALUES($this->cid, $pub_id)";
           $result = $this->dbi->query($stmt);	
        }
      }
      
      function addCategoryViewers($cid = null, $viewers)
      {
        $this->setCatID($cid);
        foreach($viewers as $view_id)
        {
           $stmt = "INSERT INTO $this->cat_view_tbl(CAT_ID, VIEWER_ID) VALUES($this->cid, $view_id)";
           $result = $this->dbi->query($stmt);	
        }
      }
      
      function deleteCategoryViewers($cid = null)
      {
      	$this->setCatID($cid);
        $stmt = "DELETE FROM $this->cat_view_tbl WHERE CAT_ID = $this->cid";
        $result = $this->dbi->query($stmt);
      }
      
      function deleteCategoryPublishers($cid = null)
      {
      	$this->setCatID($cid);
        $stmt = "DELETE FROM $this->cat_pub_tbl WHERE CAT_ID = $this->cid";
        $result = $this->dbi->query($stmt);
      }
      
      function isViewable($cid = null, $vid)
      {
        $this->setCatID($cid);
        $stmt = "SELECT VIEWER_ID FROM $this->cat_view_tbl WHERE CAT_ID = $this->cid AND VIEWER_ID = $vid OR CAT_ID = $this->cid AND VIEWER_ID = 0";
        //echo $stmt;
        $result = $this->dbi->query($stmt);
        return $result->numRows();
      }
      
      function isPublishable($cid = null, $pid)
      {
        $this->setCatID($cid);
        $stmt = "SELECT PUBLISHER_ID FROM $this->cat_pub_tbl WHERE CAT_ID = $this->cid AND PUBLISHER_ID = $pid OR CAT_ID = $this->cid AND PUBLISHER_ID = 0";
        $result = $this->dbi->query($stmt);
        return $result->numRows();
      }
      
      function getHighestOrder()
      {
         $stmt = "SELECT MAX(CAT_ORDER) AS MAX_ORDER FROM $this->cat_tbl";
         $result = $this->dbi->query($stmt);
         $row = $result->fetchRow();
         return $row->MAX_ORDER; 
      }
      
      function updateCategoryOrders($orderArr)
      {
         while(list($cid, $newOrder) = each($orderArr))	
         {
            $stmt = "UPDATE $this->cat_tbl SET CAT_ORDER = -1 WHERE CAT_ORDER = $newOrder;";
            $result = $this->dbi->query($stmt);
            $stmt = "UPDATE $this->cat_tbl SET CAT_ORDER = $newOrder WHERE CAT_ID = $cid;";
            $result = $this->dbi->query($stmt);
         }
         return ($result == DB_OK) ? true : false;
      }
      
      
      
      
      
}