<?php

   require_once 'survey.conf';

   require_once $SURVEY_FORM_CLASS;


   class surveyFormMngr extends PHPApplication {

      function run()
      {
          $cmd = strtolower($this->getRequestField('cmd'));

          if (strcmp($cmd, 'delete'))
          {
              $this->addDriver();

          } else
          {
              $this->delForm();
          }
     }

      function addDriver()
      {
         $step = $this->getRequestField('step');
         if (!$step)
         {
            $this->displayAddFormMenu();

         } else if ($step==2){

            $this->addForm();
         }
         else if ($step==3){

             $this->addLabels();
         }

      }

      function authorize()
      {
          return TRUE;
      }


      function displayAddFormMenu()
      {
          global $SURVEY_ADD_FORM_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $REL_APP_PATH,
                 $SURVEY_MNGR,
                 $APP_SURVEY_FORM_MNGR;

          $today = mktime();

          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $SURVEY_ADD_FORM_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_var('SURVEY_FORM_MNGR', $APP_SURVEY_FORM_MNGR);
          $template->set_var('TODAY', $today);
          $template->set_var('SURVEY_MNGR', $SURVEY_MNGR);
          $template->set_var('BASE_URL', $this->getBaseURL());
          $template->set_var('APP_PATH', $REL_APP_PATH);
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');
       }

       function addForm()
       {
          $today = $this->getRequestField('today');
          $userfile = $this->getRequestField('userfile');
          $userfile_name = $this->getRequestField('userfile_name');
          $formname = $this->getRequestField('formname');

          $num_fields = $this->getRequestField('num_fields');
          $subject = $this->getRequestField('subject');
          $from = $this->getRequestField('from');
          
          global $FORMS_DIR;
          global $ERRORS, $SURVEY_MNGR, $REL_APP_PATH;

          $this->emptyError($subject, 'ADD_FORM_MISSING_SUBJECT');
          $this->emptyError($from,    'ADD_FORM_MISSING_FROM');

          $filename = $_FILES['userfile']['name'];

          if (!is_array($_FILES['userfile']) ||
                empty($formname))
          {
              $this->alert('ADD_SURVEY_FORM_REQ_MISSING');

          } else if ($num_fields <= 0 ||
                    !is_numeric($num_fields)) {
              $this->alert('FIELD_NUM_INVALID');

          } else {

             $ext = $this->fileextension($filename);

             $filename = preg_replace("/.$ext/", ".ihtml", $filename);

             copy($_FILES['userfile']['tmp_name'], $FORMS_DIR.'/'.$_FILES['userfile']['name']);
             //copy($userfile, $FORMS_DIR . '/' .$filename);

             $surveyFormObj = new SurveyForm($this->dbi);

             $data = array(
                     'NAME'       => $formname,
                     'TEMPLATE'   => $filename,
                     'SUBJECT'    => $subject,
                     'MAILFROM'   => $from,
                     'CREATE_TS'  => $today,
                     'CREATOR_ID' => $this->getUID()
                     );

              $dataType = array(
                               'NAME'       => 'text',
                               'TEMPLATE'   => 'text',
                               'SUBJECT'    => 'text',
                               'MAILFROM'   => 'text'
                               );


             $result = $surveyFormObj->addNewSurveyForm($data, $dataType);

             if ($result)
             {
                 $this->takeFormLabels($num_fields,
                                       $formname,
                                       $result);

             } else {

                 $this->show_status($this->getMessage('FORM_UPLOAD_FAILED'),
                                    $SURVEY_MNGR);
             }
          }

       }

       function takeFormLabels($num, $name, $formid)
       {
          global $SURVEY_ADD_LABEL_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $SURVEY_MNGR,
                 $APP_SURVEY_FORM_MNGR;

          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $SURVEY_ADD_LABEL_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_block('mainBlock', 'labelBlock', 'lblock');
          $template->set_var('FORM_NAME', stripslashes($name));
          $template->set_var('FORM_ID', $formid);
          for ($i=1;$i<=$num;$i++)
          {
             $template->set_var('LABEL_SERIAL',$i);
             $template->parse('lblock', 'labelBlock', true);

          }

          $template->set_var('SURVEY_FORM_MNGR', $APP_SURVEY_FORM_MNGR);
          $template->set_var('BASE_URL', $this->getBaseURL());
          //$template->set_var('TODAY', $today);
          $template->set_var('SURVEY_MNGR', $SURVEY_MNGR);
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }

       function addLabels()
       {
          $labels = $this->getRequestField('labels');
          $formid = $this->getRequestField('formid');

          global $SURVEY_MNGR;

          $formObj = new SurveyForm($this->dbi);
          $lcounter = 1;
          foreach ($labels as $label)
          {
             $status = $formObj->addLabel($formid, $lcounter++, $label);
             if (!$status) {
                $this->alert('LBL_ADD_FAILED');
                return;
             }
          }
          if ($status)
          {
              $this->show_status($this->getMessage('FORM_UPLOAD_SUCCESSFUL'),
                                 $SURVEY_MNGR);
          }
          else {

              $this->show_status($this->getMessage('FORM_UPLOAD_FAILED'),
                                 $SURVEY_MNGR);
          }
       }

       function delForm()
       {
           $form_id = $this->getRequestField('form_id');
           global $SURVEY_MNGR;

           if (!$form_id)
           {
              $this->alert('FORM_NOT_SELECTED');
           }

           $formObj = new SurveyForm($this->dbi, $form_id);

           $status = $formObj->deleteForm();

           if ($status)
           {
               $this->show_status($this->getMessage('FORM_DELETED'),
                                  $SURVEY_MNGR);
           } else {
               $this->show_status($this->getMessage('FORM_NOT_DELETED'),
                                  $SURVEY_MNGR);
           }
       }

   }//class


   /* Session variables must be defined before session_start() method is called */
   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $SURVEY_DB_URL;

   $thisApp = new surveyFormMngr(
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
