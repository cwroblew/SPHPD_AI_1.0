<?php

   require_once 'ecampaign.conf';

   require_once $ECAMPAIGN_UNSUB_CLASS;
   require_once $ECAMPAIGN_URL_CLASS;

   class unsub extends PHPApplication {

      function run()
      {
          $chk = $this->getRequestField('chk');
          $step = $this->getRequestField('step');
          $m = $this->getRequestField('m');


          if($m == 'test')
          {
             $this->alert('TEST_MODE');
     	     exit();
          }

          if($chk != $this->computeCheckSum())
          {
             $this->alert('INVALID_UNSUB_REQUEST');
          }

          if( $step != 2)
          {
             $this->askForConfirmation();
          }else{
             $this->unsubUser();
          }
      }

      function computeCheckSum()
      {
          global $SECRET;
          
          $uid = $this->getRequestField('uid');
          $c = $this->getRequestField('c');

          return ($uid << 8) +  ($c << 4) + $SECRET;

      }


      function askForConfirmation()
      {
          $uid = $this->getRequestField('uid');
          $c = $this->getRequestField('c');
          $e = $this->getRequestField('e');
          $l = $this->getRequestField('l');
          $chk = $this->getRequestField('chk');


          global $ECAMPAIGN_UNSUB_TEMPLATE,
                 $REL_TEMPLATE_DIR, $ECAMPAIGN_UNSUB_MNGR,
                 $REL_APP_PATH;



          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_UNSUB_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          
          $template->set_var(array('BASE_URL' => $this->base_url,
                                   'UNSUB'    => $ECAMPAIGN_UNSUB_MNGR,
                                   'USER_ID'  => $uid,
                                   'C_ID'     => $c,
	                           'L_ID'     => $l,
                                   'EMAIL'    => $e,
                                   'CHK'      => $chk,
                                   'APP_PATH' =>$REL_APP_PATH
                                   )
                             );
                                   
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');




      }


      function unsubUser()
      {

	 global $ECAMPAIGN_UNSUB_CONFIRM_TEMPLATE;
         
         
         $uid = $this->getRequestField('uid');
         $c = $this->getRequestField('c');
         $l = $this->getRequestField('l');

	 $unsubObj = new EcampaignUnsub($this->dbi);

         $status = $unsubObj->storeUnsub($uid, $l, $c);


         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', $ECAMPAIGN_UNSUB_CONFIRM_TEMPLATE);
         $template->set_block('fh','mainBlock', 'main');
         $template->set_var('BASE_URL', $this->base_url);
         $template->parse('main','mainBlock', false);
         $template->pparse('output', 'fh');



      }


   }//class

   global $ECAMPAIGN_DB_URL;
   $thisApp = new unsub(
                            array('app_name'              =>  $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_db_url'            => $ECAMPAIGN_DB_URL,
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_auto_chk_session'  => FALSE,
                                  'app_debugger'          => $OFF
                            )
                        );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
