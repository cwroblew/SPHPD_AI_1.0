<?php

   require_once "ld.conf";
   require_once $CATEGORY_CLASS;
   require_once $DOC_CLASS;
   require_once $RESPONSE_CLASS;
   require_once $THEME_CLASS;
   
   
   global $SESSION_USER_ID;
   class prMngr extends PHPApplication {

     function run()
     {
          global $LD_HOME_TEMPLATE;
          
          $this->uid = $this->getUID();
          
          $themeObj = new Theme($this->dbi,null,'ld_tool');

          $this->themeObj = $themeObj;

          $this->theme = $themeObj->getUserTheme($this->getUID());

          $this->displayDocHome($LD_HOME_TEMPLATE);          
          
     }
     
     function authorize()
     {
        $cat = $this->getRequestField('cat');
        
        $this->setUserType();
        if (empty($cat))
        {
           if (!isset($this->isAdmin) || !$this->isAdmin)
           {
              return false;	
           }
           else
           {
              return true;	
           }   
        }
        else
        {
           if(isset($this->isAdmin) && $this->isAdmin)
           {
              return true;	
           }
           $catObj = new Category($this->dbi);
           $cid = $catObj->getCategoryIDbyName($cat);
           if (!$catObj->isViewable($cid, $this->getUID()) && !$catObj->isPublishable($cid, $this->getUID()))
           {
              return false;	
           }
           else
           {
              return true;	
           }
        }
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

     function displayDocHome($templateFile = null, $mainMenu = null)
     {
          global $LD_MNGR, $LD_DETAILS_MNGR, $LD_ADMIN_MNGR;
          global $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          
          $cat = $this->getRequestField('cat');
          $cid = $this->getRequestField('cid');
          
          $themeTemplate = new Template($THEME_TEMPLATE_DIR);

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
          $template->set_block('mainBlock', 'catBlock', 'cat');
          
          $template->set_var('cat', null);
          
          $template->set_block('catBlock', 'catchkBlock', 'catchkbox');
          $template->set_block('mainBlock', 'adminBlock', 'admin');
          $template->set_block('adminBlock', 'delModCatBlock', 'dmc');
          $template->set_block('adminBlock', 'addCatBlock', 'addCat');
          $template->set_block('catBlock', 'headingBlock', 'heading');
          $template->set_block('headingBlock', 'chkBlock', 'chkbox');
          
          $catObj = new Category($this->dbi);
          $docObj = new Doc($this->dbi);
          $respObj =  new Response($this->dbi);
          if (empty($cat))
          {
          	$categories = $catObj->getCategories();
          	$template->parse('dmc', 'delModCatBlock', false);
          	$template->parse('addCat', 'addCatBlock', false);
          	$template->set_var('CATNAME', null);
          }
          else
          {
               $template->set_var('CATNAME', $cat);
               $template->set_var('dmc', null);
               $template->set_var('addCat', null);
               $cid = $catObj->getCategoryIDbyName($cat);                  
               $categories = array
                                 (
                                   $cid => $cat
                                 );
               
          }	
          //$this->dump_array($categories);
          
          
          
          if (!empty($categories))
          {
              while (list($cid, $cname) =  each($categories))
              {
                 $template->set_var('heading', null);
                 if (!isset($this->isAdmin) || !$this->isAdmin)
                 {
                    $template->set_var('catchkbox', null);
                 }
                 else
                 {
                    if (empty($cat))
                    {
                      $template->set_var('CAT_ID', $cid);
                      $template->parse('catchkbox', 'catchkBlock', false);	
                    }
                    else
                    {
                      $template->set_var('catchkbox', null);
                    }
                 }
                 if ((!isset($this->isAdmin) ||!$this->isAdmin) && !$catObj->isPublishable($cid, $this->uid))
                 {
                    $doces = $docObj->getDocesByCatID($cid);
                 }
                 else
                 {
                     $doces = $docObj->getAllDocesByCatID($cid);	
                 }   
                 
                 if (!empty($doces))
                 {
                     while (list($nid, $info) = each($doces))
                     {
                        $num_resp = $respObj->getTotalResponseByDocID($nid);
                        $template->set_var('DOC_ID', $nid);
                        $template->set_var('HEADING', stripslashes($info->HEADING));
                        $template->set_var('NUM_RESP', empty($num_resp) ? null : "[$num_resp]");
                        $template->set_var('PUB_DATE', date("m/d/Y",$info->PUBLISH_DATE));
                        if ((!isset($this->isAdmin) || !$this->isAdmin)  && !$catObj->isPublishable($cid, $this->uid))
                        {
                           $template->set_var('chkbox', null);
                        }
                        else
                        {
                           $template->set_var('DOC_ID', $nid);
                           $template->parse('chkbox', 'chkBlock', false);	
                        }
                        
                        $template->parse('heading', 'headingBlock', true);
                     }
                     $template->set_var('CAT_NAME', stripslashes($cname));
                     $cObj = new Category($this->dbi);
                     $cdesc = $cObj->getCategoryDesc($cid);
                     $template->set_var('CAT_DESC', stripslashes($cdesc));
                     $template->parse('cat', 'catBlock', true);
                 }
                 else
                 {
                     if ($this->isAdmin  || $catObj->isPublishable($cid, $this->uid))
                     {
                        $template->set_var('heading', null);
                        $template->set_var('CAT_NAME', stripslashes($cname));
                        $cObj = new Category($this->dbi);
                        $cdesc = $cObj->getCategoryDesc($cid);
                        $template->set_var('CAT_DESC', stripslashes($cdesc));
                        $template->parse('cat', 'catBlock', true);
                        
                     }   
                 }
              }
          }
          else
          {
              $template->set_var('cat', null);	
          }    
          
          if ((isset($this->isAdmin) && $this->isAdmin)  || $catObj->isPublishable($cid, $this->uid))
          {
             $template->parse('admin', 'adminBlock', false);	
          }
          else
          {
             $template->set_var('admin', null);	
          }
          
          global $REL_APP_PATH; 
          $template->set_var(array
                                  (
                                   'LD_MNGR'           =>    $REL_APP_PATH.'/'.$LD_MNGR,
                                   'LD_DETAILS_MNGR'   =>    $REL_APP_PATH.'/'.$LD_DETAILS_MNGR,
                                   'LD_ADMIN_MNGR'     =>    $REL_APP_PATH.'/'.$LD_ADMIN_MNGR
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
   
   

   
   $thisApp = new prMngr(
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
