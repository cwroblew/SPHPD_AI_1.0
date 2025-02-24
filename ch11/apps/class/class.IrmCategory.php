<?php

   class IrmCategory
   {
      function IrmCategory($dbi = null)
      {
         global $IRM_CATEGORY_TBL;
         
         $this->category_tbl = $IRM_CATEGORY_TBL;
         
         $this->dbi = $dbi;         
      }
      

      function getCategoryList()
      {
         $listArr = array();
         
         $stmt = "SELECT CATEGORY_ID, CATEGORY_NAME FROM $this->category_tbl WHERE P_CATEGORY_ID = 0";
         
         $result = $this->dbi->query($stmt);
         
         if(!empty($result))
         {
            while($row = $result->fetchRow())
            {
               $listArr[$row->CATEGORY_ID] = stripslashes($row->CATEGORY_NAME);
            }
         } else {
         
            $listArr = null;
         }

         return $listArr;
      }
      
      
      function getSubCategoryList($p_id = null)
      {
         $listArr = array();

         $stmt = "SELECT CATEGORY_ID, CATEGORY_NAME FROM $this->category_tbl WHERE P_CATEGORY_ID = $p_id";
          
         $result = $this->dbi->query($stmt);
         
         if($result->numRows() == 0)
         {
            return null;
         }
         
         while($row = $result->fetchRow())
         {
            $listArr[$row->CATEGORY_ID] = stripslashes($row->CATEGORY_NAME);
         }

         return $listArr;
      }
      
      
      function addCategory($name = null, $pcat = null, $uid = null)
      {
         $cat_name = $this->dbi->quote(addslashes($name));
         
         $now = mktime();
      
         $stmt = "INSERT INTO $this->category_tbl (CATEGORY_NAME, P_CATEGORY_ID, CREATED_BY, CREATE_TS) VALUES ($cat_name , $pcat, $uid, $now)";
                                                    
         $result = $this->dbi->query($stmt);
         
         return ($result == DB_OK) ? TRUE : FALSE;
      }
      
      
      function existInList($catName = null)
      {
      	 $catName = $this->dbi->quote(addslashes($catName));
      	 
         $stmt = "SELECT CATEGORY_ID FROM $this->category_tbl WHERE CATEGORY_NAME = $catName";
         
         //echo $stmt;
        
         $result = $this->dbi->query($stmt);
         
         if($result->numRows() <= 0)
         {
            return 0;
         
         } else {
         
            $row = $result->fetchRow();
            
            return $row->CATEGORY_ID;
         }
      }
      
      
      function getCategoryName($catID = null)
      {
      	 $stmt = "SELECT CATEGORY_NAME FROM $this->category_tbl WHERE CATEGORY_ID = $catID";
      	 
      	 //echo $stmt;
         
         $result = $this->dbi->query($stmt);
         
         if($result->numRows() <= 0)
         {
            return null;
         
         } else {
         
            $row = $result->fetchRow();
            
            return stripslashes($row->CATEGORY_NAME);
         }
      }
      
      
      function getParentCategory($catID = null)
      {
         $stmt = "SELECT P_CATEGORY_ID FROM $this->category_tbl WHERE CATEGORY_ID = $catID";
         
         $result = $this->dbi->query($stmt);
         
         if($result->numRows() <= 0)
         {
            return null;
         
         } else {
         
            $row = $result->fetchRow();
            
            return $row->P_CATEGORY_ID;
         }
      }
      
      
      function deleteCategory($catID = null)
      {
         $stmt = "DELETE FROM $this->category_tbl WHERE CATEGORY_ID = $catID";
      	 
         $result = $this->dbi->query($stmt);
      	 
         return ($result == DB_OK) ? TRUE : FALSE;       
      }
      
      
      function modifyCategory($catID, $newcategory, $pid, $uid)
      {
         $cat_name = $this->dbi->quote(addslashes($newcategory));
         
         $now = mktime(); 
         
         $statement = " UPDATE $this->category_tbl SET CATEGORY_NAME=$cat_name, P_CATEGORY_ID=$pid, CREATED_BY=$uid, CREATE_TS=$now WHERE CATEGORY_ID = $catID";
         
         $result = $this->dbi->query($statement);

         return ($result == DB_OK) ? TRUE : FALSE;
      }
   }

?>