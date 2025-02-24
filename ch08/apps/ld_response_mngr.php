<?php

   require_once "ld.conf";
   require_once $CATEGORY_CLASS;
   require_once $DOC_CLASS;
   require_once $RESPONSE_CLASS;
   require_once $THEME_CLASS;
   
   class prResponseMngr extends PHPApplication {

     function run()
     {
          global $LD_RESPONSE_TEMPLATE, $LD_VIEW_RESPONSE_TEMPLATE;
          
          $this->uid = $this->getUID();
          $cmd = $this->getRequestField('cmd');

          $themeObj = new Theme($this->dbi,null,'ld_tool');

          $this->themeObj = $themeObj;

          $this->theme = $themeObj->getUserTheme($this->uid); 
          
          if (empty($cmd))
          {
             $this->displayResponseForm($LD_RESPONSE_TEMPLATE);
          }
          else if (!strcmp($cmd, 'submit'))
          {
             $this->submitResponse();	
          }
          else if (!strcmp($cmd, 'view'))
          {
             $this->showResponse($LD_VIEW_RESPONSE_TEMPLATE);	
          }
     }
     
     function showResponse($templateFile = null, $mainMenu = null)
     {
          global $LD_MNGR, $LD_DETAILS_MNGR, $LD_RESPONSE_MNGR;
          global $THEME_TEMPLATE, $REL_APP_PATH;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          
          $rid = $this->getRequestField('rid');
          
          $themeTemplate = new Template($THEME_TEMPLATE_DIR);

          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
          
          
          $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
          
          
          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);


          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
          
          $respObj = new Response($this->dbi, $rid);
          $nid = $respObj->getResponseDocID();
          $docObj = new Doc($this->dbi, $nid);
          
          $docHeading = $docObj->getHeading();
          $docPublishDate = $docObj->getPublishDate();
          $responderName = $respObj->getResponder();
          $responseHeading = $respObj->getResponseSubject();
          $responseBody = $respObj->getResponseBody();
          
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $templateFile);

          $template->set_block('fh','mainBlock', 'main');
          
          $template->set_var(array
                                  (
                                    'DOC_HEADING'      =>    $docHeading,
                                    'PUBLISH_DATE'      =>    date("m/d/Y",$docPublishDate),
                                    'RESPONSE_HEADING'  =>    $responseHeading,
                                    'RESPONDER'         =>    $responderName,
                                    'RESPONSE_BODY'     =>    $responseBody,
                                    'DOC_ID'           =>    $nid,
                                    'LD_MNGR'           =>    $REL_APP_PATH.'/'.$LD_MNGR,
                                    'LD_DETAILS_MNGR'   =>    $REL_APP_PATH.'/'.$LD_DETAILS_MNGR,
                                    'LD_RESPONSE_MNGR'  =>    $REL_APP_PATH.'/'.$LD_RESPONSE_MNGR,
                                  )
                            );
          
          
          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);

          //$template->set_var('USER_NAME', ucfirst($thisUser->getName()));

          $themeTemplate->set_var('CONTENT_BLOCK',
                                  $template->parse('mblock', 'mainBlock'));

          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');          
          
          
          
     }
     
     function submitResponse()
     {
          global $LD_MNGR;
          
          $nid = $this->getRequestField('nid');
          $sub = $this->getRequestField('sub');
          $rate = $this->getRequestField('rate');
          $comment = $this->getRequestField('comment');
          $ts = $this->getRequestField('ts');
          
          
          if (empty($nid) || empty($sub) || empty($rate) || empty($comment))
          {
             $this->alert('INPUT_MISSING');	
             exit;
          }
          $params = array(
                           'RESPONSE_ID'   =>  'null',
                           'RESPONDER'     =>  strtolower($this->getSessionField('SESSION_USERNAME')),
                           'SUBJECT'       =>  $sub,
                           'RATE'          =>  $rate,
                           'COMMENT'       =>  $comment,
                           'DOC_ID'       =>  $nid,
                           'RESPONSE_TS'   =>  $ts
                         );
                         
          $responseObj = new Response($this->dbi);
          $status = $responseObj->addResponse($params);
          if ($status)
          {
             $this->showStatusMessage($this->getMessage('RESPONSE_ADDED'));	
          }
          else
          {
             $this->showStatusMessage($this->getMessage('RESPONSE_NOT_ADDED'));		
          }         	
     }
     
     function showStatusMessage($statusMessage)
     {
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         
         global $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         global $TEMPLATE_DIR;
         global $STATUS_TEMPLATE;

         $themeTemplate = new Template($THEME_TEMPLATE_DIR);

         $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
         $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
         $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
         

         $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
         $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
         $themeTemplate->set_var('PHOTO', $photo);

         $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);


         $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));



         $template = new Template($TEMPLATE_DIR);

         $template->set_file('fh1', $STATUS_TEMPLATE);
         $template->set_block('fh1','mainBlock','mblock');


         
         $template->set_var('STATUS_MESSAGE', ($statusMessage));
         

         $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));

         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);


         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');
	
     }

     function displayResponseForm($templateFile = null, $mainMenu = null)
     {
          global $LD_MNGR, $LD_DETAILS_MNGR, $LD_RESPONSE_MNGR;
          global $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          $nid = $this->getRequestField('nid');
          
          $themeTemplate = new Template($THEME_TEMPLATE_DIR);

          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
          
          
          $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
          
          
          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);


          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
          
          if (empty($nid))
          {
             $this->alert('DOC_ID_MISSING');	
             exit;
          }
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $templateFile);

          $template->set_block('fh','mainBlock', 'main');
          
          $docObj = new Doc($this->dbi, $nid);
          
          
          global $REL_APP_PATH;
          $template->set_var(array
                                  (
                                   'HEADING'          =>  $docObj->getHeading(),
                                   'DOC_ID'           =>    $nid,
                                   'LD_MNGR'           =>    $REL_APP_PATH.'/'.$LD_MNGR,
                                   'LD_DETAILS_MNGR'   =>    $REL_APP_PATH.'/'.$LD_DETAILS_MNGR,
                                   'LD_RESPONSE_MNGR'  =>    $REL_APP_PATH.'/'.$LD_RESPONSE_MNGR,
                                   'TS'                =>    mktime()
                                  )
                             );
          
          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);

          //$template->set_var('USER_NAME', ucfirst($thisUser->getName()));

          $themeTemplate->set_var('CONTENT_BLOCK',
                                  $template->parse('mblock', 'mainBlock'));

          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');
      }
      


   }//class
   
   

   
   $thisApp = new prResponseMngr(
                             array('app_name'              =>  $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_auto_chk_session'  => TRUE,
                                  'app_debugger'          => $OFF,
                                  'app_db_url'            => $LD_DB_URL,
                                   )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
