<?php

   class EcampaignCampaign
   {
      function EcampaignCampaign($dbi = null, $cid = null)
      {
          global $ECAMPAIGN_TBL;

          $this->ecampaign_tbl  = $ECAMPAIGN_TBL;


          $this->dbi = $dbi;

	      $this->setCampaignID($cid);

      }

     function setCampaignID($cid = null)
      {
          if (!empty($cid))
          {
              $this->cid  = $cid;
          }
          return isset($this->cid) ? $this->cid : NULL;
      }

      function getEcampaignInfo($cid = null)
      {

         $fields   = array('CAMPAIGN_ID',
                           'NAME',
                           'LIST_ID',
                           'MSG_ID'
                           );

         $fieldStr = implode(',', $fields);

         $this->setCampaignID($cid);

         $stmt   = "SELECT $fieldStr FROM $this->ecampaign_tbl " .
                   "WHERE CAMPAIGN_ID = $this->cid";


         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             $this->ECAMPAIGN_ID  = $row->CAMPAIGN_ID;
             $this->NAME          = stripslashes($row->NAME);
             $this->MSG_ID       = $row->MSG_ID;
             //echo $row->LIST_ID;
             $this->LIST_ID      = $row->LIST_ID;
             return TRUE;
         }

         return FALSE;
      }

      function getStatus($cid = null)
      {

          $this->setCampaignID($cid);

          $stmt = "SELECT STATUS from $this->ecampaign_tbl " .
                  "WHERE CAMPAIGN_ID = $this->cid";

          $result = $this->dbi->query($stmt);

          if ($result != null)
          {
             $row = $result->fetchRow();
             return $row->STATUS;
          }

          return null;
      }

      function setStatus($status = null, $cid = null)
      {
          $this->setCampaignID($cid);

          $stmt = "UPDATE $this->ecampaign_tbl SET STATUS = $status ".
                  "WHERE CAMPAIGN_ID = $this->cid";


          $result = $this->dbi->query($stmt);

          if ($result == DB_OK)
          {
              return TRUE;
          }

          return FALSE;
      }



      function getListID($ecampaign_id = null)
      {
         $this->setCampaignID($ecampaign_id);
         return $this->LIST_ID;
      }

      function getMessageID($ecampaign_id = null)
      {
         $this->setCampaignID($ecampaign_id);
         return $this->MSG_ID;
      }

      function getEcampaignID()
      {
         return $this->ECAMPAIGN_ID;
      }

      function getCampaignInfo($cid = null)
      {

      	$this->setCampaignID($cid);

      	$fieldsArr = array(
      	        	'NAME' => 'text',
      	        	'LIST_ID' => 'number',
      	        	'MSG_ID' => 'number');

      	$fields = implode(",", array_keys($fieldsArr));


      	$statement = "SELECT $fields FROM $this->ecampaign_tbl " .
      	             "WHERE CAMPAIGN_ID = $this->cid";

      	$result = $this->dbi->query($statement);

        $retArray = array();

      	if ($result == null)
      	{
      	   return $retArray;
      	}

        $row = $result->fetchRow();

      	while(list($k, $v) = each ($fieldsArr))
      	{
            if (!strcmp($v, 'text'))
            {
            	$retArray[$k] = stripslashes($row->$k);
            } else {
                $retArray[$k] = $row->$k;
            }
        }

        return $retArray;
      }


   function getAvailableCampaigns()
   {
       $statement = "SELECT CAMPAIGN_ID, NAME FROM $this->ecampaign_tbl";
       $result = $this->dbi->query($statement);
       $cmpgArr = array();
       while ($row = $result->fetchRow())
       {
          	$cmpgArr[$row->CAMPAIGN_ID] = stripslashes($row->NAME);
       }
       return $cmpgArr;
   }

   function addCampaign($params = null)
   {

      	$fieldsArr = array(
      	        	'NAME' => 'text',
      	        	'LIST_ID' => 'number',
      	        	'MSG_ID' => 'number');

      	$fields = implode(",", array_keys($fieldsArr));

      	$valueList = array();

      	while(list($k, $v) = each ($fieldsArr))
      	{
            if (!strcmp($v, 'text'))
            {
            	$valueList[] = $this->dbi->quote(addslashes($params[$k]));
            } else {
                $valueList[] = $params[$k];
            }
        }

        $values = implode(',', $valueList);
      	$statement = "INSERT INTO $this->ecampaign_tbl($fields) VALUES($values)";


       	$result = $this->dbi->query($statement);

      	return ($result != DB_OK) ? false : true;
      	
  }


   function deleteCampaign($cid = null)
   {

	$this->setCampaignID($cid);



	$statement = "DELETE FROM $this->ecampaign_tbl ".
		     "WHERE  CAMPAIGN_ID = $this->cid";

        $result = $this->dbi->query($statement);
      	if ($result != DB_OK)
      	{
      	   return false;
      	}
      	else
      	{
      	   return true;
      	}

  }


   function modifyCampaign($params = null )
   {

         $this->setCampaignID($params['CAMPAIGN_ID']);

         $fieldsArr = array(
      	        	'NAME' => 'text',
      	        	'LIST_ID' => 'number',
      	        	'MSG_ID' => 'number');

      	$fields = implode(",", array_keys($fieldsArr));

      	$valueList = array();

      	while(list($k, $v) = each ($fieldsArr))
      	{
            if (!strcmp($v, 'text'))
            {
            	$valueList[] = $k .'='. $this->dbi->quote(addslashes($params[$k]));
            } else {
                $valueList[] = $k .'='.$params[$k];
            }
        }
        
        $keyValues = implode(', ', $valueList);


        $statement = "UPDATE $this->ecampaign_tbl ".
                     "SET $keyValues ".
                     "WHERE CAMPAIGN_ID = $this->cid";



        $result = $this->dbi->query($statement);

      	if ($result != DB_OK)
      	{
      	   return false;
      	}
      	else
      	{
      	   return true;
      	}

   }



}//class

?>