<?php

   require_once "vote.conf";

   require_once VOTE_CLASS;
   
   class VoteMngr extends PHPApplication 
   {
     function run()
     {
         $this->setPollID();
         
         //creates an object of the Vote class with the request POLL ID.
         $this->_voteObj = new Vote($this->dbi, $this->_pollID);
         
         //adds vote if cookie is not set, otherwise shows poll result directly.
         (!empty($_COOKIE['VOTED_'.$this->_pollID])) ? $this->displayVoteResult() : $this->addVote();
     }
     
     function setPollID()
     {
         //shows error if POLL ID is not supplied, after setting it as a member variable.
         $this->_pollID = $_REQUEST['poll_id'];
         if (empty($this->_pollID))
         {
             $this->alert('REQUIRED_POLL_ID_MISSING');
             return;
         }
     }   
     
     function addVote()
     {
         //shows error if no vote is supplied.
         $vote = $_REQUEST['vote'];
         if (empty($vote))
         {
             $this->alert('VOTE_MISSING');
             return;
         }
         
         //sets cookie if vote addition is successful.
         if ($this->_voteObj->addVote($vote))
         {
            setcookie ('VOTED_'.$this->_pollID, base64_encode($this->_pollID), 
                        time() + COOKIE_EXPIRATION_TIME);  	
         }

         //shows error if vote additon fails
         else
         {
             $this->alert('VOTE_NOT_ADDED');
             return;
         }
         
         //shows vote result
         $this->displayVoteResult();
     }
     
     function displayVoteResult()
     {
         $template = new Template($this->getTemplateDir());
         
         //loads the template file based on the POLL ID.
         $template->set_file('fh', sprintf("%03d", $this->_pollID).".html");
         $template->set_block('fh', 'mainBlock', 'main');
         
         //retrieves poll analysis
         $pollChoices = $this->_voteObj->getPollChoices();
         

         //retrieves total votes for the poll and sets in template
         $template->set_var('TOTAL_VOTES', $totalVoteCnt = $this->_voteObj->getTotalVoteCount());
         
         //retrieves vote count for each choice and sets to the template
         if(! empty($pollChoices))
         {
            foreach($pollChoices as $thisChoice)
            {
                $countArr[$thisChoice] = $voteCnt = $this->_voteObj->getVoteCountByChoice($thisChoice);
                $percentageArr[$thisChoice] = intval(($voteCnt*100)/$totalVoteCnt);
            }
         
            $numOfChoicesPerPoll = $GLOBALS['choicesPerPoll'];
            $totalChoices = $numOfChoicesPerPoll[$this->_pollID];
            for ($i=1; $i<=$totalChoices; $i++)
            {
               $template->set_var(
                               array(
                                      $i.'_VOTE_COUNT'   => $countArr[$i],
                                      $i.'_VOTE_PERCENT' => $percentageArr[$i]
                                    )
                              );	
            }
         }
         
         $template->parse('main', 'mainBlock', false);
         $template->pparse('output', 'fh');
     }
     
     
   }//class
   
   $thisApp = new VoteMngr(
                             array('app_name'              =>  $APPLICATION_NAME,
                                   'app_version'           => '1.0.0',
                                   'app_type'              => 'WEB',
                                   'app_auto_connect'      => TRUE,
                                   'app_auto_authorize'    => FALSE,
                                   'app_auto_chk_session'  => FALSE,
                                   'app_debugger'          => $OFF,
                                   'app_db_url'            => $VOTE_DB_URL,
                                   )
                          );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
