<?php

   require_once "webforms.conf";
   

   class FormSubmissionApp extends PHPApplication {

      function run()
      { 
          
         // The request is allowed and therefore lets proceed
         // with form submission.

         // Create a form submission object
         $thisForm = new FormSubmission($this->dbi);

         // Check to see if the request is for a known form.
         if (! $thisForm->isKnownForm($_REQUEST['form_id']))
         {
             $this->alert('NOT_VALID_REQUEST');
             $this->logRequest();
             return;
        
         } else {

            $thisForm->loadConfigFile();
            $thisForm->setupForm();
  
         }

         // First see of the request is allowed
         if (! $this->authorize())
         {
             $this->alert('NOT_VALID_REQUEST');
             $this->logRequest();
             return;
         }

         // OK, now the request and form is valid
         // Lets process the form.
         
         $status = $thisForm->processForm();
         
         if ($status == 1)
         {
            // Redirect the submitter to an URL or show
            // a thank page
            if (AUTO_REDIRECT)
            {   
               header("Location: ".AUTO_REDIRECT_URL);

            } else {
                $this->showPage(SHOW_THANKYOU_TEMPLATE);
            }
         
         } else {

            // If there are errors in form processing get the error messages (if any)
            if ($thisForm->hasError())
            {
                $errorInfo = $thisForm->getErrorMessage($this->language);
            }
            
            if ($status == MISSING_REQUIRED_VALUES)
            {
               $this->alert('MISSING_REQUIRED_DATA' . $errorInfo);
            
            } else if($status == BAD_DATA) {
            
               $this->alert('INVALID_DATA_GIVEN');
             
            } else if($status == DATABASE_FAILURE) {
            
               $this->alert('DATA_SUBMISSION_FAILED');
            
            } else if($status == INVALID_FILE_SIZE) {
            
               $this->alert('LARGER_FILE_SIZE');
            }
         }

         return TRUE;
      }


      function showPage($templateFile = null)
      {  
         // Use cleaned up form data to personalize the template
                   
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', $templateFile);
         $template->set_block('fh', 'mainBlock', 'mblock');
         
         foreach($_REQUEST as $key => $value)
         {
            $template->set_var(strtoupper($key), $value);
         }

         $template->parse('mblock', 'mainBlock');
         $template->pparse('output', 'fh');

      }

    
      function authorize()
      {
          // This method is called automatically by application
          // when app_auto_authorize'=> TRUE when application object
          // is created.
          //
          // Use ACL_ALLOW_FROM and ACL_DENY_FROM
          
          $currentIP = $_SERVER['REMOTE_ADDR'];

          $aclObj = new ACL(array(
                                  'current_ip' => $currentIP,
                                  'allow_from' => ACL_ALLOW_FROM,
                                  'deny_from'  => ACL_DENY_FROM
                                 )
                            );

          return ($aclObj->isAllowed()) ? TRUE: FALSE;
      }

      function logRequest()
      {
      	  // Append a log entry to FORM_LOG_FILE
      	  // Log Format: TIMESTAMP, IP_ADDR, REQUEST_URL
      }

   }//class

   $thisApp = new FormSubmissionApp(
                                    array('app_name'              => APPLICATION_NAME,
                                          'app_version'           => '1.0.0',
                                          'app_type'              => 'WEB',
                                          'app_auto_connect'      => TRUE,
                                          'app_auto_authorize'    => FALSE,
                                          'app_auto_chk_session'  => FALSE,
                                          'app_debugger'          => $OFF,
                                          'app_db_url'            => FORM_DB_URL,
                                         )
                                    );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
