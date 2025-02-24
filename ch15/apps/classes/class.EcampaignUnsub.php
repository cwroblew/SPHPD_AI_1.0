<?php

   class EcampaignUnsub
   {
	function EcampaignUnsub($dbi = null)
      	{
           global $ECAMPAIGN_UNSUB_TBL;

           $this->unsub_tbl = $ECAMPAIGN_UNSUB_TBL;

           $this->dbi = $dbi;

      	}

      	function storeUnsub($uid = null, $lid = null, $cid = null)
      	{
	       $unsub_ts = time();

           $statement = "INSERT INTO $this->unsub_tbl (REC_ID, LIST_ID, CAMPAIGN_ID, UNSUB_TS) VALUES($uid, $lid, $cid, $unsub_ts)";
           
      	   $result = $this->dbi->query($statement);

      	   if ($result != DB_OK)
      	   {
      	      return false;
      	   }
      	   return true;
      	}

    }

?>
