<?php

   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   
   class tafMsgMngr extends PHPApplication {

     function run()
     {
         $cmd = strtolower($_REQUEST['cmd']);
         
         if (!strcmp($cmd, 'add') || !strcmp($cmd, 'modify'))
         {
            $this->addModifyDriver($cmd);	
         }
         else if (!strcmp($cmd, 'delete'))
         {
            $this->deleteMessage();	
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
            $mid = $_REQUEST['msgID'];
            $acArr = array(
                           'ACCESS_OBJ'           =>          'MSG',
                           'ALLOW_TBL'            =>          TAF_MSG_OWNER_IP_TBL,
                           'DENY_TBL'             =>          null,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'            =>          $mid
                          );
            $acObj = new AccessControl($this->dbi, $acArr);
            return $acObj->isAccessAllowed();             	
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
     	   $this->addModifyMessage($mode);	
     	}
     }
     
     function displayAddModifyMenu($mode)
     {
        if (!strcmp($mode, 'modify'))
        {
           $mid = $_REQUEST['msgID'];
           if (empty($mid))
           {
              $this->alert('MSG_NOT_SELECTED');	
              return;
           }
           $msgObj = new Message($this->dbi, $mid);
           $msgInfo = $msgObj->getMessageInfo();
           
           $acArr = array(
                           'ACCESS_OBJ'           =>          'MSG',
                           'ALLOW_TBL'            =>          TAF_MSG_OWNER_IP_TBL,
                           'DENY_TBL'             =>          null,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'            =>          $mid
                          );
            $acObj = new AccessControl($this->dbi, $acArr);
            $accessIPArr = $acObj->getAccessIPs();
            $accessIPs = (!empty($accessIPArr)) ? implode("\n",$accessIPArr) : null;
        }
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', TAF_MSG_SETUP_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_var(
                           array(
                                 'TAF_MNGR'         =>  TAF_MNGR,
                                 'MSG_NAME'         =>  isset($msgInfo['MSG_NAME']) ? $msgInfo['MSG_NAME'] : NULL,
                                 'MSG_SUB'          =>  isset($msgInfo['SUBJECT']) ? $msgInfo['SUBJECT'] : NULL,
                                 'MSG_BODY'         =>  isset($msgInfo['BODY']) ? $msgInfo['BODY'] : NULL, 
                                 'MSG_FROM'         =>  isset($msgInfo['MSG_FROM']) ? $msgInfo['MSG_FROM'] : NULL,
                                 'MSG_REPLY_TO'     =>  isset($msgInfo['REPLY_TO']) ? $msgInfo['REPLY_TO'] : NULL,
                                 'MSG_ID'           =>  isset($mid) ? $mid : NULL,
                                 'MODE'             =>  ucwords($mode),
                                 'REQ_IP'           =>  $_SERVER['REMOTE_ADDR'],
                                 'ACCESS_IP'        =>  isset($accessIPs) ? $accessIPs : NULL,
                                 'TAF_MSG_MNGR'     =>  TAF_MSG_MNGR 
                                )
                          );
        $template->parse('main','mainBlock', false);
        $template->pparse('output', 'fh');
     }
     
     function addModifyMessage($mode)
     {
        $params = array(
                        'MSG_ID'     =>   $_REQUEST['msgID'] ? $_REQUEST['msgID'] : 'null',
                        'MSG_NAME'   =>   $_REQUEST['msgName'],
                        'BODY'       =>   $_REQUEST['msgBody'],
                        'MSG_FROM'   =>   $_REQUEST['msgFrom'],
                        'REPLY_TO'   =>   $_REQUEST['msgReplyTo'],
                        'SUBJECT'    =>   $_REQUEST['msgSub']
                       );
        $msgObj = new Message ($this->dbi);
        $acArr = array(
                           'ACCESS_OBJ'           =>          'MSG',
                           'ALLOW_TBL'            =>          TAF_MSG_OWNER_IP_TBL,
                           'DENY_TBL'             =>          null,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],                           
                          );
        $acObj = new AccessControl($this->dbi, $acArr);
        
        
        $IPArr = explode("\n", $_REQUEST['msg_access_ips']);
        
        
        if (!strcmp($mode, 'add'))
        {
           $status = $msgObj->addMessage($params);
           if (!$status)
           {
              $this->show_status($this->getMessage('MSG_NOT_ADDED'), TAF_MNGR);
              return;	
           }
           $acObj->setAccessObjectID($status);           
           $acObj->addAccessIPs($IPArr);
           $this->show_status($this->getMessage('MSG_ADDED'), TAF_MNGR);
        }
        else if (!strcmp($mode, 'modify'))
        {
           $status = $msgObj->modifyMessage($params);	
           if (!$status)
           {
              $this->show_status($this->getMessage('MSG_NOT_MODIFIED'), TAF_MNGR);
              return;	
           }
           $acObj->setAccessObjectID($_REQUEST['msgID']);
           $acObj->deleteAccessIP();
           $acObj->addAccessIPs($IPArr);
           $this->show_status($this->getMessage('MSG_MODIFIED'), TAF_MNGR);
        }
     }
     
     function deleteMessage()
     {
        $mid = $_REQUEST['msgID'];
        if (empty($mid))
        {
           $this->alert('MSG_NOT_SELECTED');	
           return;
        }
        
        $msgObj = new Message($this->dbi);        
        $status = $msgObj->deleteMessage($mid);
        if ($status)
        {
           $acArr = array(
                           'ACCESS_OBJ'           =>          'MSG',
                           'ALLOW_TBL'            =>          TAF_MSG_OWNER_IP_TBL,
                           'DENY_TBL'             =>          null,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                           'AC_OBJ_ID'            =>          $mid 
                          );
           $acObj = new AccessControl($this->dbi, $acArr);
           $acObj->deleteAccessIP($mid);
        }
        $this->show_status($this->getMessage($status ? 'MSG_DELETED' : 'MSG_NOT_DELETED'), TAF_MNGR);
     }
     
   }//class
   
   $thisApp = new tafMsgMngr(
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
