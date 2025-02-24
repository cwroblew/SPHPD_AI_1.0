<?php

   require_once "survey.conf";

   require_once $SURVEY_LIST_CLASS;
   require_once $SURVEY_FORM_CLASS;
   require_once $SURVEY_CLASS;

   class surveyExecManager extends PHPApplication {

      function run()
      {
          $this->executeSurvey();
      }

      function executeSurvey()
      {
         $loop = $this->getRequestField('loop');
         $total = $this->getRequestField('total');
         $lastrow = $this->getRequestField('lastrow');
         $exec_ts = $this->getRequestField('exec_ts');
         $exec_id = $this->getRequestField('exec_id');
         $survey_id = $this->getRequestField('survey_id');

         global $SURVEY_MNGR,
                $MAX_WAIT_PER_DELIVERY,
                $XMAILER_ID,
                $MAX_DELIVERY_AT_A_TIME;

         global $SURVEY_EXECUTION_TEMPLATE,
                $REL_APP_PATH,
                $FORMS_DIR,
                $SURVEY_RESPONSE_MNGR,
                $SURVEY_EXEC_MNGR,
                $SURVEY_POWERED_BY_TEMPLATE,
                $REL_APP_PATH;

         $this->emptyError($survey_id, 'RUN_SURVEY_ID_MISSING');

         $thisSurvey = new Survey($this->dbi, $survey_id);

         $status = $thisSurvey->getSurveyInfo();

         if (!$status)
         {
            $this->alert('RUN_SURVEY_ID_MISSING');
         }

         $lastrow  = $thisSurvey->getStatus();
         $formID   = $thisSurvey->getFormID();
         $surveyID = $thisSurvey->getSurveyID();
         $server   = $this->getServer();
         $appPath  = $this->getAppPath();

         $pwrByTemplate = new Template($this->getTemplateDir());
         $pwrByTemplate->set_file('fh', $SURVEY_POWERED_BY_TEMPLATE);
         $pwrByTemplate->set_block('fh', 'mainBlock');
         $pwrByTemplate->parse('fh', 'mainBlock');
         $poweredByStr = $pwrByTemplate->parse('output', 'fh');

         if (empty($lastrow))
         {
            // first time. Create survey execution record
            $exec_ts = time();

            $exec_id = $thisSurvey->addExecutionRecord($surveyID, time());

            if (empty($exec_ts))
            {
                $this->alert('SURVEY_EXECUTION_FAILED');
            }
         }

         // Ready to execute
         $listObj = new SurveyList($this->dbi, $thisSurvey->getListID());

         $targetData = $listObj->getTargetData($lastrow,
                                               $MAX_DELIVERY_AT_A_TIME);

         //Load the survey form

         $formObj       = new SurveyForm($this->dbi);
         $formData      = $formObj->getFormInfo($formID);
         $formTemplate  = $formData->TEMPLATE;
         $emailTemplate = new Template($FORMS_DIR);
         $emailTemplate->set_file('fh', $formTemplate);
         $emailTemplate->set_block('fh', 'mainBlock', 'mblock');


         while(list($suid, $row) = each($targetData))
         {
             $this->debug("$suid $row->FIRST $row->LAST $row->EMAIL");

             $nextLastRow = $suid;

             $emailTemplate->set_var(
                                    array(
                                    'FIRST'           => $row->FIRST,
                                    'LAST'            => $row->LAST,
                                    'EMAIL'           => $row->EMAIL,
                                    'SUID'            => $row->SUID,
                                    'SURVEY_ID'       => $surveyID,
                                    'FORM_ID'         => $formID,
                                    'EXEC_TS'         => $exec_ts,
                                    'EXEC_ID'         => $exec_id,
                                    'SURVEY_RESPONSE' => $SURVEY_RESPONSE_MNGR,
                                    'APP_PATH'        => $REL_APP_PATH,
                                    'SERVER_URL'      => $server,
                                    'POWERED_BY_LOGO' => $poweredByStr,
                                    'REL_APP_PATH'    => $appPath
                                    )
                                    );

             $message = $emailTemplate->parse('mblock','mainBlock');

             $headers  = "From: " . stripslashes($formData->MAILFROM) . "\r\n";
             $headers .= "X-Mailer: $XMAILER_ID\r\n";
             $headers .= "X-SUID: $row->SUID\r\n";
             $headers .= "Content-Type: text/html\r\n";

             $subject = stripslashes($formData->SUBJECT);
             mail ($row->EMAIL, $subject, $message, $headers);
             $this->debug("$row->EMAIL, $subject" );
             $emailTemplate->set_var('mblock', null);
         }

         if (empty($loop))
         {
             $loop = 0;
         }

         if (empty($loop))
         {
             $total = $listObj->getTotalRecordCount();
         }

         $percent = sprintf("%.1f", $MAX_DELIVERY_AT_A_TIME * $loop * 100 / $total) ;

         $this->debug("$MAX_DELIVERY_AT_A_TIME  Total = $total  LOOP = $loop");
         $this->debug("Total $total percent = $percent");

         $loop++;

         $thisSurvey->setStatus(isset($nextLastRow) ? $nextLastRow : 0, $thisSurvey->getSurveyID());

         if (empty($nextLastRow))
         {
             $thisSurvey->setStatus(0, $thisSurvey->getSurveyID());
             $this->show_status($this->getMessage('SURVEY_SENT'), $SURVEY_MNGR);
             return;
         }

         $menuTemplate = new Template($this->getTemplateDir());

         $menuTemplate->set_file('fh', $SURVEY_EXECUTION_TEMPLATE);

         $menuTemplate->set_block('fh','mainBlock', 'main');

         $menuTemplate->set_var(array(
                                       'WAIT'              => $MAX_WAIT_PER_DELIVERY,
                                       'SURVEY_ID'         => $survey_id,
                                       'LOOP'              => $loop,
                                       'TOTAL'             => $total,
                                       'PERCENT_COMPLETED' => $percent,
                                       'PERCENT_LABEL'     => $percent,
                                       'LAST_SUID'         => $nextLastRow,
                                       'EXEC_TS'           => $exec_ts,
                                       'EXEC_ID'           => $exec_id,
                                       'BASE_URL'          => $this->base_url,
                                       'SURVEY_MNGR'       => $SURVEY_MNGR,
                                       'SURVEY_EXEC_MNGR'  => $SURVEY_EXEC_MNGR,
                                       'APP_PATH'          => $appPath
                                     )
                               );

         $menuTemplate->parse('main', 'mainBlock');
         $menuTemplate->pparse('output', 'fh');

      }


      function authorize()
      {
          return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $SURVEY_DB_URL;

   $thisApp = new surveyExecManager(
                             array( 'app_name'     => $APPLICATION_NAME,
                                    'app_version'  => '1.0.0',
                                    'app_type'     => 'WEB',
                                    'app_db_url'   => $SURVEY_DB_URL,
                                    'app_debugger' => $OFF,
                                    'app_auto_connect' => TRUE,
                                    'app_auto_authorize' => TRUE,
                                    'app_auto_chk_session' => TRUE
                                   )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
