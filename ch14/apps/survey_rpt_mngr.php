<?php

   require_once "survey.conf";
   require_once $SURVEY_REPORT_CLASS;

   class surveyReportManager extends PHPApplication {

      function run()
      {
          global $SESSION_USERNAME;
          global $SURVEY_DB_URL;

          // check if user is authentic and has authorization
          // to access this application

/*
          $this->check_session($SESSION_USERNAME);

          if ($this->connect($SURVEY_DB_URL) == FALSE)
          {
             $this->alert('APP_FAILED');
             exit;
          }
*/
          if (! $this->authorize($SESSION_USERNAME))
          {
             $this->alert('UNAUTHORIZED_ACCESS');
          }

          // At this point user is authorized

          $this->showSurveyReport();
     }

      function showSurveyReport()
      {
         global $SURVEY_MNGR;
         global $SURVEY_REPORT_TEMPLATE;
         global $SURVEY_RPT_MNGR;
         global $REPORT_EVEN_ROW_COLOR, $REPORT_ODD_ROW_COLOR;

         $exec_id = $this->getRequestField('exec_id');
         $orderid = $this->getRequestField('orderid');
         $tdesc = $this->getRequestField('tdesc');
         $rdesc = $this->getRequestField('rdesc');

         $appPath = $this->getAppPath();

         $this->emptyError($exec_id,'REPORT_NOT_SELECTED');
         $template = new Template($this->getTemplateDir());

         $template->set_file('fh', $SURVEY_REPORT_TEMPLATE);
         $template->set_block('fh', 'mainBlock', 'mblock');
         $template->set_block('mainBlock', 'responseBlock', 'rblock');

         $reportObj = new SurveyReport($this->dbi);

         if (!strcmp($orderid, 'CNT'))
         {
             $desc = $tdesc;

         } else if (!strcmp($orderid, 'VALUE')) {

             $desc = $rdesc;
         }

         $responseArr = $reportObj->getSurveyResponse($exec_id,
                                                      $orderid,
                                                      isset($desc) ? $desc : NULL);

         $count = 0;
         while (list($key, $value) = each($responseArr))
         {
             $rowColor = ($count++ % 2) ?
                         $REPORT_EVEN_ROW_COLOR : $REPORT_ODD_ROW_COLOR;

             list($fieldid, $fieldvalue) = explode(':', $key);
             $fieldLabel = $reportObj->getLabelsbyFieldAndExecID($fieldid,
                                                                 $exec_id);
             $template->set_var(array(
                                       'LABEL'     => $fieldLabel,
                                       'RESPONSE'  => $fieldvalue,
                                       'TOTAL'     => $value,
                                       'ROW_COLOR' => $rowColor
                                     )
                                );
             $template->parse('rblock', 'responseBlock', true);
         }

         $template->set_var('TDESC', $this->toggleDescField($tdesc));
         $template->set_var('RDESC', $this->toggleDescField($rdesc));


         $dateRange = $reportObj->getResponseDateRange($exec_id);

         $template->set_var(array(
                   'EXEC_ID'           => $exec_id,
                   'BASE_URL'          => $this->base_url,
                   'SURVEY_MNGR'       => $SURVEY_MNGR,
                   'SURVEY_RPT_MNGR'   => $SURVEY_RPT_MNGR,
                   'START_DATE'        => date('m-d-Y h:i', $dateRange['STARTDATE']),
                   'END_DATE'          => date('m-d-Y h:i', $dateRange['LASTDATE']),
                   'TOTAL_RESPONSE'    => $reportObj->getTotalResponseCount($exec_id),
                   'APP_PATH'          => $appPath
                   )
                   );

         if (!$reportObj->getTotalResponseCount($exec_id))
         {
             $template->set_var('rblock', null);
             $template->set_var('START_DATE', null);
             $template->set_var('END_DATE', null);
         }
         $template->parse('mblock','mainBlock');
         $template->pparse('output', 'fh');

      }

      function toggleDescField($field = null)
      {
         return (empty($field)) ? 'desc' : null;
      }

      function authorize()
      {
          return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   global $SURVEY_DB_URL;

   $thisApp = new surveyReportManager(
                                      array( 'app_name'    => $APPLICATION_NAME,
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
