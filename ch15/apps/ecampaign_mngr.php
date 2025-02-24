<?php

   require_once "ecampaign.conf";

   require_once $ECAMPAIGN_LIST_CLASS;
   require_once $ECAMPAIGN_URL_CLASS;
   require_once $ECAMPAIGN_MESSAGE_CLASS;
   require_once $ECAMPAIGN_CAMPAIGN_CLASS;
   //require_once $ECAMPAIGN_CLASS;

   class ecampaignMngr extends PHPApplication {

      function run()
      {
          
         global $ECAMPAIGN_MENU_TEMPLATE;

         $this->displayMenu($ECAMPAIGN_MENU_TEMPLATE, TRUE);

          
     }

      
      function displayMenu($templateFile = null, $mainMenu = null)
      {

          global $ECAMPAIGN_MENU_TEMPLATE;
          global $ECAMPAIGN_MNGR,
                 $ECAMPAIGN_URL_MNGR,
                 $ECAMPAIGN_LIST_MNGR,
                 $ECAMPAIGN_CAMPAIGN_MNGR,
                 $ECAMPAIGN_MESSAGE_MNGR,
                 $ECAMPAIGN_EXEC_MNGR,
                 $ECAMPAIGN_REPORT_MNGR,
                 $REL_APP_PATH;

          $menuTemplate = new Template($this->getTemplateDir());
          $menuTemplate->set_file('fh', $templateFile);

          $menuTemplate->set_block('fh','mainBlock', 'main');
          $menuTemplate->set_block('mainBlock', 'listBlock',   'list');
          $menuTemplate->set_block('mainBlock', 'urlBlock',   'url');
          $menuTemplate->set_block('mainBlock', 'messageBlock',   'msgblock');
          $menuTemplate->set_block('mainBlock', 'campaignBlock',   'cmpblock');

          $menuTemplate->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $menuTemplate->set_var('BASE_URL', $this->base_url);

          $ListObj = new EcampaignList($this->dbi);
          $List = array();
          $List    = $ListObj->getAvailableLists();

          while(list($lid, $name) = each($List))
          {
             $menuTemplate->set_var('LIST_ID', $lid);
             $menuTemplate->set_var('LIST_NAME', $name);
             $menuTemplate->parse('list', 'listBlock', true);
          }

	      $EcampaignURLobj = new EcampaignURL($this->dbi);
          $List2 = array();
          $List2 = $EcampaignURLobj->getURLList();

          while( list( $url_id, $name) = each($List2))
          {
             $menuTemplate->set_var('URL_ID', $url_id);
             $menuTemplate->set_var('URL_NAME', $name);
             $menuTemplate->parse('url', 'urlBlock', true);
          }

          $EcampaignMessageObj = new EcampaignMessage($this->dbi);
          $messages = $EcampaignMessageObj->getAvailableMessages();
          while( list( $mid, $name) = each($messages))
          {
             $menuTemplate->set_var('MESSAGE_ID', $mid);
             $menuTemplate->set_var('MESSAGE_NAME', $name);
             $menuTemplate->parse('msgblock', 'messageBlock', true);
          }

          $cmpgObj = new EcampaignCampaign($this->dbi);
          $cmpgArr = $cmpgObj->getAvailableCampaigns();
          while (list($cid, $cname) = each($cmpgArr))
          {
             $menuTemplate->set_var('CAMPAIGN_ID', $cid);
             $menuTemplate->set_var('CAMPAIGN_NAME', $cname);
             $menuTemplate->parse('cmpblock', 'campaignBlock', true);
          }



          
          $menuTemplate->set_var(array(
                                      'APP_PATH'                => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                      'ECAMPAIGN_LIST_MNGR'     => $ECAMPAIGN_LIST_MNGR,
                                      'ECAMPAIGN_CAMPAIGN_MNGR' => $ECAMPAIGN_CAMPAIGN_MNGR,
                                      'ECAMPAIGN_MNGR'          => $ECAMPAIGN_MNGR,
                                      'ECAMPAIGN_URL_MNGR'      => $ECAMPAIGN_URL_MNGR,
                                      'ECAMPAIGN_MESSAGE_MNGR'  => $ECAMPAIGN_MESSAGE_MNGR,
                                      'ECAMPAIGN_EXEC_MNGR'     => $ECAMPAIGN_EXEC_MNGR,
                                      'ECAMPAIGN_REPORT_MNGR'   => $ECAMPAIGN_REPORT_MNGR
                                      

                                      )
                                );

          $menuTemplate->parse('main', 'mainBlock', false);

          $menuTemplate->pparse('output', 'fh');

      }

      function authorize()
      {
          return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $ECAMPAIGN_DB_URL;

   $thisApp = new ecampaignMngr(

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
