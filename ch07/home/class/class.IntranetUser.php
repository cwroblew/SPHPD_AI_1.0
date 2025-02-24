<?php

   class IntranetUser
   {
      function IntranetUser($dbi = null, $sid = null)
      {
          
          global $USER_PREFERENCE_TBL,
                 $USER_DETAILS_TBL;

          $this->dbi = $dbi;

          $this->user_details_tbl = $USER_DETAILS_TBL;
          $this->user_pref_tbl    = $USER_PREFERENCE_TBL;

          if (!empty($sid))
          {
             $this->sid = $sid;
          }

      }

      function setIntranetUserID($sid = null)
      {

          if (!empty($sid))
          {
              $this->sid  = $sid;
          }

          return $this->sid;
      }

      function getContactInfo($sid = null)
      {
          // see getEnrollmentRecord
            $this->setIntranetUserID($sid);

            $statement = "SELECT * from $this->user_details_tbl WHERE USER_ID = $this->sid";            
            $result = $this->dbi->query($statement);
            $row = $result->fetchRow();

            $this->contactInfo = $row;
            return $row;

         $this->dbi->free();
      }


      function getName()
      {
         if (!isset($this->contactInfo))
         {
             $this->getContactInfo();
         }
         return sprintf("%s %s",
                                ucfirst($this->contactInfo->FIRST),
                                ucfirst($this->contactInfo->LAST)
                       );
      }

      function getPreferences($uid)
      {

         $statement = "SELECT USER_ID, PREFERENCE_ID, VALUE FROM $this->user_pref_tbl WHERE USER_ID = $uid";

         $result = $this->dbi->query($statement);
         $pref = array();

         while ($row = $result->fetchRow())
         {
           if ($row->PREFERENCE_ID==1) $pref['theme'] = $row->VALUE;
           if ($row->PREFERENCE_ID==2) $pref['autotip'] = $row->VALUE;
         }

         return $pref;
      }

      function updateAutoTip($uid, $tip)
      {

          $qstr = $this->dbi->quote($tip);

         $statement = "UPDATE $this->user_pref_tbl SET VALUE=$qstr WHERE ".
                      " USER_ID = $uid AND PREFERENCE_ID=2";

         $result = $this->dbi->query($statement);
      }

      function addAutoTip( $uid, $tip)
      {

         $qstr = $this->dbi->quote($tip);

         $statement = "INSERT INTO $this->user_pref_tbl (USER_ID, PREFERENCE_ID, VALUE) VALUES($uid, 2, $qstr)";


         $result = $this->dbi->query($statement);

         return ($result == DB_OK) ? TRUE : FALSE;

      }

   }
?>
