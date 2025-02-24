<?php


   class Theme
   {
      function Theme($dbi = null, $theme_id = null, $page = null)
      {

          global $USER_PREFERENCE_TBL;

          global $DEFAULT_THEME;

          //print_r($dbi); 
          $this->dbi = $dbi;

          $this->default_theme = $DEFAULT_THEME;

          $this->pref_tbl = $USER_PREFERENCE_TBL;

          if (!empty($theme_id))
          {
             $this->theme_id = $theme_id;
          }

          if (!empty($page))
          {
             $this->page = $page;
          }

      }

      function setPage($page = null)
      {
      	 $this->page = $page;
      }

      function setThemeID($theme_id = null)
      {
          if (!empty($theme_id))
          {
              $this->theme_id  = $theme_id;
          }

          return $this->theme_id;
      }

      function getThemeInfo($theme_id = null)
      {

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

      function addTheme($theme_id, $uid)
      {

         $qstr = $this->dbi->quote($theme_id);

         $statement = "INSERT INTO $this->pref_tbl (USER_ID, PREFERENCE_ID, VALUE) VALUES($uid, 1, $qstr)";


         $result = $this->dbi->query($statement);

         return ($result == DB_OK) ? TRUE : FALSE;

      }


      function updateTheme($theme_id, $uid)
      {

         $statement = "UPDATE $this->pref_tbl SET VALUE = '$theme_id' WHERE " .
                      "USER_ID = $uid AND PREFERENCE_ID = 1";

         $result = $this->dbi->query($statement);


         if ($result != null)
         {
            $this->theme_id = $theme_id;

            return TRUE;

         } else {

            return FALSE;

         }
      }

      function getUserTheme($uid)
      {

         $statement = "SELECT VALUE FROM $this->pref_tbl " .
                      "WHERE USER_ID=$uid AND PREFERENCE_ID = 1";
                      
         
         $result = $this->dbi->query($statement);
         
         if ($result->numRows() > 0)
         {
            $row = $result->fetchRow();
            return $row->VALUE;
         }

         return $this->default_theme;
      }

      function getAllThemes()
      {
         global $THEME_TBL;
         $statement = "SELECT * FROM $THEME_TBL";
         $result = $this->dbi->query($statement);
         $themeArr = array();
         while ($row = $result->fetchRow())
         {
            $themeArr[$row->THEME_ID] = $row->THEME_NAME;
         }
         return $themeArr;
      }


      function getLeftNavigation($dir = null)
      {
      	  $this->leftNavigationPage = sprintf("%s/%s_left_nav.html", $dir, isset($this->page) ? $this->page : NULL);

      	  if (! file_exists($this->leftNavigationPage))
      	  {
      	     $this->leftNavigationPage = sprintf("%s/default_left_nav.html", $dir);
      	  }

          $fp = fopen($this->leftNavigationPage, "r");
          if ($fp)
          {
             $contents = fread($fp, filesize($this->leftNavigationPage));
             fclose($fp);
          }


          return $contents;
      }
   }


?>
