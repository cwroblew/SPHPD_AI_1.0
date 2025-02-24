<?php

   require_once 'ecampaign.conf';

   require_once $ECAMPAIGN_LIST_CLASS;
   require_once $ECAMPAIGN_URL_CLASS;
   require_once $ECAMPAIGN_MESSAGE_CLASS;
   require_once $ECAMPAIGN_CAMPAIGN_CLASS;
   //require_once $ECAMPAIGN_CLASS;

   class ecampaignExecManager extends PHPApplication {

      function run()
      {

          // At this point user is authorized

          $this->executeCampaign();
     }

      function executeCampaign()
      {
         global $ECAMPAIGN_MNGR,
                $MAX_WAIT_PER_DELIVERY,
                $XMAILER_ID,
                $MAX_DELIVERY_AT_A_TIME,
                $ECAMPAIGN_REDIR_MNGR;

         global $ECAMPAIGN_EXECUTION_TEMPLATE,
                $REL_APP_PATH,
                $ECAMPAIGN_EXEC_MNGR,
                $REL_APP_PATH,
                $MAIL_TEMPLATE,
                $SECRET,
                $ECAMPAIGN_UNSUB_MNGR,
		        $FROM_HEADER,
		        $REPLY_HEADER,
		        $PRIORITY_HEADER,
		        $SUBJECT_HEADER;		     
         
                
         $loop = $this->getRequestField('loop');
         $lastrow = $this->getRequestField('lastrow');
         $total = $this->getRequestField('total');
         $ecampaign_id = $this->getRequestField('ecampaign_id');
		        


         $this->emptyError($ecampaign_id, 'RUN_ECAMPAIGN_ID_MISSING');
         $thisEcampaign = new EcampaignCampaign($this->dbi, $ecampaign_id);

         $status = $thisEcampaign->getEcampaignInfo();

         if (!$status)
         {
            $this->alert('RUN_ECAMPAIGN_ID_MISSING');
         }

         $lastrow     = $thisEcampaign->getStatus();
         if ($lastrow == -1)
         {
            $this->alert('CAMPAIGN_ALREADY_EXECUTED');
            return;
         }

         $messageID   = $thisEcampaign->getMessageID();
	     $listID      = $thisEcampaign->getListID();
         

         $server   = $this->getServer();
         $appPath  = $this->getAppPath();

         $listObj = new EcampaignList($this->dbi, $listID);
         if (empty($lastrow))
         {
            

            $cdburl = $listObj->getClientDBURL();            
            if (!$cdburl)
            {
                $this->alert('LIST_MISSING');
                return;
            }

            list($key, $value) = each($cdburl);

            $client_dbi = new DBI($value);
            if(!$client_dbi->connected)
            {
               $this->show_status($this->getMessage('CONNECT_DATABASE_FALIED'),
                                    $ECAMPAIGN_MNGR);
               return;	
            }

            $total = $listObj->prepareLocalList($listID, $client_dbi, $key);

	        if ($total == 0)
	        {
               $this->alert('EMPTY_LIST_ERROR');
	        }

         }


         // Ready to execute
         


         $targetData = $listObj->getTargetData($lastrow, $MAX_DELIVERY_AT_A_TIME);

         //$this->dump_array($targetData);

         //Load the survey form

         $messageObj    = new EcampaignMessage($this->dbi);
         $messageRow      = $messageObj->getEcampaignMessageInfo($messageID);
         if (!$messageRow)
         {
                $this->alert('MESSAGE_MISSING');
                return;
         }
         $mailBody = $messageRow->BODY;

         $emailTemplate = new Template($this->getTemplateDir());
         $emailTemplate->set_file('fh', $MAIL_TEMPLATE);
         $emailTemplate->set_block('fh', 'mainBlock', 'mblock');
         $emailTemplate->set_var('BODY',$mailBody);
         $emailTemplate->parse('mblock', 'mainBlock');


         $urlObj = new EcampaignURL($this->dbi);
         $urlArr = $urlObj->getURLList();
         
         $headerArr = $messageObj->getEcampaignHeaderInfo($messageID);

         $headers  = "From: " . stripslashes($headerArr[$FROM_HEADER]) . "\r\n";
         $headers .= "Reply-To: ". stripslashes($headerArr[$REPLY_HEADER]) . "\r\n";
         $headers .= "X-Priority: ". $headerArr[$PRIORITY_HEADER] . "\r\n";
         $headers .= "X-Mailer: $XMAILER_ID\r\n";
         //$headers .= "X-SUID: $row->REC_ID\r\n";
         $headers .= "Content-Type: text/html\r\n";

	     $subject = stripslashes($headerArr[$SUBJECT_HEADER]);


         while(list($recid, $row) = each($targetData))
         {

             $nextLastRow = $recid;
             foreach (array_keys($urlArr) as $urlid)
             {
                 $chksum = ($urlid << 8) + ($row->REC_ID << 8) +  ($ecampaign_id << 4) + $SECRET;
                 $url = $server.$REL_APP_PATH.'/'.$ECAMPAIGN_REDIR_MNGR.'?u='.$urlid.'&uid='.$row->REC_ID.'&c='.$ecampaign_id.'&chk='.$chksum;
                 $emailTemplate->set_var('URL'.$urlid , $url);
             }

             $unsubChk = ($row->REC_ID << 8) +  ($ecampaign_id << 4) + $SECRET;
             $unsuburl = $server.$REL_APP_PATH.'/'.$ECAMPAIGN_UNSUB_MNGR.'?e='.$row->EMAIL.'&uid='.$row->REC_ID.'&l='.$listID.'&c='.$ecampaign_id.'&chk='.$unsubChk;

	         $emailTemplate->set_var(
                                    array(
                                    'UNSUB'           => $unsuburl,
                                    'FIRST'           => $row->FIRST,
                                    'LAST'            => $row->LAST,
                                    'EMAIL'           => $row->EMAIL,
                                    'REC_ID'          => $row->REC_ID,
                                    'AGE'             => $row->AGE,
                                    'INCOME'          => $row->INCOME,
                                    'SEX'             => $row->SEX,
                                    'ECAMPAIGN_ID'    => $ecampaign_id,
                                    'APP_PATH'        => $REL_APP_PATH,
                                    'SERVER_URL'      => $server,
                                    'REL_APP_PATH'    => $appPath
                                    )
                                    );

             $message = $emailTemplate->parse('mblock','mainBlock');

             
	         $this->debug("Sending mail to: ".$row->EMAIL);

	         $mailresult = mail ($row->EMAIL, $subject , $message, $headers);

	         if (!$mailresult)
	         {
	         	//Add the REC_ID to bounce array.
	         	$listObj->addToBounced($ecampaign_id ,$listID, $row->REC_ID);

	         }

             $emailTemplate->set_var('mblock', null);
         }

         if (empty($loop))
         {
             $loop = 0;
         }

         $percent = sprintf("%.1f", $MAX_DELIVERY_AT_A_TIME * $loop * 100 / $total);

         $loop++;

         $thisEcampaign->setStatus(isset($nextLastRow) ? $nextLastRow : 0, $ecampaign_id);

         if (empty($nextLastRow))
         {
             $thisEcampaign->setStatus(-1, $ecampaign_id);
             $this->show_status($this->getMessage('CAMPAIGN_SENT'), $ECAMPAIGN_MNGR);
             return;
         }

         $menuTemplate = new Template($this->getTemplateDir());

         $menuTemplate->set_file('fh', $ECAMPAIGN_EXECUTION_TEMPLATE);

         $menuTemplate->set_block('fh','mainBlock', 'main');

         $menuTemplate->set_var(array(
                                       'WAIT'              => $MAX_WAIT_PER_DELIVERY,
                                       'ECAMPAIGN_ID'      => $ecampaign_id,
                                       'LOOP'              => $loop,
                                       'TOTAL'             => $total,
                                       'PERCENT_COMPLETED' => $percent,
                                       'PERCENT_LABEL'     => $percent,
                                       'LAST_SUID'         => $nextLastRow,
                                       'EXEC_TS'           => isset($exec_ts) ? $exec_ts : NULL,
                                       'EXEC_ID'           => isset($exec_id) ? $exec_id : NULL,
                                       'BASE_URL'          => $this->base_url,
                                       'ECAMPAIGN_EXEC_MNGR'  => $ECAMPAIGN_EXEC_MNGR,
                                       'APP_PATH'          => $appPath
                                     )
                               );

         $menuTemplate->parse('main', 'mainBlock');
         $menuTemplate->pparse('output', 'fh');

      }


      function authorize()
      {
          return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   $thisApp = new ecampaignExecManager(
                             array( 'app_name'     => $APPLICATION_NAME,
                                    'app_version'  => '1.0.0',
                                    'app_type'     => 'WEB',
                                    'app_db_url'   => $ECAMPAIGN_DB_URL,
                                    'app_debugger' => $OFF,
                                    'app_auto_connect' => TRUE,
                                    'app_auto_authorize' => FALSE,
                                    'app_auto_chk_session' => TRUE
                                  )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
