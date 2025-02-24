<?php

   require_once "ld.conf";
   require_once $CATEGORY_CLASS;
   require_once $DOC_CLASS;
   require_once $RESPONSE_CLASS;
   
   require_once $THEME_CLASS;
   
   
   class prDetailMngr extends PHPApplication {

     function run()
     {
          global $LD_DETAILS_TEMPLATE;
          
          $this->uid = $this->getUID();

          $themeObj = new Theme($this->dbi,null,'ld_tool');

          $this->themeObj = $themeObj;

          $this->theme = $themeObj->getUserTheme($this->uid); 

          $this->setUserType();
          $this->displayDocDetail($LD_DETAILS_TEMPLATE);
     }
     
     function setUserType()
     {
         if ($this->getUID() > 0)
          {
             $user_dbi = new DBI(USER_DB_URL);
             $userObj = new User($user_dbi, $this->getUID());
             
             if ($userObj->getType() == LD_ADMIN_TYPE)
             {
                $this->isAdmin = true;
             }
          }
          else $this->isAdmin = false;	
     }

     function displayDocDetail($templateFile = null, $mainMenu = null)
     {
          global $LD_MNGR, $LD_DETAILS_MNGR, $LD_RESPONSE_MNGR, $LD_ADMIN_MNGR;
          global $THEME_TEMPLATE, $REL_APP_PATH;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          
          $themeTemplate = new Template($THEME_TEMPLATE_DIR);
          
          $nid = $this->getRequestField('nid');
          global $ratings;

          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
          
          $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
          
          
          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);


          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
          
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $templateFile);

          $template->set_block('fh','mainBlock', 'main');
          $template->set_block('mainBlock', 'responseBlock', 'response');
          $template->set_block('mainBlock', 'adminBlock', 'admin');
          $template->set_block('responseBlock', 'chkBlock', 'chk');
          
          if (empty($nid))
          {
            $this->alert('DOC_ID_MISSING');	
          }
          $docObj = new Doc($this->dbi, $nid);
          $docObj->trackVisit($nid, $this->getUID(), mktime());
          $respObj =  new Response($this->dbi);
          
          $template->set_var(array
                                  (
                                   'HEADING'          =>  $docObj->getHeading(),
                                   'PUBLISH_DATE'     =>  date("m/d/Y", $docObj->getPublishDate()),
                                   'BODY'             =>  $docObj->getBody()
                                  )
                            );
                            
          $responses = $respObj->getResponsesByDocID($nid);
          if (!empty($responses))
          {
              while (list($rid, $info) =  each($responses))
              {
                  //$template->set_var('response', null);
                  if (isset($this->isAdmin) && $this->isAdmin)
                  {
                     $template->set_var('RESPONSE_ID', $rid);
                     $template->parse('chk', 'chkBlock', false);	
                  }
                  else
                  {
                     $template->set_var('chk', null);
                  }
                  
                  $template->set_var('RESPONSE_ID', $rid);
                  $template->set_var('RESPONSE_HEADING', stripslashes($info->SUBJECT));
                  $template->set_var('RESPONDER', stripslashes($info->RESPONDER));
                  $template->parse('response', 'responseBlock', true);
              }
          }
          else
          {
              $template->set_var('response', null);	
          }
          
          $template->set_var('TOTAL_RESPONSE', $respObj->getTotalResponseByDocID($nid));
          $template->set_var('STAR_VAL', $ratings[round($respObj->getAvgRatingByDocID($nid))]);
          
          if (isset($this->isAdmin) && $this->isAdmin)
          {
             $template->parse('admin', 'adminBlock', false);	
          }
          else
          {
             $template->set_var('admin', null);	
          }

          $trackArr = $docObj->getTrackDetails($nid);
           
          global $LD_VISIT_LIST_MNGR;
          $template->set_var(array
                                  (
                                   'LD_MNGR'           =>    $REL_APP_PATH.'/'.$LD_MNGR,
                                   'LD_DETAILS_MNGR'   =>    $REL_APP_PATH.'/'.$LD_DETAILS_MNGR,
                                   'LD_RESPONSE_MNGR'  =>    $REL_APP_PATH.'/'.$LD_RESPONSE_MNGR,
                                   'LD_ADMIN_MNGR'     =>    $REL_APP_PATH.'/'.$LD_ADMIN_MNGR,
                                   'LD_VISIT_LIST_MNGR'=>    $REL_APP_PATH.'/'.$LD_VISIT_LIST_MNGR,
                                   'DOC_ID'           =>    $nid,
                                   'NUM_VISIT'         =>    sizeof($trackArr)
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
          /*$template->parse('main', 'mainBlock', false);

          $template->pparse('output', 'fh');*/
      }


   }//class
   
   

   
   $thisApp = new prDetailMngr(
                             array('app_name'              =>  $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_auto_chk_session'  => FALSE,
                                  'app_debugger'          => $OFF,
                                  'app_db_url'            => $LD_DB_URL,
                                   )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
