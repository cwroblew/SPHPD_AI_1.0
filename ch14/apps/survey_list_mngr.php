<?php

   require_once "survey.conf";
   require_once $SURVEY_LIST_CLASS;

   /* Session variables must be defined before session_start()
      method is called */

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   class surveyListMngr extends PHPApplication {

      function run()
      {
          $cmd = strtolower($this->getRequestField('cmd'));

          if (empty($cmd) || !strcmp($cmd, 'upload'))
          {

              $this->addDriver();

          } else {

             $this->delList();
          }
     }

      function addDriver()
      {
         $step = $this->getRequestField('step');


          if (empty($step))
          {
             $this->displayAddListMenu();

          } else {

             $this->addList();
          }
      }

      function authorize()
      {
          return TRUE;
      }


      function displayAddListMenu()
      {
          global $SURVEY_ADD_LIST_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $REL_APP_PATH,
                 $SURVEY_MNGR,
                 $APP_SURVEY_LIST_MNGR;

          $today    = mktime();
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $SURVEY_ADD_LIST_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_var('SURVEY_LIST_MNGR', $APP_SURVEY_LIST_MNGR);
          $template->set_var('SURVEY_MNGR', $SURVEY_MNGR);
          $template->set_var('APP_PATH', $REL_APP_PATH);
          $template->set_var('TODAY', $today);
          $template->set_var('BASE_URL', $this->getBaseURL());

          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }

       function delList()
       {
           global $SURVEY_MNGR;

           $list_id = $this->getRequestField('list_id');
           
           if (empty($list_id))
           {
               $this->alert(LIST_NO_LIST_CHOSEN);
           }

           $listObject = new SurveyList($this->dbi, $list_id);

           $status = $listObject->deleteList();

           if ($status)
           {
              $this->show_status($this->getMessage('LIST_DELETE_SUCCESSFUL'),
                                 $SURVEY_MNGR);

           } else {

              $this->show_status($this->getMessage('LIST_DELETE_FAILED'),
                                 $SURVEY_MNGR);
           }

       }

       function addList()
       {
          global $UPLOAD_DIR;
          global $SURVEY_MNGR;
          
          
          $today = $this->getRequestField('today');          
          $listname = $this->getRequestField('listname');
          $email_filter = $this->getRequestField('email_filter');
          $ucword_filter = $this->getRequestField('ucword_filter');
          
          if (!is_array($_FILES['userfile']) || empty($listname))
              $this->alert('ADD_SURVEY_LIST_REQ_MISSING');

          else
          {
             copy($_FILES['userfile']['tmp_name'], $UPLOAD_DIR.'/'.$_FILES['userfile']['name']);

             

             $surveyListObj = new SurveyList($this->dbi);
             $result = $surveyListObj->addNewSurveyList($UPLOAD_DIR."/".$_FILES['userfile']['name'],
                                                        $listname,
                                                        $this->getUID(),
                                                        $_FILES['userfile']['name'], 
                                                        $today, 
                                                        $ucword_filter, 
                                                        $email_filter);
             if ($result)
             {

                 $this->show_status($this->getMessage('LIST_UPLOAD_SUCCESSFUL'),
                                    $SURVEY_MNGR);
             } else {
                 $this->show_status($this->getMessage('LIST_UPLOAD_FAILED'),
                                    $SURVEY_MNGR);
             }
          }


       }

   }//class

   global $SURVEY_DB_URL;

   $thisApp = new surveyListMngr(
                            array( 'app_name'    => $APPLICATION_NAME,
                                  'app_version' => '1.0.0',
                                  'app_type'    => 'WEB',
                                  'app_db_url'  => $SURVEY_DB_URL,
                                  'app_debugger'=> $OFF,
                                  'app_auto_connect' => TRUE,
                                  'app_auto_authorize' => TRUE,
                                  'app_auto_chk_session' => TRUE
                                 )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
