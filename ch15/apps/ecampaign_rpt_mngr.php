<?php

   require_once "ecampaign.conf";
   require_once $ECAMPAIGN_REPORT_CLASS;
   require_once $ECAMPAIGN_URL_CLASS;
   require_once $ECAMPAIGN_CAMPAIGN_CLASS;

   class ecampaignReportManager extends PHPApplication {

      function run()
      {
          // At this point user is authorized
          $this->showEcampaignReport();
     }

      function showEcampaignReport()
      {
         $ecampaign_id = $this->getRequestField('ecampaign_id');
         $orderid = $this->getRequestField('orderid');
         $tdesc = $this->getRequestField('tdesc');
         $udesc = $this->getRequestField('udesc');
         
         global $ECAMPAIGN_REPORT_TEMPLATE, $ECAMPAIGN_REPORT_MNGR, $ECAMPAIGN_MNGR;
         global $REPORT_EVEN_ROW_COLOR, $REPORT_ODD_ROW_COLOR;
         
         if (empty($ecampaign_id))
         {
            $this->alert('CAMPAIGN_NOT_CHOSEN');
            return;	
         }
         
         
         if (empty($orderid)) $orderid = 'URL_ID';
         $reportObj = new EcampaignReport($this->dbi, $ecampaign_id);
         $urlObj = new EcampaignURL($this->dbi);
         $cmpObj = new EcampaignCampaign($this->dbi);
         
         $URLResponse = $reportObj->getURLResponse($ecampaign_id, null, $orderid, $tdesc);
                 
         $UniqueURLResponse = $reportObj->getURLResponse($ecampaign_id, 'DISTINCT', $orderid, $udesc);
                 
         $unsubResponse = $reportObj->getUnsubResponse($ecampaign_id);
         
         $bouncedResponse = $reportObj->getBounceResponse($ecampaign_id);
         
         
         
         
         
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $ECAMPAIGN_REPORT_TEMPLATE);
         $menuTemplate->set_block('fh','mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'responseBlock', 'rblock');
         
         $cmpInfo = $cmpObj->getCampaignInfo($ecampaign_id);
         $cmpName = $cmpInfo['NAME'];
         $menuTemplate->set_var('CMPG_NAME', $cmpName);
         
         $count = 0;
         if (empty($URLResponse))
         {
             $menuTemplate->set_var('rblock', null);  	
         }
         else
         {
             while (list($urlid, $response) = each($URLResponse))
             {
                    
                    $rowColor = ($count++ % 2) ?
                         $REPORT_EVEN_ROW_COLOR : $REPORT_ODD_ROW_COLOR;
                    $menuTemplate->set_var('ROW_COLOR', $rowColor);  
                    $urlarr = $urlObj->getURLInfo($urlid);
                    $menuTemplate->set_var('URL_ID', $urlid);  
                    $menuTemplate->set_var('URL_ADDR', $urlarr['URL']);  
                    $menuTemplate->set_var('URL_NAME', $urlarr['NAME']);  
                    $menuTemplate->set_var('TOTAL_CLICK', $response);
                    $menuTemplate->set_var('UNIQUE_CLICK', $UniqueURLResponse[$urlid]);
                    $menuTemplate->parse('rblock', 'responseBlock', true);
            }
         }
         
         
         $menuTemplate->set_var('TDESC', $this->toggleDescField($tdesc));
         $menuTemplate->set_var('UDESC', $this->toggleDescField($udesc));
         $menuTemplate->set_var('BOUNCED', $bouncedResponse);
         $menuTemplate->set_var('UNSUB', $unsubResponse);
         $menuTemplate->set_var('APP_PATH', $this->getAppPath());
         $menuTemplate->set_var('ECAMPAIGN_RPT_MNGR', $ECAMPAIGN_REPORT_MNGR);
         $menuTemplate->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
         $menuTemplate->set_var('CMPG_ID', $ecampaign_id);
         
         $menuTemplate->set_var('BASE_URL', $this->getBaseURL());
         
         $menuTemplate->parse('main','mainBlock', false);
         $menuTemplate->pparse('output', 'fh');
         
         
      }
      function authorize()
      {
          return TRUE;
      }

      function toggleDescField($field = null)
      {
         return (empty($field)) ? 'desc' : null;
      }

   }//class


   $thisApp = new ecampaignReportManager(
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
