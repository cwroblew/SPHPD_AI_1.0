<?php

   require_once 'ecampaign.conf';

   require_once $ECAMPAIGN_LIST_CLASS;
   require_once $ECAMPAIGN_CAMPAIGN_CLASS;
   require_once $ECAMPAIGN_MESSAGE_CLASS;

   /* Session variables must be defined before
     session_start() method is called */

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   class ecampaignCampaignMngr extends PHPApplication {

      function run()
      {
          $cmd = $this->getRequestField('cmd');

          $cmd = strtolower($cmd);

          if (empty($cmd) || !strcmp($cmd, 'create'))
          {
              $this->createCampaign();

          } else if (!strcmp($cmd, 'delete')) {

              $this->delCampaign();

          } else if (!strcmp($cmd, 'modify')) {

              $this->modifyCampaign();
          }
      }

      function createCampaign()
      {
          $step = $this->getRequestField('step');

           if (!$step)
           {
              $this->displayCampaignMenu();

           } else if ($step == 2){

              $this->addCampaign();
           }
      }

      function delCampaign()
      {
            global $ECAMPAIGN_MNGR;
            
            $camp_id = $this->getRequestField('camp_id');

            if (empty($camp_id)) $this->alert('CAMPAIGN_NOT_CHOSEN');


            $EcampaignDELObj = new EcampaignCampaign($this->dbi);

            $status = $EcampaignDELObj->deleteCampaign($camp_id);

            if ($status)
            {
              $this->show_status($this->getMessage('CAMPAIGN_DELETE_SUCCESSFUL'),$ECAMPAIGN_MNGR);
            }
            else {

              $this->show_status($this->getMessage('CAMPAIGN_DELETE_FAILED'), $ECAMPAIGN_MNGR);
            }
      }

      function modifyCampaign()
      {
            $camp_id = $this->getRequestField('camp_id');
            $step = $this->getRequestField('step');

            if (empty($camp_id)) $this->alert('CAMPAIGN_NOT_CHOSEN');

            if (!$step)
            {
               $this->displayCampaignMenu();

            }else if ($step == 2)
            {

               $this->updateCampaign();
            }
      }

      function authorize()
      {
          return TRUE;
      }

      function displayCampaignMenu()
      {
          global $ECAMPAIGN_ADD_CAMPAIGN_TEMPLATE,
                 $ECAMPAIGN_MNGR,
                 $REL_APP_PATH,
                 $ECAMPAIGN_CAMPAIGN_MNGR;
          $camp_id = $this->getRequestField('camp_id');       

          $template = new Template($this->getTemplateDir());

          $template->set_file('fh', $ECAMPAIGN_ADD_CAMPAIGN_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_block('mainBlock','listBlock', 'lblock');
          $template->set_block('mainBlock','messageBlock', 'mblock');


          $template->set_var(array(                  
                                 'ECAMPAIGN_MNGR'          => $ECAMPAIGN_MNGR,
                                 'ECAMPAIGN_CAMPAIGN_MNGR' => $ECAMPAIGN_CAMPAIGN_MNGR,
                                 'BASE_URL'                => $this->base_url,
                                 'APP_PATH'                => $REL_APP_PATH                                 
                                  )
                             );
                             

          $this->debug("App path = $REL_APP_PATH");

          if ($camp_id)
          {
             $thisCampaign = new EcampaignCampaign($this->dbi, $camp_id);
             $campaignData = $thisCampaign->getCampaignInfo();
             $template->set_var('CAMPAIGN_NAME', $campaignData['NAME']);
             $template->set_var('MODE', $this->getMessage('CAMPAIGN_MODIFY_BTN'));

          } else {

               $template->set_var('CAMPAIGN_NAME', null);
               $template->set_var('MODE', $this->getMessage('CAMPAIGN_CREATE_BTN'));
          }

          $template->set_var('CAMP_ID', $camp_id);

          $ListObj = new EcampaignList($this->dbi);
          $List =    array();
          $List    = $ListObj->getAvailableLists();

          while(list($lid, $name) = each($List))
          {
             if (isset($campaignData['LIST_ID']) && $lid == $campaignData['LIST_ID'])
             {
                $template->set_var('CHOSEN', 'selected');
             } else {
                $template->set_var('CHOSEN', null);
             }

             $template->set_var('LIST_ID', $lid);
             $template->set_var('LIST_NAME', $name);
             $template->parse('lblock', 'listBlock', true);
          }

          $Message=array();
          $msgObj = new EcampaignMessage($this->dbi);
          $Message  = $msgObj->getAvailableMessages();

          while(list($mid, $name) = each($Message))
          {
             if (isset($campaignData['MSG_ID']) && $mid == $campaignData['MSG_ID'])
             {
                $template->set_var('CHOSEN', 'selected');
             } else {
                $template->set_var('CHOSEN', null);
             }

             $template->set_var('MSG_ID', $mid);
             $template->set_var('MESSAGE_NAME', $name);
             $template->parse('mblock', 'messageBlock', true);
          }

          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');
       }

       function addCampaign()
       {
          global $ERRORS,$REL_APP_PATH;
          global $ECAMPAIGN_MNGR;
          
          $name = $this->getRequestField('name');
          $lid = $this->getRequestField('lid');
          $mid = $this->getRequestField('mid');
          

          $this->emptyError($name, 'MISSING_CAMPAIGN_NAME');
          $this->emptyError($lid, 'MISSING_LIST_NAME');
          $this->emptyError($mid, 'MISSING_MESSGE_NAME');


          $EcampaignADDObj = new EcampaignCampaign($this->dbi);

          $params = array('NAME' => $name,
                          'LIST_ID' => $lid,
                          'MSG_ID' => $mid
                          );

          $status = $EcampaignADDObj->addCampaign($params);

          if ($status)
          {
              $this->show_status($this->getMessage('CAMPAIGN_UPLOAD_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);
          }
          else {

              $this->show_status($this->getMessage('CAMPAIGN_UPLOAD_FAILED'),
                                 $ECAMPAIGN_MNGR);
          }
       }


       

       function updateCampaign()
       {
          global $ERRORS,$REL_APP_PATH, $ECAMPAIGN_MNGR;
          
          
          $camp_id = $this->getRequestField('camp_id');
          $name = $this->getRequestField('name');
          $lid = $this->getRequestField('lid');
          $mid = $this->getRequestField('mid');
          

          $this->emptyError($name, 'MISSING_CAMPAIGN_NAME');
          $this->emptyError($lid, 'MISSING_LIST_NAME');
          $this->emptyError($mid, 'MISSING_MESSGE_NAME');

          $this->debug("Campaign id = $camp_id ");

          $EcampaignMODObj = new EcampaignCampaign($this->dbi);


          $params = array('CAMPAIGN_ID' => $camp_id,
                          'NAME'        => $name,
                          'LIST_ID'     => $lid,
                          'MSG_ID'      => $mid
                          );

          $status = $EcampaignMODObj->modifyCampaign($params);

          if ($status)
          {
              $this->show_status($this->getMessage('CAMPAIGN_MODIFIED_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);
          }
          else {

              $this->show_status($this->getMessage('CAMPAIGN_MODIFIED_FAILED'),
                                 $ECAMPAIGN_MNGR);
          }
       }
       
   }//class

   global $ECAMPAIGN_DB_URL;

   $thisApp = new ecampaignCampaignMngr(
                          		array( 'app_name'       => $APPLICATION_NAME,
                                	       'app_version'    => '1.0.0',
                                  	       'app_type'       => 'WEB',
                                  	       'app_db_url'     => $ECAMPAIGN_DB_URL,
                                 	       'app_debugger'   => $OFF,
                                 	       'app_auto_connect' => TRUE,
                                 	       'app_auto_authorize' => FALSE,
                                 	       'app_auto_chk_session' => TRUE
                               		     )
                        		);

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
