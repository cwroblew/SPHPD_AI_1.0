<?php

   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   
   class tafFrmMngr extends PHPApplication {

     function run()
     {
         $cmd = strtolower($_REQUEST['cmd']);
         
         if (!strcmp($cmd, 'add') || !strcmp($cmd, 'modify'))
         {
            $this->addModifyDriver($cmd);	
         }
         else if (!strcmp($cmd, 'delete'))
         {
            $this->deleteForm();	
         }
     }
     
     function authorize()
     {
         $cmd = strtolower($_REQUEST['cmd']);
         if (!strcmp($cmd, 'add'))
         {
            return true;	
         }
         else
         {
            $fid = $_REQUEST['frmID'];
            $acArr = array(
                           'ACCESS_OBJ'           =>          'FRM',
                           'ALLOW_TBL'            =>          TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'             =>          null,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'            =>          $fid
                          );
            $acObj = new AccessControl($this->dbi, $acArr);

            // if access is explicitly allowed OR not explicitly deined then return true, else false
            return ($acObj->isAccessAllowed() && ! $acObj->isAccessDenied());             	
         }
     }
     
     function addModifyDriver($mode)
     {
     	$step = $this->getRequestField('step');
     	if ($step == 1 || empty($step))
     	{
     	   $this->displayAddModifyMenu($mode);	
     	}
     	else if ($step == 2)
     	{
     	   $this->addModifyForm($mode);	
     	}
     }
     
     function displayAddModifyMenu($mode)
     {
         
        if (!strcmp($mode, 'modify'))
        {
           //check whether the form ID is given
           $fid = $_REQUEST['frmID'];
           if (empty($fid))
           {
              $this->alert('FRM_NOT_SELECTED');	
              return;
           }
           $frmObj = new Form($this->dbi, $fid);
           $frmInfo = $frmObj->getFormInfo();
           $activateTS = $frmInfo['ACTIVATION_TS'];
           $terminateTS = $frmInfo['TERMINATION_TS'];
           
           list($frmActivateMonth, $frmActivateDay, $frmActivateYear) = explode(':', date('n:j:Y', $activateTS));
           list($frmTerminateMonth, $frmTerminateDay, $frmTerminateYear) = explode(':', date('n:j:Y', $terminateTS));
           
           $acArr = array(
                           'ACCESS_OBJ'    =>  'FRM',
                           'ALLOW_TBL'     =>  TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'      =>  TAF_FRM_RESTRICT_IP_TBL,
                           'REQUEST_IP'    =>  $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'     =>  $fid
                          );           
           $acObj = new AccessControl($this->dbi, $acArr);
           $accessIPArr = $acObj->getAccessIPs();
           if (!empty($accessIPArr))
           {
              $accessIPs = implode("\n",$accessIPArr);
           }   
           $deniedIPArr = $acObj->getDeniedIPs();
           if (!empty($deniedIPArr))
           {
              $deniedIPs = implode("\n",$deniedIPArr);
           }   
        }
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', TAF_FRM_SETUP_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'startMonthBlock', 'smb');
        $template->set_block('mainBlock', 'endMonthBlock', 'emb');
        $template->set_block('mainBlock', 'startDayBlock', 'sdb');
        $template->set_block('mainBlock', 'endDayBlock', 'edb');
        $template->set_block('mainBlock', 'startYearBlock', 'syb');
        $template->set_block('mainBlock', 'endYearBlock', 'eyb');
        $template->set_block('mainBlock', 'friendMsgBlock', 'fmb');
        $template->set_block('mainBlock', 'origMsgBlock', 'omb');
        $template->set_block('mainBlock', 'subscrMsgBlock', 'scromb');
        
        $template->set_var(
                           array(
                                 'TAF_MNGR'       =>  TAF_MNGR,
                                 'MODE'           =>  ucwords($mode),
                                 'REQ_IP'         =>  $_SERVER['REMOTE_ADDR'],                                 
                                 'TAF_FRM_MNGR'   =>  TAF_FRM_MNGR,
                                 'FRM_NAME'       =>  isset($frmInfo['FRM_NAME']) ? $frmInfo['FRM_NAME'] : NULL,
                                 'MAX_SUBMTN'     =>  isset($frmInfo['MAX_FRIEND_PER_ORIGIN']) ? $frmInfo['MAX_FRIEND_PER_ORIGIN'] : NULL,                                  
                                 'SCORE_SUBMTN'   =>  isset($frmInfo['SCORE_PER_FRIEND_SUBMISSION']) ? $frmInfo['SCORE_PER_FRIEND_SUBMISSION'] : NULL,
                                 'SCORE_SUBMTN'   =>  isset($frmInfo['SCORE_PER_FRIEND_SUBMISSION']) ? $frmInfo['SCORE_PER_FRIEND_SUBMISSION'] : NULL,
                                 'SCORE_SUBSCR'   =>  isset($frmInfo['SCORE_PER_FRIEND_SUBSCRIPTION']) ? $frmInfo['SCORE_PER_FRIEND_SUBSCRIPTION'] : NULL,
                                 'DENIED_IPS'     =>  isset($deniedIPs) ? $deniedIPs : NULL,
                                 'ALLOWED_IPS'    =>  isset($accessIPs) ? $accessIPs : NULL,
                                 'FRM_ID'         =>  isset($fid) ? $fid : NULL
                                )
                          );
        
        //The loop to control the activation and termination months
        for ($i=1;$i<=12;$i++)
        {
           $template->set_var('MON', $i);
           $template->set_var('MON_STR', date('M', mktime(0,0,0,$i,1,MIN_YEAR)));
           $template->set_var('SM_CHOSEN', (isset($frmActivateMonth) && $frmActivateMonth==$i) ? 'SELECTED' : NULL);
           $template->set_var('EM_CHOSEN', (isset($frmTerminateMonth) && $frmTerminateMonth==$i) ? 'SELECTED' : NULL);
                                 
           $template->parse('smb', 'startMonthBlock', true);
           $template->parse('emb', 'endMonthBlock', true);              	
        }
        //The loop to control the activation and termination days
        for ($i=1;$i<=31;$i++)
        {
           $template->set_var('DAY', $i);
           $template->set_var('SD_CHOSEN', (isset($frmActivateDay) && $frmActivateDay==$i) ? 'SELECTED' : NULL);
           $template->set_var('ED_CHOSEN', (isset($frmTerminateDay) && $frmTerminateDay==$i) ? 'SELECTED' : NULL);
           $template->parse('sdb', 'startDayBlock', true);
           $template->parse('edb', 'endDayBlock', true);	
        }
        //The loop to control the activation and termination years
        for ($i=MIN_YEAR;$i<=MAX_YEAR;$i++)
        {
           $template->set_var('YEAR', $i);
           $template->set_var('SY_CHOSEN', (isset($frmActivateYear) && $frmActivateYear==$i) ? 'SELECTED' : NULL);
           $template->set_var('EY_CHOSEN', (isset($frmTerminateYear) && $frmTerminateYear==$i) ? 'SELECTED' : NULL);
           $template->parse('syb', 'startYearBlock', true);
           $template->parse('eyb', 'endYearBlock', true);	
        }
        
        $msgObj = new Message($this->dbi);
        //get all the messages
        $msgs = $msgObj->getAllMessages();
        if (!empty($msgs))
        {
           $acArr = array(
                          'ACCESS_OBJ'           =>          'MSG',
                          'ALLOW_TBL'            =>          TAF_MSG_OWNER_IP_TBL,                           
                          'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR']
                         );
           $acObj = new AccessControl($this->dbi, $acArr);
           while(list($msgID, $msgName) = each($msgs))
           {
              //Decides whether the msg is usable by this IP
              $acObj->setAccessObjectID($msgID);   
              if ($acObj->isAccessAllowed() && ! $acObj->isAccessDenied())
              {
                 $template->set_var(
                                    array(
                                          'MSG_ID'               =>    $msgID,
                                          'MSG_NAME'             =>    $msgName,
                                          'FRIEND_MSG_CHOSEN'    =>    (isset($frmInfo['FRIENDS_MSG_ID']) && $frmInfo['FRIENDS_MSG_ID'] == $msgID) ? 'SELECTED' : null,
                                          'ORIG_MSG_CHOSEN'      =>    (isset($frmInfo['ORIGIN_MSG_ID']) && $frmInfo['ORIGIN_MSG_ID'] == $msgID) ? 'SELECTED' : null,
                                          'SUBSCR_MSG_CHOSEN'    =>    (isset($frmInfo['SUBSCRIBER_MSG_ID']) && $frmInfo['SUBSCRIBER_MSG_ID'] == $msgID) ? 'SELECTED' : null,
                                         )
                                   );
                 $template->parse('fmb', 'friendMsgBlock', true);                  
                 $template->parse('omb', 'origMsgBlock', true);
                 $template->parse('scromb', 'subscrMsgBlock', true);
              }   
           }
        }         
        
        $template->parse('main','mainBlock', false);
        $template->pparse('output', 'fh');
     }
     
     function addModifyForm($mode)
     {
        $activateTS = mktime(0,0,0, $_REQUEST['start_month'], $_REQUEST['start_day'], $_REQUEST['start_year']);
        $terminateTS = mktime(0,0,0, $_REQUEST['end_month'], $_REQUEST['end_day'], $_REQUEST['end_year']);
        
        if ($activateTS > $terminateTS )
        {
           $this->alert('INCORRECT_DATE_RANGE');	
           return;
        }
        
        $params = array(
                        'FRM_ID'                        =>   $_REQUEST['frmID'] ? $_REQUEST['frmID'] : 'null',
                        'FRM_NAME'                      =>   $_REQUEST['frmName'],
                        'ACTIVATION_TS'                 =>   $activateTS,
                        'TERMINATION_TS'                =>   $terminateTS,
                        'FRIENDS_MSG_ID'                =>   $_REQUEST['friends_msg'],
                        'ORIGIN_MSG_ID'                 =>   $_REQUEST['originator_msg'],
                        'SUBSCRIBER_MSG_ID'             =>   $_REQUEST['subscription_msg'],
                        'MAX_FRIEND_PER_ORIGIN'         =>   $_REQUEST['maxsubmissions'],
                        'SCORE_PER_FRIEND_SUBMISSION'   =>   $_REQUEST['score_submission'],
                        'SCORE_PER_FRIEND_SUBSCRIPTION' =>   $_REQUEST['score_subscription']
                       );                                                                  
        $frmObj = new Form ($this->dbi);                                                
        $acArr = array(                                                                    
                           'ACCESS_OBJ'           =>          'FRM',                       
                           'ALLOW_TBL'            =>          TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'             =>          TAF_FRM_RESTRICT_IP_TBL,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],                           
                          );
        $acObj = new AccessControl($this->dbi, $acArr);
        
        $accessIPArr = explode("\n", $_REQUEST['access_ips']);
        $deniedIPArr = explode("\n", $_REQUEST['banned_ips']);

        //Create FORM ACTION value
        $action = sprintf("%s%s/%s", $this->get_server(),
                                        $GLOBALS['REL_APP_PATH'],
                                        TAF_SUBMISSION_MNGR
                             );   
        
        if (!strcmp($mode, 'add'))
        {
           $status = $frmObj->addForm($params);
           if (!$status)
           {
              $this->show_status($this->getMessage('FRM_NOT_ADDED'), TAF_MNGR);
              return;	
           }
           $acObj->setAccessObjectID($status);           
           if (!empty($accessIPArr))
           {
              $acObj->addAccessIPs($accessIPArr);
           }
           if (!empty($deniedIPArr))
           {   
              $acObj->addDeniedIPs($deniedIPArr);
           }


           $addMsg = $this->getMessage('FRM_ADDED');
           $patterns = array("/FORM_ACTION/", "/FORM_ID/", "/NL/");
           $replace = array($action, $status, "\n");
           $addMsg= preg_replace($patterns, $replace, $addMsg);
           $this->show_status($addMsg, TAF_MNGR);

        }
        else if (!strcmp($mode, 'modify'))
        {
           $status = $frmObj->modifyForm($params);	
           if (!$status)
           {
              $this->show_status($this->getMessage('FRM_NOT_MODIFIED'), TAF_MNGR);
              return;	
           }
           $acObj->setAccessObjectID($_REQUEST['frmID']);
           $acObj->deleteAccessIP();
           $acObj->deleteDeniedIP();
           if (!empty($accessIPArr))
           {
              $acObj->addAccessIPs($accessIPArr);
           }
           if (!empty($deniedIPArr))
           {   
              $acObj->addDeniedIPs($deniedIPArr);
           }

           
           //$this->show_status($this->getMessage('FRM_MODIFIED'), TAF_MNGR);
           $modMsg = $this->getMessage('FRM_MODIFIED');
           $patterns = array("/FORM_ACTION/", "/FORM_ID/", "/NL/");
           $replace = array($action, $params['FRM_ID'], "\n");
           $modMsg= preg_replace($patterns, $replace, $modMsg);
           $this->show_status($modMsg, TAF_MNGR);

        }
     }
     
     function deleteForm()
     {
        $fid = $_REQUEST['frmID'];
        if (empty($fid))
        {
           $this->alert('FRM_NOT_SELECTED');	
           return;
        }        
        $frmObj = new Form($this->dbi);        
        $status = $frmObj->deleteForm($fid);
        if ($status)
        {
           $acArr = array(
                           'ACCESS_OBJ'           =>          'FRM',
                           'ALLOW_TBL'            =>          TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'             =>          TAF_FRM_RESTRICT_IP_TBL,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'            =>          $fid 
                          );
           $acObj = new AccessControl($this->dbi, $acArr);
           $acObj->deleteAccessIP($fid);
           $acObj->deleteDeniedIP($fid);
        }
        $this->show_status($this->getMessage($status ? 'FRM_DELETED' : 'FRM_NOT_DELETED'), TAF_MNGR);
     }
     
   }//class
   
   $thisApp = new tafFrmMngr(
                             array('app_name'              => $APPLICATION_NAME,
                                   'app_version'           => '1.0.0',
                                   'app_type'              => 'WEB',
                                   'app_auto_connect'      => TRUE,
                                   'app_auto_authorize'    => TRUE,
                                   'app_auto_chk_session'  => FALSE,
                                   'app_debugger'          => $OFF,
                                   'app_db_url'            => $TAF_DB_URL,
                                   )
                            );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
