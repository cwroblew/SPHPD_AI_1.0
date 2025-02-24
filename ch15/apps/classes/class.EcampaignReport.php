<?php


    class EcampaignReport
    {
       function EcampaignReport($dbi = null, $campaign_id = null)
       {

          global $ECAMPAIGN_TBL, $ECAMPAIGN_TRACK_TBL, $ECAMPAIGN_UNSUB_TBL, $ECAMPAIGN_BOUNCED_TBL;
                 

          $this->dbi                     = $dbi;
          $this->campaign_id                 = $campaign_id;
          
          $this->ecampaign_tbl           = $ECAMPAIGN_TBL;
          $this->track_tbl               = $ECAMPAIGN_TRACK_TBL;
          $this->unsub_tbl               = $ECAMPAIGN_UNSUB_TBL;
          $this->bounced_tbl             = $ECAMPAIGN_BOUNCED_TBL;
       }

       function setEcampaignCampaignID($campaign_id = null)
       {
          if (!empty($campaign_id))
          {
               $this->campaign_id  = $campaign_id;
          }
          
          return $this->campaign_id;
          
       }
       
       function getURLResponse($campaign_id = null, $uniqueVal = null, $orderVal = null, $descVal = null)
       {

          $this->setEcampaignCampaignID($campaign_id);

          $stmt = "SELECT URL_ID, COUNT($uniqueVal USER_ID) AS CNT " .
                  "FROM $this->track_tbl ".
                  "WHERE CAMP_ID = $this->campaign_id ".
                  "GROUP BY URL_ID ".
                  "ORDER BY $orderVal $descVal";
          
          $result = $this->dbi->query($stmt);;

          if ($result != null)
          {
             $urlrespArr = array();
             while ($row = $result->fetchRow())
             {
                $urlrespArr[$row->URL_ID] = $row->CNT;   
             }
             return $urlrespArr;
          }

          return 0;
       }
       
       function getUnsubResponse($campaign_id = null)
       {
          $this->setEcampaignCampaignID($campaign_id);
          
          $stmt = "SELECT REC_ID FROM $this->unsub_tbl WHERE CAMPAIGN_ID = $campaign_id";
          
          
          $result = $this->dbi->query($stmt);
          
          if ($result != null)
          {
          	 return $result->numRows();
          }
          
          return 0;
       }
       
       function getBounceResponse($campaign_id = null)
       {
       	  $this->setEcampaignCampaignID($campaign_id);
          
          $stmt = "SELECT REC_ID ".
                  "FROM $this->bounced_tbl ".
                  "WHERE CAMPAIGN_ID = $campaign_id";
          
          $result = $this->dbi->query($stmt);
          
          if ($result != null)
          {
          	 
          	 return $result->numRows();
          }
          
          return 0;
       }

       
    }

?>
