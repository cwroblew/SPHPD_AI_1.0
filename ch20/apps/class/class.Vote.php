<?php

    class Vote
    {
        
        //Constructor takes the DBI object and the POLL ID as parameter. 
        function Vote($dbi = null, $pid = null)
        {
           $this->dbi = $dbi;           
           $this->vote_tbl = VOTE_TBL;
           $this->setPollID($pid);
        }
        
        function setPollID($pid)
        {
           if (!empty($pid))
           {
              $this->pid = $pid;	
           }	
           return $this->pid;
        }
        
        function addVote($vote, $pid = null)
        {
           $this->setPollID($pid);
           $curTime = mktime();
           
           // if single vote item
           if(! is_array($vote))
           {
              $stmt = "INSERT INTO $this->vote_tbl (POLL_ID, VOTE, VOTE_TS) " .
                      "VALUES ($this->pid, $vote, $curTime)";
              $result = $this->dbi->query($stmt);

           } else {
              foreach ($vote as $entry)
              {
 
                 $stmt = "INSERT INTO $this->vote_tbl (POLL_ID, VOTE, VOTE_TS) " .
                         "VALUES ($this->pid, $entry, $curTime)";
                 $result = $this->dbi->query($stmt);
              }

           }

           return ($result == DB_OK) ? TRUE : FALSE;
        }
        
        function getPollChoices($pid = null)
        {
           $this->setPollID($pid);
           $stmt = "SELECT DISTINCT(VOTE) AS CHOICE from $this->vote_tbl where POLL_ID = $this->pid";
           
           $result = $this->dbi->query($stmt);
           if ($result->numRows() <= 0)
           {
              return null;	
           }
           while ($row = $result->fetchRow())
           {
              $retArr[] = $row->CHOICE;	
           }
           
           return $retArr;
        }
        
        function getVoteCountByChoice($vote, $pid = null)
        {
           $this->setPollID($pid);
           $stmt = "SELECT COUNT(*) AS VOTE_COUNT from $this->vote_tbl where POLL_ID = $this->pid and VOTE = $vote";
           $result = $this->dbi->query($stmt);
           $row = $result->fetchRow();
           return $row->VOTE_COUNT;           	
        }
        
        function getTotalVoteCount($pid = null)
        {
           $this->setPollID($pid);
           $stmt = "SELECT COUNT(*) AS TOTAL_VOTE_COUNT from $this->vote_tbl where POLL_ID = $this->pid";
           $result = $this->dbi->query($stmt);
           $row = $result->fetchRow();
           return $row->TOTAL_VOTE_COUNT;	
        }
        
        
        
        function getSubscriptionStatus($frnd, $fid = null)
        {
           $frnd = $this->dbi->quote(addslashes($frnd));
           $fid = $this->setFormID($fid);
           $stmt = "SELECT SUBSCRIPTION FROM $this->subscr_tbl WHERE FRND_EMAIL = $frnd AND FRM_ID = $this->fid";
           $result = $this->dbi->query($stmt);
           if ($result->numRows() <= 0)
           {
              return null;	
           }
           $row = $result->fetchRow();
           return (!strcmp($row->SUBSCRIPTION, 'sub')) ? 1 : -1;
        }
    }

?>
