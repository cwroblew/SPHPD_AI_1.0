<?php

   class EcampaignTrack
   {
	function EcampaignTrack($dbi = null)
      	{
           global $ECAMPAIGN_TRACK_TBL;

           $this->track_tbl = $ECAMPAIGN_TRACK_TBL;

           $this->dbi = $dbi;

      	}

      	function storeTrack($uid = null, $cid = null, $urlid = null)
      	{
	       $track_ts = time();
           $statement = "INSERT INTO $this->track_tbl (USER_ID, CAMP_ID, URL_ID, TRACK_TS) VALUES($uid, $cid, $urlid, $track_ts)";
		   $result = $this->dbi->query($statement);

      	   if ($result != DB_OK)
      	   {
      	      return false;
      	   }
      	   return true;
      	}
      	
    }

?>