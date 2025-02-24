<?php

   class Category
   {

      function Category($dbi = null, $cid = null)
      {
         global $CONTACT_CATEGORY_TBL;         
         

         $this->dbi = $dbi;
                  
         $this->cat_tbl = $CONTACT_CATEGORY_TBL;
         
         $this->std_fields = array( 
                                     'CAT_ID'         =>   'number',
                                     'CAT_NAME'       =>   'text',
                                     'CAT_DESC'       =>   'text',
                                     'CAT_PARENT'     =>   'number'
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
              $this->cid = isset($this->cid) ? $this->cid : null;
          }
          
          return $this->cid;
      }
      
      function getCategoryName($cid = null)
      {
      	  
      	  $this->loadCatInfo($cid);
      	  return $this->CAT_NAME;
      }
      
      function getCategoryParent($cid = null)
      {
      	  
      	  $this->loadCatInfo($cid);
      	  return $this->CAT_PARENT;
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
         return $this->getCategoryIDbyName($params['CAT_NAME']);
         
      }
      
      function getParentCategories()
      {
         $stmt = "SELECT $this->fields FROM $this->cat_tbl WHERE CAT_PARENT = 0 ORDER BY CAT_NAME";
         
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
      
      function getSubCategories($parent)
      {
         $stmt = "SELECT $this->fields FROM $this->cat_tbl WHERE CAT_PARENT = $parent ORDER BY CAT_NAME";
         
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
         
         
         
         $result = $this->dbi->query($stmt);
         return ($result == DB_OK) ? true :  false;
      }
      
      function hasChild($cid = null)
      {
      	$this->setCatID($cid);
      	$stmt = "SELECT COUNT(*) AS PARENT_COUNT FROM $this->cat_tbl WHERE CAT_PARENT = $this->cid";
      	$result = $this->dbi->query($stmt);
      	$row = $result->fetchRow();
      	return $row->PARENT_COUNT;
      }
      
      function getParentOf($cid = null)
      {
      	$this->setCatID($cid);

        if (empty($this->cid)) return FALSE; 
        
      	$stmt = "SELECT CAT_PARENT FROM $this->cat_tbl WHERE CAT_ID = $this->cid";
      	
      	
      	$result = $this->dbi->query($stmt);
      	if ($result->numRows() <= 0)
      	{
      	   return null;	
      	}
      	$row = $result->fetchRow();
      	return $row->CAT_PARENT;
      }
      
      function replaceParentCat($old, $new)
      {
        $stmt = "UPDATE $this->cat_tbl SET CAT_PARENT = $new WHERE CAT_PARENT = $old";
        $result = $this->dbi->query($stmt);
        return ($result == DB_OK) ? true : false;
      }
}
