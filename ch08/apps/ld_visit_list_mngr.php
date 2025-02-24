<?php

   require_once "ld.conf";
   require_once $CATEGORY_CLASS;
   require_once $DOC_CLASS;
   require_once $RESPONSE_CLASS;
   
   require_once $THEME_CLASS;
   

   class prListMngr extends PHPApplication {

     function run()
     {
          global $LD_VISIT_LIST_TEMPLATE;
          

          
          $this->uid = $this->getUID();

          $themeObj = new Theme($this->dbi,null,'ld_tool');

          $this->themeObj = $themeObj;

          $this->theme = $themeObj->getUserTheme($this->uid); 

          //$this->setUserType();
          $this->displayDocVisitList($LD_VISIT_LIST_TEMPLATE);
     }
     
     function authorize()
     {
        return true;	
     }
     
     function displayDocVisitList($templateFile = null, $mainMenu = null)
     {
          
          $nid = $this->getRequestField('nid');
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $templateFile);

          $template->set_block('fh','mainBlock', 'main');
          $template->set_block('mainBlock', 'visitorBlock', 'visitor');
          
          if (empty($nid))
          {
            $this->alert('DOC_ID_MISSING');	
          }
          $docObj = new Doc($this->dbi, $nid);
          
          
          $heading = stripslashes($docObj->getHeading());
          $template->set_var('DOC_HEADING', $heading);
          $trackArr = $docObj->getTrackDetails($nid);
          
          $user_dbi = new DBI(USER_DB_URL);
          $i = 0; 
          foreach($trackArr as $trackInfo)
          {
             $template->set_var('ROW_COLOR', ($i++%2) ? ODD_COLOR : EVEN_COLOR);
             $userObj = new User($user_dbi, $trackInfo->UID);
             $template->set_var('UNAME', $userObj->getEMAIL());
             $template->set_var('DATE', date("m/d/Y", $trackInfo->VISIT_TS));
             $template->set_var('TIME', date("h:i a", $trackInfo->VISIT_TS));
             $template->parse('visitor', 'visitorBlock', true);	
          }
          
          
                             
          
          //$template->set_var('USER_NAME', ucfirst($thisUser->getName()));

          $template->parse('main', 'mainBlock', false);

          

          $template->pparse('output', 'fh');
      }


   }//class
   
   

   
   $thisApp = new prListMngr(
                             array('app_name'              =>  $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_authorize'    => TRUE,
                                  'app_auto_chk_session'  => TRUE,
                                  'app_debugger'          => $OFF,
                                  'app_db_url'            => $LD_DB_URL,
                                   )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
