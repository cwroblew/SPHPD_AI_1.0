<?php
   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   
   class taf extends PHPApplication {

     function run()
     {
        $this->processRequest();
     }
     
     function processRequest()
     {
        $origIP = $_SERVER['REMOTE_ADDR'];
        $fid = $this->getRequestField('formid');
        $orig = $this->getRequestField('origin');
        $emailArr = $this->getRequestField('email');
        $nameArr = $this->getRequestField('name');
        
        if (empty($fid))
        {
           $this->alert('FRM_ID_MISSING');	
           return;
        }
        
        if (empty($orig))
        {
           $this->alert('ORIG_EMAIL_MISSING');	
           return;	
        }

        if (empty($emailArr))
        {
           $this->alert('FRIEND_EMAIL_MISSING');	
           return;	
        }
        
        $acArr = array(
                           'ACCESS_OBJ' => 'FRM',
                           'ALLOW_TBL'  => TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'   => TAF_FRM_RESTRICT_IP_TBL,
                           'REQUEST_IP' => $origIP,
                           'AC_OBJ_ID'  => $fid
                          );
        $acObj = new AccessControl($this->dbi, $acArr);
        
        if ($acObj->isAccessDenied())
        {
           $this->alert('IP_BANNED');	
           return;
        }
        
        
        $frmObj = new Form($this->dbi, $fid);
        $msgObj = new Message($this->dbi);        
        $frmInfo = $frmObj->getFormInfo();
        $ts = mktime();

        if (($frmInfo['ACTIVATION_TS'] > $ts) || ($frmInfo['TERMINATION_TS'] < $ts))
        {
           $this->alert('TIME_RANGE_EXCEEDED');	
           return;
        }
        
        $frndMsgID = $frmInfo['FRIENDS_MSG_ID'];  
        $msgInfo = $msgObj->getMessageInfo($frndMsgID);
        $frndMessage = $msgInfo['BODY'];
        $frndMsgSub = $msgInfo['SUBJECT'];
        $frndMsgFrom = $msgInfo['MSG_FROM'];
        $frndMsgRepyTo = $msgInfo['REPLY_TO'];
        $headers  = "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "From: $frndMsgFrom\r\n";
        $headers .= "From: $frndMsgRepyTo\r\n";        
        
        $friendsCount = 0;

        $seenFriend = array();

        while((list($key, $email) = each($emailArr)) && (!$frmObj->isMaximumSubmitted($orig)))
        {  

           $email = strtolower(trim($email));

           if (empty($email) || (!empty($seenFriend) && $seenFriend{$email}) )
           {
               continue;
           }

           // Set this email address as seen so that we do not 
           // process duplicate friend email addresses.
           $seenFriend{$email} = TRUE;

           $friendsCount++;
 
           if (!$frmObj->hasUnsubscribed($email))
           {
               $params = array(
                                'FRND_ID'      => 'null',
                                'FRND_EMAIL'   => $email,
                                'FRND_NAME'    => $nameArr[$key],
                                'FRM_ID'       => $fid,
                                'ORIGIN_EMAIL' => $orig,
                                'ORIGIN_IP'    => $origIP,
                                'SUBMIT_TS'    => $ts
                              );

               $frndID = $frmObj->addSubmissionData($params);

               if ($frndID)
               {
                  $frndArr[$email] = $nameArr[$key];
                  $frndMailTemplate = new Template($this->getTemplateDir());
                  $frndMailTemplate->set_file('fh', TAF_FRIEND_MSG_TEMPLATE);
                  $frndMailTemplate->set_block('fh', 'mainBlock', 'main');

                  $subscriptionURL = sprintf("%s%s/%s",$this->get_server(), 
                                                       $GLOBALS['REL_APP_PATH'],
                                                       TAF_SUBSCRIPTION_MNGR);

                  $this->debug("Subscription Manager $subscriptionURL");
                  $frndMailTemplate->set_var(array
                                                  (
                                                    'ORIGIN'         => $orig,
                                                    'NAME'           => $nameArr[$key],
                                                    'FRND_EMAIL'     => $email,
                                                    'SUBSCRIBE_MNGR' => $subscriptionURL,
                                                    'FRND_MSG'       => $frndMessage,
                                                    'FRM_ID'         => $fid,
                                                    'CHECKFLAG'      => base64_encode($frndID.':'.$email.':'.$orig)
                                                  )
                                            );
                  $body = $frndMailTemplate->parse('main', 'mainBlock', false);                                    
                  mail($email, $frndMsgSub, $body, $headers);
               }   
           }   
        }

        if(! $friendsCount)
        {
           $this->alert('FRIEND_EMAIL_MISSING');
           return;
        }

        // If no friend was added show error message and abort
        if(count($frndArr) <= 0)
        {
           $this->alert('INVALID_DATA');
           return;
        }

        $origMsgID = $frmInfo['ORIGIN_MSG_ID'];        
        $msgInfo = $msgObj->getMessageInfo($origMsgID);        
        $origMessage = $msgInfo['BODY'];
        $origMsgSub = $msgInfo['SUBJECT'];
        $origMsgFrom = $msgInfo['MSG_FROM'];
        $origMsgRepyTo = $msgInfo['REPLY_TO'];
        $headers  = "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "From: $origMsgFrom\r\n";
        $headers .= "From: $origMsgRepyTo\r\n";
        
        $origMailTemplate = new Template($this->getTemplateDir());
        $origMailTemplate->set_file('fh', TAF_ORIGIN_MSG_TEMPLATE);
        $origMailTemplate->set_block('fh', 'mainBlock', 'main');
        $origMailTemplate->set_block('mainBlock', 'frndBlock', 'frnd');
        
        $origMailTemplate->set_var('ORIG_MSG', $origMessage);

         while(list($email, $name) = each($frndArr))
         {
           $origMailTemplate->set_var('FRND_NAME', $name);	
           $origMailTemplate->set_var('FRND_EMAIL', $email);
           $origMailTemplate->parse('frnd', 'frndBlock', true);
         }	

        $reporterURL = sprintf("%s%s/%s",$this->get_server(), 
                                         $GLOBALS['REL_APP_PATH'],
                                         TAF_REPORT_MNGR);
        
        $origMailTemplate->set_var(array(
                                         'TAF_REPORTER' => $reporterURL,
                                         'FRM_ID'       => $fid,
                                         'CHECKFLAG'    => base64_encode($fid.":".$orig),
                                         'ORIG'         => $orig 
                                        )
                                  );       
        
        
        $origBody = $origMailTemplate->parse('main', 'mainBlock', false);
        
        mail($orig, $origMsgSub, $origBody, $headers);
        
        $this->show_status($this->getMessage('FRNDS_SUBMITTED'), '/');
     }
     
     
     
   }//class
   
   $thisApp = new taf(array('app_name'              => $APPLICATION_NAME,
                            'app_version'           => '1.0.0',
                            'app_type'              => 'WEB',
                            'app_auto_connect'      => TRUE,
                            'app_auto_authorize'    => FALSE,
                            'app_auto_chk_session'  => FALSE,
                            'app_debugger'          => $OFF,
                            'app_db_url'            => $TAF_DB_URL,
                           )
                     );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
