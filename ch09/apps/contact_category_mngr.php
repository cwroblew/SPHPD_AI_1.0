<?php
   require_once "contact.conf";
   require_once $THEME_CLASS;
   require_once $CATEGORY_CLASS;
   require_once $CONTACT_CLASS;
   
   class contactCategoryMngr extends PHPApplication {

     function run()
     {    
        $cmd = strtolower($this->getRequestField('cmd'));
        $themeObj = new Theme($this->dbi,null,'pr_tool');
        $this->themeObj = $themeObj;
        $this->theme = $themeObj->getUserTheme($this->getUID());
        if (!strcmp($cmd, 'add'))
        {
           $this->addDriver();	
        }
        else if (!strcmp($cmd, 'modify'))
        {  
           $this->modifyDriver();	
        }
        else if (!strcmp($cmd, 'delete'))
        {
           $this->deleteDriver();	
        }
     }
     
     function authorize()
     {
     	$this->setUserType();
     	return $this->isAdmin;
     }
     
     function setUserType()
     {
        if ($this->getUID() > 0)
        {
             global $USER_DB_URL;
             $user_dbi = new DBI($USER_DB_URL);
             $userObj = new User($user_dbi, $this->getUID());             
             if ($userObj->getType() == CONTACT_ADMIN_TYPE)
             {
                $this->isAdmin = true;
             }
        }
        else $this->isAdmin = false;
     }
     
     function addDriver()
     {
        $step = ($this->getRequestField('step'));
        if($step == 1 || empty($step))
        {
           $this->displayAddModifyMenu('add');	
        }
        else if($step == 2)
        {
           $this->addCategory();	
        } 
     }
     
     function deleteDriver()
     {
        $step = ($this->getRequestField('step'));
        if($step == 1 || empty($step))
        {
           $this->displayDeleteOptions();	
        }
        else if($step == 2)
        {
           $this->deleteCategory();	
        } 
     }
     
     function deleteCategory()
     {
        $type = ($this->getRequestField('type'));
        $cat_id = ($this->getRequestField('cat_id'));
        $del_opt = ($this->getRequestField('del_opt'));
        $tr_cat = ($this->getRequestField('tr_cat'));
        
        $catObj = new Category($this->dbi);
        $contactObj = new Contact($this->dbi);
        switch ($del_opt) 
        {
          case 1:
             if (!strcmp($type, 'parent'))
             {
                $subCats = $catObj->getSubCategories($cat_id);
                if (!empty($subCats))
                {
                   while (list($cid, $cname) = each($subCats))
                   {
                      $this->deleteContactsByCatID($cid);
                      $catObj->deleteCategory($cid);
                   }                   	
                }                
             }
             else
             {
                $this->deleteContactsByCatID($cat_id);
             }                          
             break;
             case 2:
             if ($tr_cat <= 0)
             {
                $this->alert('CAT_MISSING');
                exit;
             }
             if (!strcmp($type, 'parent'))
             {
                $catObj->replaceParentCat($cat_id, $tr_cat);
             }
             else
             {
             	$contactObj->replaceCategory($cat_id, $tr_cat);
             }
             break;          
        }
        $status = $catObj->deleteCategory($cat_id);
        $this->showContents($status ? $this->getMessage('CATEGORY_DELETED') : $this->getMessage('CATEGORY_NOT_DELETED'));
     }
     
     function deleteContactsByCatID($cid)
     {
        $contactObj = new Contact($this->dbi);
        $contacts = $contactObj->getContactsByCatID($cid);
        if (!empty($contacts))
        {
           while (list($contact_id, $info) = each($contacts))
           {
            $contactObj->deleteContact($contact_id);	
           }
        }  
     }
     
     function displayDeleteOptions()
     {
        global $REL_APP_PATH;
        $cat_id = ($this->getRequestField('cat_id'));
        
        if (empty($cat_id))
        {
           $this->alert('CAT_MISSING');	
        }
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_CAT_DEL_OPT_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        
        $template->set_var('DEL_CAT_ID', $cat_id);
        $template->set_var('CONTACT_CAT_MNGR', $REL_APP_PATH.'/'.CONTACT_CAT_MNGR);
        
        $catObj = new Category($this->dbi);
        $parents = $catObj->getParentCategories();
        
        if (in_array($cat_id, array_keys($parents)))                               //the cat was a parent cat.
        {                                                                          
            $template->set_var('TYPE', 'parent');
            $template->set_var('DIS_OPT', null);                                   
            while(list($cid, $cname) = each($parents))                             
            {                                                                      
               if($cid != $cat_id)                                                 
               {                                                                   
                  $template->set_var('CAT_NAME', $cname);                          
                  $template->set_var('CAT_ID', $cid);                              
                  $template->parse('cat','catBlock',true);	                   
               }                                                                   
            }                                                                      
        }                                                                          
        else                                                                       //the cat was a subcat
        {
            $template->set_var('TYPE', 'child');
            $template->set_var('DIS_OPT', 'disabled');
            $subCats = $catObj->getSubCategories($catObj->getParentOf($cat_id));
            while(list($cid, $cname) = each($subCats))
            {
               if($cid != $cat_id)
               {   
                  $template->set_var('CAT_NAME', $cname);
                  $template->set_var('CAT_ID', $cid);
                  $template->parse('cat','catBlock',true);	
               }   
            }  
        }
        $this->showContents($template->parse('main', 'mainBlock'));
     }
     
     function modifyDriver()
     {
        
        $step = ($this->getRequestField('step'));
        if($step == 1 || empty($step))
        {
           $this->displayAddModifyMenu('modify');	
        }
        else if($step == 2)
        {
           $this->modifyCategory();	
        } 
     }
     
     function displayAddModifyMenu($mode)
     {
       	
       	$cat_id = ($this->getRequestField('cat_id'));
       	$template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_CAT_ADD_MOD_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'parentBlock', 'parent');
       	
       	$catObj = new Category($this->dbi);
       	if(!strcmp($mode, 'modify'))
        {
           if (empty($cat_id))
           {
             $this->alert('SELECT_CAT');
             exit;
           }
           $modCatName = $catObj->getCategoryName($cat_id);
           $modCatDesc = $catObj->getCategoryDesc($cat_id);
           $modCatParent = $catObj->getCategoryParent($cat_id);
        }
        $template->set_var('MODE', ucwords($mode));
        
        $parents = $catObj->getParentCategories();
        
        if (!empty($parents))
        {
           while(list($cid, $cname) = each($parents))
           {
              if($cid != $cat_id)
              {   
                 $template->set_var('PARENT_NAME', $cname);
                 $template->set_var('PARENT_ID', $cid);
                 $template->set_var('CHOSEN', (isset($modCatParent) && $cid == $modCatParent) ? 'selected': null);
                 $template->parse('parent','parentBlock',true);	
              }   
           }	
        }
        else
        {
           $template->set_var('parent', null);	
        }
        $template->set_var(array(
        			 'CAT_NAME'          =>    isset($modCatName) ? $modCatName : NULL,
        			 'CAT_DESC'          =>    isset($modCatDesc) ? $modCatDesc : NULL,
        			 'PARENT_CHECKED'    =>    (isset($modCatParent) && $modCatParent) ? null : 'CHECKED',
        			 'CHILD_CHECKED'     =>    (isset($modCatParent) && $modCatParent) ? 'CHECKED' : null,
        			 'MOD_CAT_ID'        =>    isset($cat_id) ? $cat_id : NULL 
                                )
                          );
        
        global $REL_APP_PATH;
        $template->set_var('CONTACT_CAT_MNGR', $REL_APP_PATH.'/'.CONTACT_CAT_MNGR);
        $this->showContents($template->parse('mblock', 'mainBlock'));
     }
     
     function addCategory()
     {
     	$cat_name = ($this->getRequestField('cat_name'));
     	$parent_cat = ($this->getRequestField('parent_cat'));
     	$cat_option = ($this->getRequestField('cat_option'));
     	$cat_desc = ($this->getRequestField('cat_desc'));
     	
     	$parent_cat = !strcmp($cat_option, 'parent') ? 0 : $parent_cat;
     	
     	$catObj = new Category($this->dbi);
     	$params = array(
     	                  'CAT_ID'      =>  'null',
     	                  'CAT_NAME'    =>  $cat_name,
     	                  'CAT_DESC'    =>  $cat_desc,
     	                  'CAT_PARENT'  =>  $parent_cat
     	               );
     	$cat_id = $catObj->addCategory($params);
     	$this->showContents($cat_id ? $this->getMessage('CATEGORY_ADDED') : $this->getMessage('CATEGORY_NOT_ADDED'));
     }
     
     function modifyCategory()
     {
     	$cat_id = ($this->getRequestField('cat_id'));
     	$cat_option = ($this->getRequestField('cat_option'));
     	$cat_name = ($this->getRequestField('cat_name'));
     	$parent_cat = ($this->getRequestField('parent_cat'));
     	$cat_desc = ($this->getRequestField('cat_desc'));
     	
     	
     	$catObj = new Category($this->dbi);
     	
     	$parent_cat = !strcmp($cat_option, 'parent') ? 0 : $parent_cat;
     	
     	if (empty($cat_id))
     	{
     	   $this->alert('CAT_MISSING');	
     	   exit;
     	}
     	
     	if($parent_cat > 0 && $catObj->hasChild($cat_id))
     	{
     	   $this->alert('HAS_CHILD_SUB_IMPOSSIBLE');
     	   exit;	
     	}
     	if($parent_cat > 0 && $parent_cat == $cat_id)
     	{
     	   $this->alert('PARENT_CHILD_SAME');
     	   exit;     		
     	}
     	$params = array(
     	                  'CAT_ID'      =>  $cat_id,
     	                  'CAT_NAME'    =>  $cat_name,
     	                  'CAT_DESC'    =>  $cat_desc,
     	                  'CAT_PARENT'  =>  $parent_cat
     	               );
     	$status = $catObj->modifyCategory($params);
     	$this->showContents($status ? $this->getMessage('CATEGORY_MODIFIED') : $this->getMessage('CATEGORY_NOT_MODIFIED'));
     }
      
     function showContents($contents)
     {
        global $THEME_TEMPLATE;
        global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
        global $SESSION_AUTO_TIP_SHOWN;
        global $REL_TEMPLATE_DIR;
        global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
        global $TEMPLATE_DIR;
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
        $template->set_file('fh1', STATUS_TEMPLATE);
        $template->set_block('fh1','mainBlock','mblock');
        $template->set_var('STATUS_MESSAGE', $contents);
        $themeTemplate->set_var('CONTENT_BLOCK', $template->parse('mblock', 'mainBlock'));
        $themeTemplate->set_var('SERVER_NAME', $this->get_server());
        $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
        $themeTemplate->parse('cnblock', 'contentBlock');
        $themeTemplate->parse('mmblock', 'mmainBlock');
        $themeTemplate->pparse('output', 'fh');	
     }
   }//class
   
   

   
   $thisApp = new contactCategoryMngr(array
                                           ('app_name'             =>  $APPLICATION_NAME,
                                            'app_version'           => '1.0.0',
                                            'app_type'              => 'WEB',
                                            'app_auto_connect'      => TRUE,
                                            'app_auto_authorize'    => TRUE,
                                            'app_auto_chk_session'  => TRUE,
                                            'app_debugger'          => $OFF,
                                            'app_db_url'            => $CONTACT_DB_URL,
                                           )
                                     );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>