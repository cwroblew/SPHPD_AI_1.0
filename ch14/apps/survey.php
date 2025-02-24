<?php 

   require_once "survey.conf";

   require_once $SURVEY_RESPONSE_CLASS;

   class surveyResponseMngr extends PHPApplication {

      function run()
      {

          global $SURVEY_DB_URL;

          // check if user is authentic and has authorization
          // to access this application

          if ($this->connect($SURVEY_DB_URL) == FALSE)
          {
             $this->alert('APP_FAILURE');
             exit;
          }

          $this->addRecord();
     }

     function addRecord()
     {
        $this->debug("Add Record()");
        
        global $SUID, $FORM_ID, $SURVEY_ID, $EXEC_ID, $EXEC_TS;
        
        $SUID = $this->getRequestField('SUID');
        $FORM_ID = $this->getRequestField('FORM_ID');
        $SURVEY_ID = $this->getRequestField('SURVEY_ID');
        $EXEC_ID = $this->getRequestField('EXEC_ID');
        $EXEC_TS = $this->getRequestField('EXEC_TS');        

        $SURVEY_TS = $EXEC_TS;

        $responseObj = new SurveyResponse($this->dbi);

        $status = $responseObj->isSubmitted($SUID, $EXEC_ID);

        if ($status)
        {
           $this->alert('SURVEY_ALREADY_SUBMITTED',
                        'close');
           return FALSE;

        } else {

           // add response record
           $responseObj->addSubmitRecord($SUID,
                                         $EXEC_ID,
                                         time());

        }

        $errors = 0;

        while (list($k, $v) = each($GLOBALS))
        {
           if (is_numeric($k))
           {
              $data['SUID']        = $SUID;
              $data['EXEC_ID']     = $EXEC_ID;
              $data['FIELD_ID']    = $k;
              $data['VALUE']       = $v;
              $status = $responseObj->add($data);
              if (!$status) $errors++;
           }
        }

        if (! $errors)
        {
            $this->alert('SURVEY_SUBMITTED',
                         'close');

        } else {

            $this->alert('SURVEY_SUBMITTED_WITH_ERRORS',
                         'close');
        }

     }

   }//class

   global $SURVEY_DB_URL;

   $thisApp = new surveyResponseMngr(
                             array( 'app_name'           => $APPLICATION_NAME,
                                   'app_version'         => '1.0.0',
                                   'app_type'            => 'WEB',
                                   'app_db_url'          => $SURVEY_DB_URL,
                                   'app_auto_connect'    => FALSE,
                                   'app_auto_authorize'  => FALSE,
                                   'app_debugger'        => $OFF,
                                   'app_auto_chk_session' => FALSE
                                  )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
