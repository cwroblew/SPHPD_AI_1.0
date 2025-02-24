<?php

   require_once "ecampaign.conf";

   require_once $ECAMPAIGN_URL_CLASS;
   require_once $ECAMPAIGN_MESSAGE_CLASS;
   require_once $TEMPLATE_CLASS;


   class ecampaignMessageMngr extends PHPApplication {

      function run()
      {

          $cmd = $this->getRequestField('cmd');

          $cmd = strtolower($cmd);

          if (empty($cmd) || !strcmp($cmd, 'add'))
          {
              $this->addDriver();
          }
           else if (!strcmp($cmd, 'modify'))
          {
             $this->modifyDriver();
          }
           else if (!strcmp($cmd, 'delete'))
          {
             $this->deleteMessage();

          } else if (!strcmp($cmd, 'preview'))
          {
             $this->doPreview();
          }
      }

      function addDriver()
      {
          $step = $this->getRequestField('step');
          
          if (empty($step))
          {
             $this->displayAddMessageMenu();
          } else if ($step == 2){
          
             $this->addMessage();
             
          } else if ($step == 3){
          
             $this->getMsgPreviewInput();
             
          } else if ($step == 4){
          
             $this->showMsgPreview();
          }
      }
      

      function modifyDriver()
      {
          $step = $this->getRequestField('step');
          
          if (empty($step))
          {
             $this->displayModMessageMenu();
          }else if ($step == 2){
             $this->updateMessage();
          }else if ($step == 3){
             $this->getMsgPreviewInput();
          }else if ($step == 4){
             $this->showMsgPreview();
          } 
      }
      
      function authorize()
      {
          return TRUE;
      }


      function displayAddMessageMenu()
      {
          global $ECAMPAIGN_ADD_MESSAGE_TEMPLATE,
                 $ECAMPAIGN_MNGR,
                 $ECAMPAIGN_MESSAGE_MNGR; 
          $preview = $this->getRequestField('preview');                 
          
	  session_register('SESSION_DATA_HASH');
          
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_ADD_MESSAGE_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          //$template->set_block('mainBlock', 'fieldBlock', 'fblock');
          $template->set_block('mainBlock', 'urlBlock', 'ublock');
	 
	  if (!strcmp($preview, 'yes'))
	  {
	     $template->set_var(array(
	                               'MSGNAME'  =>  stripslashes($_SESSION['SESSION_DATA_HASH']['MSGNAME']),
	                               'MSGFROM'  =>  $_SESSION['SESSION_DATA_HASH']['MSGFROM'],
	                               'MSGREPLY' =>  $_SESSION['SESSION_DATA_HASH']['MSGREPLY'],
	                               'MSGPRIO'  =>  $_SESSION['SESSION_DATA_HASH']['MSGPRIO'],
	                               'MSGSUB'   =>  $_SESSION['SESSION_DATA_HASH']['MSGSUB'],
	                               'MESSAGE'  =>  stripslashes($_SESSION['SESSION_DATA_HASH']['MESSAGE'])
	                             )
	                       );
	     
  
	  }else {
	  		 
	     $template->set_var(array(
	                              'MSGNAME'   => '',
	                              'MSGFROM'   => '',
	                              'MSGREPLY'  => '',
	                              'MSGPRIO'   => 'Select Priority',
	                              'MSGSUB'    => '',
	                              'MESSAGE'   => ''
	                             )
	                         );     	
	     
             
	  }
	  
          /*****SETTING PERSONALIZATION AND URL LOOPS*********/

          $urlObj = new EcampaignURL($this->dbi);

          $urlList = $urlObj->getURLList();
          while (list($urlid, $urlname) = each ($urlList))
          {
             $template->set_var('URL_ID', $urlid);
             $template->set_var('URL_NAME', $urlname);
             $template->parse('ublock', 'urlBlock', true);
          }
          
          $template->set_var('PREVIEW_VALUE',$preview); 
          $template->set_var('ECAMPAIGN_MESSAGE_MNGR', $ECAMPAIGN_MESSAGE_MNGR);
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $template->set_var('APP_PATH', $this->getAppPath());
          $template->set_var('BASE_URL', $this->getBaseURL());
	  $template->set_var('CMD', "add");
	  $template->set_var('MODE', $this->getMessage('CAMPAIGN_CREATE_BTN'));  
	  
	  $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }

       
       
       function displayModMessageMenu()
       {
          global $ECAMPAIGN_ADD_MESSAGE_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $ECAMPAIGN_MNGR,
                 $ECAMPAIGN_MESSAGE_MNGR;
                 
          global $FROM_HEADER,
                 $REPLY_HEADER,
                 $PRIORITY_HEADER,
                 $SUBJECT_HEADER;
                 
          $msg_id = $this->getRequestField('msg_id');
          $step = $this->getRequestField('step');
          $preview = $this->getRequestField('preview');       
        	 
	  session_register('SESSION_DATA_HASH');
	  
 	
 	  if (empty($msg_id) && $preview != 'yes')
          {
             $this->alert('MSG_NOT_CHOSEN');
             return;
          }      
       
          
	  	
          $today    = mktime();
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_ADD_MESSAGE_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          //$template->set_block('mainBlock', 'fieldBlock', 'fblock');
          $template->set_block('mainBlock', 'urlBlock', 'ublock');
	 
	  if (!strcmp($preview, 'yes'))
	  {
	
	
	     $template->set_var(array(
	                   'MSGNAME'  =>  stripslashes($_SESSION['SESSION_DATA_HASH']['MSGNAME']),
	                   'MSGFROM'  =>  $_SESSION['SESSION_DATA_HASH']['MSGFROM'],
	                   'MSGREPLY' =>  $_SESSION['SESSION_DATA_HASH']['MSGREPLY'],
	                   'MSGPRIO'  =>  $_SESSION['SESSION_DATA_HASH']['MSGPRIO'],
	                   'MSGSUB'   =>  $_SESSION['SESSION_DATA_HASH']['MSGSUB'],
	                   'MESSAGE'  =>  stripslashes($_SESSION['SESSION_DATA_HASH']['MESSAGE'])
	                             )
	                       );
	  }else{
	  		 
             $msgObject = new EcampaignMessage($this->dbi);
             $result = $msgObject->getEcampaignMessageInfo($msg_id);
             $result2 = $msgObject->getEcampaignHeaderInfo($msg_id);                      	  
	  
	  
	     $template->set_var(array(
	  		   'MSGNAME' => $result->NAME,
			   'MSGFROM' =>	$result2[$FROM_HEADER],  	
	  	           'MSGREPLY'=> $result2[$REPLY_HEADER],	  	
	  	           'MSGPRIO' => $result2[$PRIORITY_HEADER],
	  	           'MSGSUB'  =>$result2[$SUBJECT_HEADER],
	  	           'MESSAGE' =>$result->BODY
	  	                      )
	  			);         
	  }
	  
          $urlObj = new EcampaignURL($this->dbi);

          $urlList = $urlObj->getURLList();
          while (list($urlid, $urlname) = each ($urlList))
          {
             $template->set_var('URL_ID', $urlid);
             $template->set_var('URL_NAME', $urlname);
             $template->parse('ublock', 'urlBlock', true);
          }

      
          $template->set_var(array(
                     
                     'ECAMPAIGN_MESSAGE_MNGR' => $ECAMPAIGN_MESSAGE_MNGR,
          	     'ECAMPAIGN_MNGR'         => $ECAMPAIGN_MNGR, 
                     'APP_PATH'               => $this->getAppPath(),
                     'TODAY'                  => $today,
                     'BASE_URL'               => $this->getBaseURL(),
                     'MSG_ID'                 => $msg_id,
                     'CMD'                    => "modify",
                     'MODE'                   => $this->getMessage('CAMPAIGN_MODIFY_BTN')
                                   )
                              );
         
          $template->set_var('MSG_ID',$msg_id);        
          $template->set_var('PREVIEW_VALUE',$preview);
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');
       }
       	
       
       function updateMessage()
       {
          global $ECAMPAIGN_MNGR, $ECAMPAIGN_MESSAGE_MNGR;
          
          $msg_id = $this->getRequestField('msg_id');
          $MSGNAME = $this->getRequestField('MSGNAME');
          $MSGFROM = $this->getRequestField('MSGFROM');
          $MSGREPLY = $this->getRequestField('MSGREPLY');
          $MSGPRIO = $this->getRequestField('MSGPRIO');
          $MSGSUB = $this->getRequestField('MSGSUB');
          $MESSAGE = $this->getRequestField('MESSAGE');
          $step = $this->getRequestField('step');
        
          if (empty($MSGNAME) || empty($MSGFROM) || empty($MSGPRIO) || empty($MESSAGE))
          {
              $this->alert('ADD_ECAMPAIGN_MESSAGE_REQ_MISSING');
              return;
          }

          $today = mktime();
          $ecampaignMsgUpdtObj = new EcampaignMessage($this->dbi);
          
                  
          $params = array('MSG_ID'    => $msg_id,
                          'NAME'      => $MSGNAME,
                          'BODY'      => $MESSAGE,
                          'CREATE_TS' => $today,
                          'CREATOR_ID'=> $this->getUID()
                         );
                         
                                  
          $result = $ecampaignMsgUpdtObj->UpdateEcampaignMessage($params);
                          
          $result2 = $ecampaignMsgUpdtObj->UpdateEcampaignMessageHdr($msg_id, $MSGFROM, $MSGREPLY, $MSGPRIO, $MSGSUB);
          
          if ($result AND $result2)
          {
             $this->show_status($this->getMessage('MESSAGE_MODIFIED_SUCCESSFUL'),$ECAMPAIGN_MNGR);
          
          } else{
            
             $this->show_status($this->getMessage('MESSAGE_UPLOAD_FAILED'), $ECAMPAIGN_MNGR);
          }

       }	
       
       
              
       function deleteMessage()
       {
           global $ECAMPAIGN_MNGR;
           $msg_id = $this->getRequestField('msg_id');

           if (empty($msg_id))
           {
               $this->alert('MSG_NOT_CHOSEN');
               return;
           }

           $msgObj = new EcampaignMessage($this->dbi, $msg_id);

           $status = $msgObj->deleteMessage();

           if ($status)
           {
              $this->show_status($this->getMessage('MSG_DELETE_SUCCESSFUL'), $ECAMPAIGN_MNGR);

           } else {

              $this->show_status($this->getMessage('MSG_DELETE_FAILED'), $ECAMPAIGN_MNGR);
           }

       }



       function addMessage()
       {
          global $ECAMPAIGN_MNGR;
          $MSGNAME = $this->getRequestField('MSGNAME');
          $MSGFROM = $this->getRequestField('MSGFROM');
          $MSGREPLY = $this->getRequestField('MSGREPLY');
          $MSGPRIO = $this->getRequestField('MSGPRIO');
          $MSGSUB = $this->getRequestField('MSGSUB');
          $MESSAGE = $this->getRequestField('MESSAGE');
                    
          
          if (empty($MSGNAME) || empty($MSGFROM) || empty($MSGPRIO) || empty($MESSAGE))
          {
              $this->alert('ADD_ECAMPAIGN_MESSAGE_REQ_MISSING');
              return;
          }else
          {
             $today = mktime();
             $ecampaignListObj = new EcampaignMessage($this->dbi);
             $result = $ecampaignListObj->addNewEcampaignMessage($MSGNAME,
                                                                 $MSGFROM,
                                                                 $MSGREPLY,
                                                                 $MSGPRIO,
                                                                 $MSGSUB,
                                                                 $MESSAGE,
                                                                 $today,
                                                                 $this->getUID()
                                                                 );


             if ($result)
             {
                $this->show_status($this->getMessage('MESSAGE_UPLOAD_SUCCESSFUL'),$ECAMPAIGN_MNGR);
            
             } else{
            
                $this->show_status($this->getMessage('MESSAGE_UPLOAD_FAILED'), $ECAMPAIGN_MNGR);
             }
          }
       }


       function getMsgPreviewInput()
       {
          global $ECAMPAIGN_PREVIEW_MESSAGE_INPUT_TEMPLATE,
                 $REL_TEMPLATE_DIR, $ECAMPAIGN_MESSAGE_MNGR,
                 $REL_APP_PATH, $ECAMPAIGN_MNGR;
                 
          $cmd = $this->getRequestField('cmd');       
          $msg_id = $this->getRequestField('msg_id');
                 
          $msgFields = array('MSGNAME', 'MSGFROM', 'MSGREPLY', 'MSGPRIO', 'MSGSUB', 'MESSAGE');

          foreach ($msgFields as $mfield)
	  {
	     global ${$mfield};
	     
	     $hash[$mfield]  = ${$mfield};
	  }

          $preview = $this->getRequestField('preview');

	  session_register('SESSION_DATA_HASH');
        
          $temp_data_hash = $this->getSessionField('SESSION_DATA_HASH');
           
          $_SESSION['SESSION_DATA_HASH'] = serialize($hash);

	  $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_PREVIEW_MESSAGE_INPUT_TEMPLATE);
          $template->set_block('fh', 'mainBlock', 'main');
          $template->set_var('ECAMPAIGN_MESSAGE_MNGR', $ECAMPAIGN_MESSAGE_MNGR);
          $template->set_var('BASE_URL', $this->base_url);
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $template->set_var('APP_PATH', $this->getAppPath());
          	
          	
          if (!strcmp($preview, 'yes'))
          {  
             $template->set_var('FIRST', $temp_data_hash['FIRST']);
             $template->set_var('LAST', $temp_data_hash['LAST']);
             $template->set_var('INCOME', $temp_data_hash['INCOME']);
             $template->set_var('AGE', $temp_data_hash['AGE']);
             $template->set_var('SEX', $temp_data_hash['SEX']);
             $template->set_var('EMAIL', $temp_data_hash['EMAIL']);
             $template->set_var('REC_ID', $temp_data_hash['REC_ID']);
          
          } else{
          		
             $template->set_var('FIRST', '');
             $template->set_var('LAST', '');
             $template->set_var('INCOME', '');
             $template->set_var('AGE', '');
             $template->set_var('SEX', '');
             $template->set_var('EMAIL', '');
             $template->set_var('REC_ID', '');
          }
          
          $template->set_var('MSG_ID',$msg_id);
          $template->set_var('CMD_VALUE', $cmd);
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }



       function doPreview()
       {
       	  
       	  global $SESSION_DATA_HASH;
          global $ECAMPAIGN_PREVIEW_MESSAGE_TEMPLATE;
          global $ECAMPAIGN_REDIR_MNGR, $REL_APP_PATH, $ECAMPAIGN_UNSUB_MNGR, $ECAMPAIGN_MESSAGE_MNGR, $ECAMPAIGN_MNGR;
          
         

	  $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_PREVIEW_MESSAGE_TEMPLATE);
          $template->set_block('fh', 'mainBlock', 'main');
          $template->set_block('mainBlock', 'msgBlock', 'msgblk');

	  session_register('SESSION_DATA_HASH');

          $template->set_var('MESSAGE', $_SESSION['SESSION_DATA_HASH']['MESSAGE']);

          $urlObj = new EcampaignURL($this->dbi);
          $urlList = $urlObj->getURLList();
          $urlIDList = array_keys($urlList);

          reset($_SESSION['SESSION_DATA_HASH']);

          $ECAMPAIGN_REDIR_MNGR = sprintf("%s%s/%s",$this->getServer(),$this->getAppPath(),$ECAMPAIGN_REDIR_MNGR);

          foreach($urlIDList as $urlID)
          {
             $urlTag = 'URL' . $urlID;
             $v = sprintf("%s?u=%d&uid=%d&mode=test", $ECAMPAIGN_REDIR_MNGR, $urlID, $_SESSION['SESSION_DATA_HASH']['REC_ID']);

             $template->set_var($urlTag, $v);
             $template->parse('msgblk', 'msgBlock', false);

          }
          
          
          //exit;

          $sess_arr = $this->getSessionField('SESSION_DATA_HASH');
          while(list($k, $v) = each($sess_arr))
          {
             $personalizationTag = strtoupper($k);

             $template->set_var($personalizationTag, stripslashes($v));

             $template->parse('msgblk', 'msgBlock', false);

          }
          

          $template->set_var('ECAMPAIGN_MESSAGE_MNGR', $ECAMPAIGN_MESSAGE_MNGR);
          $template->set_var('BASE_URL', $this->base_url);
          $template->set_var('APP_PATH', $this->getAppPath());
         
          $unurl = $this->getServer().$REL_APP_PATH.'/'.$ECAMPAIGN_UNSUB_MNGR.'?m=test'; 
          
          $template->set_var('UNSUB', $unurl);         
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          
          
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }
       

       function showMsgPreview()
       {

          global $ECAMPAIGN_PREVIEW_MESSAGE_SHOW_TEMPLATE,
                 $REL_TEMPLATE_DIR, $ECAMPAIGN_MESSAGE_MNGR,
                 $REL_APP_PATH, $ECAMPAIGN_MNGR;
                                  
          $cmd = $this->getRequestField('cmd');
          $msg_id = $this->getRequestField('msg_id');       
          //$message = $this->getRequestField('message');       

	      $msgTestFields = array('FIRST', 'LAST', 'INCOME', 'AGE', 'SEX', 'EMAIL', 'REC_ID');

	      foreach ($msgTestFields as $mtfield)
	      {
                 global ${$mtfield};
                 $hash[$mtfield]  = ${$mtfield};
	      }

              //$hash['message'] = $message;
		
	      global $SESSION_DATA_HASH;
		
	      session_register('SESSION_DATA_HASH');

              $hash = $this->appendHashes($_SESSION['SESSION_DATA_HASH'], $hash);

	      $_SESSION['SESSION_DATA_HASH'] = $hash;

          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_PREVIEW_MESSAGE_SHOW_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_block('mainBlock', 'messageBlock', 'mBlock');
          $template->set_var('ECAMPAIGN_MESSAGE_MNGR', $ECAMPAIGN_MESSAGE_MNGR);
          $template->set_var('BASE_URL', $this->base_url);
          $template->set_var('APP_PATH', $this->getAppPath());
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $template->set_var('MESSAGE', $this->getSessionField('SESSION_USER_MESSAGE'));
          $template->set_var('CMD_VALUE', $cmd);
          $template->set_var('MSG_ID',$msg_id);

          $template->parse('mBlock', 'messageBlock', false);
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }


       function appendHashes($s, $h)
       {

            $sessionHash = unserialize($s);
            while(list($k, $v) = each($sessionHash))
            {
               $h{$k} = $v;
            }

            return $h;
       }

   }//class


   /* Session variables must be defined before session_start()
      method is called */

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   $SESSION_DATA_HASH = null;

   global $ECAMPAIGN_DB_URL;

   $thisApp = new ecampaignMessageMngr(
                            array( 'app_name'     => $APPLICATION_NAME,
                                   'app_version'  => '1.0.0',
                                   'app_type'     => 'WEB',
                                   'app_db_url'   => $ECAMPAIGN_DB_URL,
                                   'app_debugger' => $OFF,
                                   'app_auto_connect' => TRUE,
                                   'app_auto_chk_session' => TRUE,
                                   'app_auto_authorize' => FALSE
                                 )
                           );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
