<?php
   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   
   class tafSubscribe extends PHPApplication {

     function run()
     {
        $this->processRequest();
     }
     
     function authorize()
     {
        $cf = $_REQUEST['cf'];
        $this->_email = $_REQUEST['email'];
        $this->_frmID = $_REQUEST['fid'];
        $this->_cmd = $_REQUEST['cmd'];
        $validCmds = array('sub', 'unsub');         
        list($this->_frndID, $chkEmail, $this->_origin) = explode(":", base64_decode($cf)); 
        if (empty($this->_frmID) || strcmp($this->_email, $chkEmail) || !in_array($this->_cmd, $validCmds))
        {
           return false;	
        }       
        $this->frmObj = new Form($this->dbi, $this->_frmID);        
        $frnds = $this->frmObj->getFriendList();
        return (array_search($chkEmail, $frnds) == $this->_frndID) ? TRUE : FALSE;
     }
     
     function processRequest()
     {
        $params = array(
                        'FRM_ID'        =>  $this->_frmID,
                        'FRND_EMAIL'    =>  strtolower($this->_email),
                        'SUBSCRIPTION'  =>  $this->_cmd,
                        'ORIGIN_EMAIL'  =>  strtolower($this->_origin),
                        'TS'            =>  mktime() 
                       );
        $status = $this->frmObj->addSubscriptionData($params);
        
        if ($status && !strcmp($this->_cmd, 'sub'))
        {
           $frmInfo = $this->frmObj->getFormInfo();
           $msgObj = new Message($this->dbi);
           $subscrMsgID = $frmInfo['SUBSCRIBER_MSG_ID'];        
           $msgInfo = $msgObj->getMessageInfo($subscrMsgID);        
           $subscrMessage = $msgInfo['BODY'];
           $subscrMsgSub = $msgInfo['SUBJECT'];
           $subscrMsgFrom = $msgInfo['MSG_FROM'];
           $subscrMsgRepyTo = $msgInfo['REPLY_TO'];
           $headers  = "Content-type: text/html; charset=iso-8859-1\r\n";
           $headers .= "From: $subscrMsgFrom\r\n";
           $headers .= "From: $subscrMsgRepyTo\r\n";           
           mail($this->_email, $subscrMsgSub, $subscrMessage, $headers);
        }
        $this->show_status($this->getMessage($status ? 'REQ_SUBMITTED' : 'REQ_NOT_SUBMITTED'), '/');
     }
     
     
     
   }//class
   
   $thisApp = new tafSubscribe(array('app_name'              => $APPLICATION_NAME,
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
