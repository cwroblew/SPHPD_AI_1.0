<?php

   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   
   class tafMngr extends PHPApplication {

     function run()
     {
         $this->displayTAFMenu();
     }     
     
     function displayTAFMenu()
     {
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', TAF_MENU_TEMPLATE);
         $template->set_block('fh', 'mainBlock', 'main');         
         $template->set_block('mainBlock', 'frmBlock', 'fb');
         $template->set_var('fb', null);         
         $template->set_block('mainBlock', 'msgBlock', 'mb');                 
         $template->set_var('mb', null);         
         $frmObj = new Form($this->dbi);         
         //get all the forms
         $frms = $frmObj->getAllForms();
         if (!empty($frms))
         {
            $acArr = array(
                           'ACCESS_OBJ'           =>          'FRM',
                           'ALLOW_TBL'            =>          TAF_FRM_OWNER_IP_TBL,
                           'DENY_TBL'             =>          TAF_FRM_RESTRICT_IP_TBL,
                           'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR']
                          );
            $acObj = new AccessControl($this->dbi, $acArr);
            while(list($frmID, $frmName) = each($frms))
            {
               //Decides whether the form is configurable by this IP
               $acObj->setAccessObjectID($frmID);

               if ($acObj->isAccessAllowed() && !$acObj->isAccessDenied())
               {
                  $template->set_var(
                                     array(
                                           'FRM_ID'    =>  $frmID,
                                           'FRM_NAME'  =>  $frmName
                                          )
                                    );
                  $template->parse('fb', 'frmBlock', true);
               }  
            }
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
               //Decides whether the msg is modifiable by this IP
               $acObj->setAccessObjectID($msgID);   
               if ($acObj->isAccessAllowed() && !$acObj->isAccessDenied())
               {
                  $template->set_var(
                                     array(
                                           'MSG_ID'    =>  $msgID,
                                           'MSG_NAME'  =>  $msgName
                                          )
                                    );
                  $template->parse('mb', 'msgBlock', true);                  
               }   
            }
         }         
         $template->set_var(
                            array(
                                  'DELETE_FRM_CONFIRMATION' =>    $this->getMessage('DELETE_FRM_CONFIRMATION'),
                                  'DELETE_MSG_CONFIRMATION' =>    $this->getMessage('DELETE_MSG_CONFIRMATION'),
                                  'TAF_FRM_MNGR'            =>    TAF_FRM_MNGR,
                                  'TAF_MSG_MNGR'            =>    TAF_MSG_MNGR,
                                  'TAF_REPORTER'            =>    TAF_REPORT_MNGR
                                 )                                           
                           );
         
         $template->parse('main', 'mainBlock');
         $template->pparse('output','fh');
     }
   }//class
   
   $thisApp = new tafMngr(
                             array('app_name'              =>  $APPLICATION_NAME,
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
