<?php

   require_once 'ecampaign.conf';

   require_once $ECAMPAIGN_URL_CLASS;
   require_once $TEMPLATE_CLASS;

   /* Session variables must be defined before
     session_start() method is called */

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   class ecampaignURLMngr extends PHPApplication {

      function run()
      {
         
         $cmd = $this->getRequestField('cmd');

         $cmd = strtolower($cmd);

         if (!strcmp($cmd, 'delete'))
         {
            $this->deleteURL();
         
         }else if(!strcmp($cmd, 'modify')){
         
            $this->modifyURLDriver();
         
         }else{             
          
            $this->addURLDriver();
         }
      }


      function addURLDriver()
      {
         $step = $this->getRequestField('step');

         if (!$step)
         {
               $this->displayAddURLMenu();

         } else if ($step == 2){

              $this->addURL();
         }
      }


      function modifyURLDriver()
      {
         $step = $this->getRequestField('step');

         if (!$step)
         {
            $this->displayModifyURLMenu();

         }else if ($step == 2)
         {
            $this->modifyURL();
         }
      }


      function deleteURL()
      {
         global $ECAMPAIGN_MNGR;
         $url_select = $this->getRequestField('url_select');

         if (empty($url_select))
         {
            $this->alert('NO_URL_CHOSEN');
         }

         $EcampaignURLObj = new EcampaignURL($this->dbi);

         $status = $EcampaignURLObj->deleteURL($url_select);

         if ($status)
         {
            $this->show_status($this->getMessage('URL_DELETE_SUCCESSFUL'), $ECAMPAIGN_MNGR);
            
         } else {

            $this->show_status($this->getMessage('URL_DELETE_FAILED'), $ECAMPAIGN_MNGR);
         }
      }


      
      function authorize()
      {
         return TRUE;
      }


      function displayAddURLMenu()
      {
         global $ECAMPAIGN_ADD_URL_TEMPLATE,
                $REL_TEMPLATE_DIR, $ECAMPAIGN_MNGR,
                $REL_APP_PATH, $ECAMPAIGN_URL_MNGR;


         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', $ECAMPAIGN_ADD_URL_TEMPLATE);
         $template->set_block('fh','mainBlock', 'main');
         
         $template->set_var('ECAMPAIGN_URL_MNGR', $ECAMPAIGN_URL_MNGR);
         $template->set_var('BASE_URL', $this->base_url);
         $template->set_var('APP_PATH', $REL_APP_PATH);
         $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
         
         $template->parse('main','mainBlock', false);
         $template->pparse('output', 'fh');
      }


      function addURL()
      {
         global $FORMS_DIR, $ECAMPAIGN_MNGR;
         global $ERRORS, $SURVEY_MNGR, $REL_APP_PATH;
         
         
              
         
         $name = $this->getRequestField('name');
         $url = $this->getRequestField('url');
         

         if ( empty($name) || empty($url) )
         {
            $this->alert('URL_REQ_MISSING');

         }else{

            $EcampaignURLObj = new EcampaignURL($this->dbi);

            $status = $EcampaignURLObj->addURL($name, $url);

            if ($status)
            {
               $this->show_status($this->getMessage('URL_UPLOAD_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);
            }
            else {

               $this->show_status($this->getMessage('URL_UPLOAD_FAILED'),
                                 $ECAMPAIGN_MNGR);
            }
         }
     }


     function displayModifyURLMenu()
     {
        global  $ECAMPAIGN_MOD_URL_TEMPLATE,
                $REL_TEMPLATE_DIR, $REL_APP_PATH,
                $REL_APP_PATH, $ECAMPAIGN_MNGR,
                $SURVEY_MNGR, $ECAMPAIGN_URL_MNGR;

        $url_select = $this->getRequestField('url_select');
         

        if (empty($url_select))
        {
           $this->alert('NO_URL_CHOSEN');
        }

        $EcampaignURLObj = new EcampaignURL($this->dbi);

        $result = $EcampaignURLObj->getURLInfo($url_select);

        $template = new Template($this->getTemplateDir());

        $template->set_file('fh', $ECAMPAIGN_MOD_URL_TEMPLATE);
        $template->set_block('fh','mainBlock', 'main');

        $template->set_var('ECAMPAIGN_URL_MNGR', $ECAMPAIGN_URL_MNGR);
        $template->set_var('APP_PATH', $REL_APP_PATH);
        $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
        $template->set_var('URL_ID', $result['URL_ID']);
        $template->set_var('NAME', $result['NAME']);
        $template->set_var('URL', $result['URL']);
        $template->set_var('BASE_URL', $this->base_url);

        $template->parse('main','mainBlock', false);
        $template->pparse('output', 'fh');
     }


     function modifyURL()
     {
        global $FORMS_DIR;
        global $ERRORS, $REL_APP_PATH;
        
        global $ECAMPAIGN_MNGR;
        
        $url_id = $this->getRequestField('url_id');
         $name = $this->getRequestField('name');
         $url = $this->getRequestField('url');
         $userfile = $this->getRequestField('userfile');
         $userfile_name = $this->getRequestField('userfile_name');
         $formname = $this->getRequestField('formname');
         $num_fields = $this->getRequestField('num_fields');
         $subject = $this->getRequestField('subject');
         $from = $this->getRequestField('from');
         

        if ( empty($name) || empty($url) )
        {

           $this->alert('URL_REQ_MISSING');

        } else{

           $EcampaignURLObj = new EcampaignURL($this->dbi);

           $status = $EcampaignURLObj->modifyURL($url_id ,$name, $url);

           if ($status)
           {
              $this->show_status($this->getMessage('URL_MODIFIED_SUCCESSFUL'), $ECAMPAIGN_MNGR);
          
           } else {

              $this->show_status($this->getMessage('URL_MODIFIED_FAILED'), $ECAMPAIGN_MNGR);
           }
        }
     }



   }//class

   global $ECAMPAIGN_DB_URL;

   $thisApp = new ecampaignURLMngr(
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