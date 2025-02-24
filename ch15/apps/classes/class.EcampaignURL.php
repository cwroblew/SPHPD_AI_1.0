<?php

   class EcampaignURL
   {
      function EcampaignURL($dbi = null)
      {
          global $ECAMPAIGN_URL_TBL;
          global $HOME_URL;

          $this->url_tbl = $ECAMPAIGN_URL_TBL;

          $this->dbi = $dbi;

          $this->home_url = $HOME_URL;
      }

      function addURL($name = null, $url = null)
      {

         $name = $this->dbi->quote(addslashes($name));
         $url  = $this->dbi->quote($url);

         $stmt = "INSERT INTO $this->url_tbl (NAME, URL) ".
                 "VALUES($name, $url)";

         $result = $this->dbi->query($stmt);

         return ($result != DB_OK) ? FALSE : TRUE;
      }

      function getURL($url_id)
      {
         $stmt = "SELECT URL FROM $this->url_tbl ".
                 "WHERE URL_ID = $url_id";

         $result = $this->dbi->query($stmt);

         if ($result->numRows() <= 0)
         {
            return $this->home_url;
         }

         $row = $result->fetchRow();

         return $row->URL;

      }

      function getURLInfo($url_id = null)
      {
          $stmt = "SELECT URL_ID, NAME, URL  FROM $this->url_tbl WHERE URL_ID = $url_id";

          $result = $this->dbi->query($stmt);

          $row = $result->fetchRow();

          $arr = array( 'URL_ID' => $row->URL_ID,
                        'NAME'   => stripslashes($row->NAME),
                        'URL'    => $row->URL
                       );
          return $arr;
      }

      function modifyURL($url_id , $name = null, $url = null)
      {

          $name = $this->dbi->quote(addslashes($name));
          $url  = $this->dbi->quote($url);

          $stmt = "UPDATE $this->url_tbl SET NAME = $name, URL= $url".
                       " WHERE URL_ID = $url_id";

          $result = $this->dbi->query($stmt);

          return ($result != DB_OK) ? FALSE : TRUE;

      }

      function getURLList()
      {

          $listArr = array();

          $stmt = "SELECT URL_ID, NAME FROM $this->url_tbl";

          $result = $this->dbi->query($stmt);

          if ($result->numRows() <=0) return $listArr;

          while($row = $result->fetchRow())
          {
             $listArr[$row->URL_ID] = stripslashes($row->NAME);
          }

          return $listArr;

      }

      function getURLLocationList()
      {

          $listArr = array();

          $stmt = "SELECT URL_ID, URL FROM $this->url_tbl";

          $result = $this->dbi->query($stmt);

          if ($result->numRows() <=0) return $listArr;

          while($row = $result->fetchRow())
          {
              $listArr[$row->URL_ID] = $row->URL;
          }

          return $listArr;
      }

      function deleteURL( $url_id = null)
      {

          $stmt = "DELETE FROM $this->url_tbl WHERE URL_ID= $url_id";

          $result = $this->dbi->query($stmt);

          return ($result != DB_OK) ? FALSE : TRUE;
      }
   }

?>