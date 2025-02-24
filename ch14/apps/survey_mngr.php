<?php

   require_once "survey.conf";
   require_once $SURVEY_LIST_CLASS;
   require_once $SURVEY_FORM_CLASS;
   require_once $SURVEY_CLASS;
   require_once $SURVEY_REPORT_CLASS;

   class surveyMngr extends PHPApplication {

      function run()
      {
          global $SURVEY_MENU_TEMPLATE;
          
          $cmd = strtolower($this->getRequestField('cmd'));

          // At this point user is authorized
          if (!strcmp($cmd, 'create'))
          {
              $this->createSurveyDriver();

          } else if (!strcmp($cmd, 'delete')) {

              $this->delSurvey();

          } else {


             $this->displayMenu($SURVEY_MENU_TEMPLATE, TRUE);

          }
     }

      function createSurveyDriver()
      {
         $step = $this->getRequestField('step');

          if ($step)
          {
             $this->saveSurvey();

          } else {

             global $SURVEY_ADD_TEMPLATE;

             $this->displayMenu($SURVEY_ADD_TEMPLATE, FALSE);

          }
      }


      function delSurvey()
      {

          $survey_id = $this->getRequestField('survey_id');
          
          global $SURVEY_MNGR;

          $this->emptyError($survey_id, 'DEL_SURVEY_ID_MISSING');

          $surveyObj = new Survey($this->dbi);

          $status    = $surveyObj->deleteSurvey($survey_id);

          if ($status)
          {
             $this->show_status($this->getMessage('SURVEY_DELETE_SUCCESSFUL'),
                                $SURVEY_MNGR);

          } else {

             $this->show_status($this->getMessage('SURVEY_DELETE_FAILED'),
                                $SURVEY_MNGR);
          }

      }

      function saveSurvey()
      {

         global $SESSION_USER_ID, $SURVEY_MNGR;

         $name = $this->getRequestField('name');
         $lid = $this->getRequestField('lid');
         $fid = $this->getRequestField('fid');
 

         $this->emptyError($name, 'ADD_SURVEY_NAME_MISSING');
         $this->emptyError($lid,  'ADD_SURVEY_LIST_MISSING');
         $this->emptyError($fid,  'ADD_SURVEY_FORM_MISSING');

         $surveyObj = new Survey($this->dbi);

         $status = $surveyObj->addSurvey($name,
                                         $lid,
                                         $fid,
                                         $SESSION_USER_ID);

         if ($status)
         {
            $this->show_status($this->getMessage('SURVEY_ADD_SUCCESSFUL'),
                               $SURVEY_MNGR);

         } else {

            $this->show_status($this->getMessage('SURVEY_ADD_FAILED'),
                               $SURVEY_MNGR);
         }

      }

      function displayMenu($templateFile = null, $mainMenu = null)
      {

          global $SURVEY_MENU_TEMPLATE;
          global $SURVEY_MNGR,
                 $SURVEY_FORM_MNGR,
                 $SURVEY_LIST_MNGR,
                 $REL_FORMS_DIR,
                 $SURVEY_RESPONSE_MNGR,
                 $SURVEY_RPT_MNGR,
                 $SURVEY_EXEC_MNGR,
                 $SURVEY_SENDER_MNGR,
                 $REL_APP_PATH,
                 $APP_SURVEY_MNGR;

          $menuTemplate = new Template($this->getTemplateDir());
          $menuTemplate->set_file('fh', $templateFile);

          $menuTemplate->set_block('fh','mainBlock', 'main');
          $menuTemplate->set_block('mainBlock', 'listBlock',   'list');
          $menuTemplate->set_block('mainBlock', 'formBlock',   'form');

          $menuTemplate->set_var('SURVEY_MNGR', $APP_SURVEY_MNGR);
          $menuTemplate->set_var('BASE_URL', $this->base_url);

          $surveyListObj = new SurveyList($this->dbi);
          $surveyList    = $surveyListObj->getAvailableLists();

          while(list($lid, $name) = each($surveyList))
          {
             $menuTemplate->set_var('LIST_ID', $lid);
             $menuTemplate->set_var('LIST_NAME', $name);
             $menuTemplate->parse('list', 'listBlock', true);
          }

          $surveyFormObj = new SurveyForm($this->dbi);
          $surveyForms   = $surveyFormObj->getAvailableForms();

          while(list($fid, $name) = each($surveyForms))
          {
             $menuTemplate->set_var('FORM_ID', $fid);
             $menuTemplate->set_var('FORM_NAME', $name);
             $menuTemplate->parse('form', 'formBlock', true);
          }

          if ($mainMenu)
          {
              $surveyObj = new Survey($this->dbi);
              $surveys   = $surveyObj->getAvailableSurveys();

              $menuTemplate->set_block('mainBlock', 'surveyBlock', 'survey');
              $menuTemplate->set_block('mainBlock', 'execBlock',   'exec');

              $execList = $surveyObj->getExecutinRecordList();

              while(list($execID, $row) = each($execList))
              {
                 $rptName = sprintf("%s (%s)", $surveys[$row->SURVEY_ID],
                                             date("m-d-Y h:i:s", $row->SURVEY_TS));
                 $menuTemplate->set_var('EXEC_ID', $execID);
                 $menuTemplate->set_var('SURVEY_REPORT', $rptName);
                 $menuTemplate->parse('exec', 'execBlock', true);
              }

              while(list($sid, $name) = each($surveys))
              {
                 $menuTemplate->set_var('SURVEY_ID', $sid);
                 $menuTemplate->set_var('SURVEY_NAME', $name);
                 $menuTemplate->parse('survey', 'surveyBlock', true);
              }

              while(list($k, $v) = each($surveyList))
              {
                $menuTemplate->set_var('LIST_ID', $lid);
                $menuTemplate->set_var('LIST_NAME', $name);
                $menuTemplate->parse('list', 'listBlock', true);
              }

          }

          $menuTemplate->set_var(array(
                  'APP_PATH'         => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                  'SURVEY_LIST_MNGR' => $SURVEY_LIST_MNGR,
                  'SURVEY_MNGR'      => $SURVEY_MNGR,
                  'SURVEY_FORM_MNGR' => $SURVEY_FORM_MNGR,
                  'SURVEY_RPT_MNGR'  => $SURVEY_RPT_MNGR,
                  'SURVEY_EXEC_MNGR' => $SURVEY_EXEC_MNGR,
                  'REL_FORMS_DIR'    => $REL_FORMS_DIR
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
   global $SURVEY_DB_URL;

   $thisApp = new surveyMngr(
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
