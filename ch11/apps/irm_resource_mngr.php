<?php

   require_once "irm.conf";
   
   class irmReourceMngr extends PHPApplication {

      function run()
      { 
         $cmd = strtolower($this->getRequestField('cmd'));

         global $IRM_MENU_TEMPLATE, $IRM_RESOURCE_DES_TEMPLATE;
         
         global $SESSION_USER_ID;       
        
         global $INTRA_DB_URL;
         
         $this->uid = $SESSION_USER_ID;
         
         $authDBI = new DBI($INTRA_DB_URL);
         
         $themeObj = new Theme($authDBI, null, 'irm');

         $this->themeObj = $themeObj;

         $this->theme = $themeObj->getUserTheme($this->getUID());
         
         if(!strcmp($cmd, 'add'))
         {
            $this->addDriver();
            
         } else if(!strcmp($cmd, 'delete')) {
         
            $this->delete();
         
         } else if(!strcmp($cmd, 'modify')) {
            
            $this->modifyDriver();
         
         } else if(!strcmp($cmd, 'disdes')) {
         
            $this->displayDescription($IRM_RESOURCE_DES_TEMPLATE);
         }
      }

   
      function addDriver()
      {
         $step = ($this->getRequestField('step'));
         
         global $IRM_RESOURCE_MENU_TEMPLATE;
         
         if($step == 2) {
         	
            $this->addResource();
         
         } else {
           
            $this->showAddMenu($IRM_RESOURCE_MENU_TEMPLATE);	
         }
      }

      function modifyDriver() 
      { 
         global $IRM_RESOURCE_MODIFY_MENU_TEMPLATE, $IRM_RESOURCE_MENU_TEMPLATE;
         
         $step = ($this->getRequestField('step'));
         
         if($step == 3) {
         
            $this->modifyResource();
         
         } else if($step == 2) {
         
            $this->showModifyMenu($IRM_RESOURCE_MENU_TEMPLATE);
         
         } else {
         
            $this->selectResource($IRM_RESOURCE_MODIFY_MENU_TEMPLATE);
         }
      }

      function populateCategory($template = null, $purpose = null, $selectValue = 0)
      {
         $categoryObj = new IrmCategory($this->dbi);

         $categoryList = array();

         $categoryList = $categoryObj->getCategoryList();

         $categoryIndex = 0;
         
         if(empty($categoryList))
         {
            return null;
         
         } else{
            $category = NULL;
            while (list($categoryid, $categoryname) = each ($categoryList))
            {
               $template->set_var('CATEGORY_ID', $categoryid);
            
               $template->set_var('CATEGO_NAME', $categoryname);
            
               $template->set_var('CATEGORY_INDEX' , $categoryIndex++);
            
               if(!strcmp($purpose, 'jsblock'))
               {            
                  $template->set_var('jsscblock', $this->populateSubCategory($template, $categoryid, 'jsblock'));   
                           
                  $category .= $template->parse('jscblock', 'jsCategoryBlock', true);
               
               } else{
                  
                  if($selectValue != $categoryid)
                  $category .= $template->parse('cblock', 'categoryBlock', true);
               }   
            }
         }
         return $category;
      }
      
      
      function populateSubCategory($template = null, $cat_id = null, $purpose = null)
      {
         $subcatagoryObj = new IrmCategory($this->dbi);
            
         $subcategoryList = array();
            
         $subcategoryList = $subcatagoryObj->getSubCategoryList($cat_id);
             
         $subcategoryIndex = 0;
               
         if(empty($subcategoryList))
         {
            return null;
            
         }else{
            
            while (list($subcategoryid, $subcategoryname) = each ($subcategoryList))
            {
               $subCategory = NULL;
               if(!strcmp($purpose, 'jsblock'))
               {
                  $template->set_var('SUBCATEGORY_INDEX' , $subcategoryIndex++);
            
                  $template->set_var('SUBCATEGORY_ID', $subcategoryid);
            
                  $template->set_var('SUBCATEGORY_NAME', $subcategoryname);
            
                  $subCategory .= $template->parse('jsscblock', 'jsSubCategoryBlock', true);
               
               } else if(!strcmp($purpose, 'scblock')){
               
                  $template->set_var('SUBCATEGORY_VALUE', $subcategoryid);
                  
                  $template->set_var('SUBCATEGORY_NAME', $subcategoryname);
                   
                  $subCategory .= $template->parse('scblock', 'subcategoryBlock', true);
               }   
            }
         }
         return $subCategory;
      }
      

      function selectResource($templateFile = null)
      {
      	 global $IRM_RESOURCE_MNGR, $REL_APP_PATH;

         $category = $this->getRequestField('category');
         $subcategory = $this->getRequestField('subcategory');
         $change = $this->getRequestField('change');
                  
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock','subCatBlock', 'sblock');
         $menuTemplate->set_block('mainBlock','resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         
         $catObj = new IrmCategory($this->dbi);
         
         $categoryList = $catObj->getCategoryList();
            
         foreach($categoryList as $key=>$value)
         {
            $menuTemplate->set_var(array( 'CAT_ID'    => $key,
                                          'CAT_NAME'  =>  $value,
                                          'SELE'      => ($category == $key) ?'selected':null
                                        )
                                  );
            $menuTemplate->parse('cblock', 'categoryBlock', true);
         }
             
         $subCategoryList = array();
         
         if(!empty($category))
         {   
            $subCategoryList = $catObj->getSubCategoryList($category);
         }
        
         $menuTemplate->set_var( array( 'SUB_ID'         => 0,
                                        'SUB_NAME'       => 'select a subcategory',
                                        'SELEC'          => 'selected'
                                      )
                               );
            
         if(!empty($subCategoryList))
         {
            $menuTemplate->parse('sblock','subCatBlock', true);   
            
            foreach($subCategoryList as $key=>$value)
            {  
               $menuTemplate->set_var( array(
                                                'SUB_ID'         => $key,
                                                'SUB_NAME'       => $value,
                                                'SELEC'          => ($subcategory != $key)?null:'selected'
                                               )
                                        );
               $menuTemplate->parse('sblock','subCatBlock', true);
            }
         } else {
            
               $menuTemplate->set_var( array( 'SUB_ID'         => 0,
                                              'SUB_NAME'       => 'No subcategory available',
                                              'SELEC'          => 'selected'
                                            )
                                     );
               $menuTemplate->parse('sblock','subCatBlock', true);
         }
               
         if(!empty($change)){
         
            $resourceList = array();
                  
            $resourceObj = new IrmResource($this->dbi);
            
            if($change == 2)
            {
               $resourceList = $resourceObj->getResourceByCategory($subcategory);
            
            } else {
            
               $resourceList = $resourceObj->getResourceByCategory($category);      
            }
            
            if(!empty($resourceList))
            {  
               $menuTemplate->set_var('RERSOURCE_ID', 0);
               $menuTemplate->set_var('RERSOURCE_NAME', 'Select a resource');
               
               $menuTemplate->parse('rblock', 'resourceBlock', true);
            	
               foreach($resourceList as $key=>$value)
               {
                  $menuTemplate->set_var('RERSOURCE_ID', $key);
                  $menuTemplate->set_var('RERSOURCE_NAME', $value);
                  
                  $menuTemplate->parse('rblock', 'resourceBlock', true);
               }
            } else {
                
                  $menuTemplate->set_var('RERSOURCE_ID', 0);
                  $menuTemplate->set_var('RERSOURCE_NAME', 'No resource Available');
                   
                  $menuTemplate->parse('rblock', 'resourceBlock', false);
            } 
         } else {
            
            $menuTemplate->set_var('RERSOURCE_ID', 0);
            $menuTemplate->set_var('RERSOURCE_NAME', 'Select a category');
                   
            $menuTemplate->parse('rblock', 'resourceBlock', false);
         }
         
         $menuTemplate->set_var(  array(
                                         'APP_PATH'           => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                         'IRM_RESOURCE_MNGR'  => $IRM_RESOURCE_MNGR
                                       )
                               );
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->displayWithTheme($output);
      }
          

      function showAddMenu($templateFile = null)
      {  
      	 global $APP_DB_URL;
                  
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         $menuTemplate->set_block('mainBlock', 'subcategoryBlock', 'scblock');
         $menuTemplate->set_block('mainBlock', 'jsCategoryBlock', 'jscblock');
         $menuTemplate->set_block('jsCategoryBlock', 'jsSubCategoryBlock', 'jsscblock');
         $menuTemplate->set_block('mainBlock','employeeBlock','eblock');
         
         $menuTemplate->set_var( array( 'CAT_SELECT_VALUE' => 0,
                                        'CAT_SELECT_NAME'  => 'Select a category',
                                        'SUBCATEGORY_NAME' => 'Select a Category first',
                                        'SUBCATEGORY_ID'   => 0,
                                        'TITLE_VALUE'      => null,
                                        'URL_VALUE'        => null,
                                        'RANK'             => 0,
                                        'RANK_VALUE'       => 'Select',
                                        'KEYWORD'          => null,
                                        'DESC'             => null,
                                        'C_TIME'           => mktime(),
                                        'PAGE_NAME'        => 'Add a New',
                                        'STEP'             => 2,
                                        'CMD'              => 'add'
                                      )
                               );
         $menuTemplate->set_var('scblock', null);
         
         $userList = array();
                  
         $authDBI = new DBI($APP_DB_URL);
         $userObj = new User($authDBI);
         $userList = $userObj->getUserList();
         
         foreach($userList as $key=>$value)
         {
            list($name, $domain) = explode('@', $value);
            
            $menuTemplate->set_var('EMP_ID',$key);
            $menuTemplate->set_var('EMP_NAME', ucwords($name));
            $menuTemplate->parse('eblock', 'employeeBlock', true);
         }
         $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock'));
         
         $menuTemplate->set_var('jscblock', $this->populateCategory($menuTemplate, 'jsblock'));
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->displayWithTheme($output);
      }


      function addResource()
      {
      	 global $IRM_MNGR, $REL_APP_PATH, $IRM_RESOURCE_TRACK_MNGR,
      	        $IRM_STATUS_TEMPLATE, $IRM_MOTD_TEMPLATE;
      	
         global $APP_DB_URL, $INTRA_DB_URL;
        
         $category = $this->getRequestField('category');
         $subcategory = $this->getRequestField('subcategory');
         $newcat = $this->getRequestField('newcat');
         $title = $this->getRequestField('title');
         $url = $this->getRequestField('url');
         $rating = $this->getRequestField('rating');
         $auto_motd = $this->getRequestField('auto_motd');
         $auto_motd_target = $this->getRequestField('auto_motd_target');
         $keywords = $this->getRequestField('keywords');
         $description = $this->getRequestField('description');
         $ctime = $this->getRequestField('ctime');
                  
         if( $category == 0 && empty($newcat))
         {
            $this->alert("CATEGORY_NAME_MISSING");
            return;
         }
         
         if(empty($title) || empty($url))
         {
            $this->alert("ESSENTIAL_FIELD_MISSING");
            return;
         }
         
         $fp = @fopen($url,"r"); 
         
         if(!$fp)
         {
            $this->alert("INCORRECT_URL");
            return;
         }
                           
         $categoryObj = new IrmCategory($this->dbi);
         
         $uid = $this->getUID();
         
         if(!empty($subcategory))
         {
            $resourceCategory = $subcategory; 
         
         } else if(!empty($category)) {
         
            if(empty($newcat)){
            
               $resourceCategory = $category;
            
            } else {
            
               $status = $categoryObj->existInList($newcat);
               
               if($status == 0)
               {
                  $status = $categoryObj->addCategory($newcat, $category, $uid);
                  
                  if($status) {
                     
                     $resourceCategory = $categoryObj->existInList($newcat);
                  }
               } else {
               
                  $resourceCategory = $status;
               }
            }
         } else {
         
            $status = $categoryObj->existInList($newcat);
            
            if($status == 0 )
            {
               $status = $categoryObj->addCategory($newcat, 0, $uid);
            
               if($status) {
                     
                  $resourceCategory = $categoryObj->existInList($newcat);
               }
            } else {
            
               $resourceCategory = $status;   
            }
         }
                 
         $now = mktime();
         
         $flag = $ctime.$uid;
         
         $url = strtolower($url);
         
         $prefix = substr($url, 0, strpos($url,':'));
         
         if(strcmp($prefix,'http') && strcmp($prefix,'ftp') && strcmp($prefix, 'news') )
         {
            $url = 'http://'.$url;
         }
         
         $params = array( 'RESOURCE_TITLE'        => $title,
                          'RESOURCE_LOCATION'     => $url,
                          'RESOURCE_CATEGORY'     => $resourceCategory,
                          'RESOURCE_RATING'       => $rating,
                          'RESOURCE_DESCRIPTION'  => $description,
                          'RESOURCE_ADDED_BY'     => $uid,
                          'CREATE_TS'             => $now,    
                          'FLAG'                  => $flag
                        );
          
         $resourceObj = new IrmResource($this->dbi);
         
         $status = $resourceObj->addResource($params);
         
         if ($status)
         {  
            $keywords = strtolower($keywords);
         
            $keywordArray = explode(" ", $keywords);
            
            $keywordStatus = $resourceObj->addKeywords($keywordArray, $status);
          
            $motdTemplate = new Template($this->getTemplateDir());
            $motdTemplate->set_file('fh', $IRM_MOTD_TEMPLATE);
            $motdTemplate->set_block('fh', 'mainBlock', 'main');
                        
            $authDBI = new DBI($APP_DB_URL);
            $userObj = new User($authDBI, $uid);
            $email = $userObj->getEMAIL();
            list($name, $domain) = explode('@', $email);
            
            $cate = $categoryObj->getCategoryName($resourceCategory);
            
            if($resourceCategory != $category && $category > 0)
            {
               $cate = $categoryObj->getCategoryName($category).' : '.$cate;
            }
            
            $motdTemplate->set_var( array(
                                          'USER'                   => $name,
                                          'IRM_NAME'               => $title,
                                          'CATEGORY_LIST'          => $cate,
                                          'IRM_RESOURCE_TRACK_MNGR'=> $IRM_RESOURCE_TRACK_MNGR,
                                          'APP_PATH'               => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                         ) 
                                   );
            $motdTemplate->set_var('R_ID', $status);
                        
            $motdMessage = $motdTemplate->parse('main', 'mainBlock', false);
            
            $intranetDBI = new DBI($INTRA_DB_URL);
            $messageObj = new Message($intranetDBI); 
            
            $flag = $flag.$uid;
            
            $motdID = $messageObj->addMessage('New Resource is added', $now, $motdMessage, $flag, $uid, 1);
            
            if($auto_motd == 1){
             
               if($auto_motd_target[0] == 0){
            
                  $au_motd[0]=0;
               
                  $messageObj->addViewer($motdID, $au_motd);
            	
               }else{
               
                  $messageObj->addViewer($motdID, $auto_motd_target);
               }
            }   
            
            $menuTemplate = new Template($this->getTemplateDir());
            $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
            $menuTemplate->set_block('fh', 'mainBlock', 'main');
            
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_ADD_SUCCESSFUL'));
            
            $message = $menuTemplate->parse('main', 'mainBlock', false);
             
         } else {
         
            $menuTemplate = new Template($this->getTemplateDir());
            $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
            $menuTemplate->set_block('fh', 'mainBlock', 'main');
            
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_ADD_FAILED'));
            
            $message = $menuTemplate->parse('main', 'mainBlock', false);
         }
         
         $this->displayWithTheme($message);         
      }
      
      
      function showModifyMenu($templateFile = null)
      {
         $resource = $this->getRequestField('resource');
      	 
      	 if($resource < 1)
      	 {
            $this->alert("RESOURCE_NAME_MISSING");
            return;
      	 }
                  
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         $menuTemplate->set_block('mainBlock', 'subcategoryBlock', 'scblock');
         $menuTemplate->set_block('mainBlock', 'jsCategoryBlock', 'jscblock');
         $menuTemplate->set_block('jsCategoryBlock', 'jsSubCategoryBlock', 'jsscblock');
         $menuTemplate->set_block('mainBlock','employeeBlock','eblock');
                  
         $resourceObj = new IrmResource($this->dbi);
         
         $resourceInfo = $resourceObj->getResourceInfo($resource);
         
         $resourceKeywords = $resourceObj->getKeywords($resource);
         
         if(!empty($resourceKeywords))
         {
            $keyword = NULL;
            foreach($resourceKeywords as $key=>$value)
            { 
               $keyword = $keyword.' '. $value;
            }
         }
         
         $categoryObj = new IrmCategory($this->dbi);
         
         $parentCategory = $categoryObj->getParentCategory($resourceInfo->RESOURCE_CATEGORY);
         
         if(empty($parentCategory))
         {
            $parentCategory = $resourceInfo->RESOURCE_CATEGORY;
            $childCategory = 0;
       
         } else{
            $childCategory = $resourceInfo->RESOURCE_CATEGORY;
         }
         
         $menuTemplate->set_var( array( 'TITLE_VALUE'      => $resourceInfo->RESOURCE_TITLE,
                                        'URL_VALUE'        => $resourceInfo->RESOURCE_LOCATION,
                                        'CAT_SELECT_VALUE' => $parentCategory,
                                        'CAT_SELECT_NAME'  => $categoryObj->getCategoryName($parentCategory),
                                        'SUBCATEGORY_ID'   => $childCategory,
                                        'SUBCATEGORY_NAME' => ($childCategory == 0)? 'Select a subcategory': $categoryObj->getCategoryName($childCategory),
                                        'RANK'             => $resourceInfo->RESOURCE_RATING,
                                        'RANK_VALUE'       => ($resourceInfo->RESOURCE_RATING != 0) ? $resourceInfo->RESOURCE_RATING." Stars":'Unspecified',
                                        'DESC'             => $resourceInfo->RESOURCE_DESCRIPTION,
                                        'KEYWORD'          => $keyword,
                                        'STEP'             => 3,
                                        'RESOURCE'         => $resource,
                                        'CMD'              => 'modify'
                                      )
                               );
         
         $menuTemplate->set_var('C_TIME', mktime());
         $menuTemplate->set_var('PAGE_NAME', 'Modify');
         
         $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock', $parentCategory));
         
         $menuTemplate->set_var('jscblock', $this->populateCategory($menuTemplate, 'jsblock'));
         
         if($childCategory == 0){
         	
            $menuTemplate->set_var( array( 'SUBID'        => 0,
                                           'SUB_NAME'     => 'Select a subcategory',
                                         )
                                  );
           
            $menuTemplate->parse('scblock','subcategoryBlock', true);
         }
         
         $subcategoryList = $categoryObj->getSubCategoryList($parentCategory);
         
         if (!empty($subcategoryList))  
           foreach($subcategoryList as $key=>$value)
           {  
              if($childCategory != $key)
              {         
                 $menuTemplate->set_var( array( 'SUBID'        => $key,
                                                'SUB_NAME'     => $value,
                                              )
                                       );
             
                 $menuTemplate->parse('scblock','subcategoryBlock', true);
              }
           }
       
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->displayWithTheme($output);
      }
      
      
      function modifyResource()
      {
         global $IRM_MNGR, $REL_APP_PATH, $IRM_RESOURCE_TRACK_MNGR,
      	        $IRM_STATUS_TEMPLATE, $IRM_MOTD_TEMPLATE;
      	
         global $APP_DB_URL, $INTRA_DB_URL;

         $category = $this->getRequestField('category');
         $subcategory = $this->getRequestField('subcategory');
         $newcat = $this->getRequestField('newcat');
         $title = $this->getRequestField('title');
         $url = $this->getRequestField('url');
         $rating = $this->getRequestField('rating');

         $resource_id = $this->getRequestField('resource_id');
         
         $auto_motd = $this->getRequestField('auto_motd');
         $auto_motd_target = $this->getRequestField('auto_motd_target');
         $keywords = $this->getRequestField('keywords');
         $description = $this->getRequestField('description');
         $ctime = $this->getRequestField('ctime');        

         $keywords = strtolower($keywords);
         
         $testArray = explode(" ", $keywords);
                  
         if( $category == 0 && empty($newcat))
         {
            $this->alert("CATEGORY_NAME_MISSING");
            return;
         }
         
         if(empty($title) || empty($url))
         {
            $this->alert("ESSENTIAL_FIELD_MISSING");
            return;
         }
                           
         $fp = @fopen($url,"r"); 
         
         if(!$fp)
         {
            $this->alert("INCORRECT_URL");
            return;
         }
         
         $categoryObj = new IrmCategory($this->dbi);
         
         $uid = $this->getUID();
         
         if(!empty($subcategory))
         {
            $resourceCategory = $subcategory; 
         
         } else if(!empty($category)) {
         
            if(empty($newcat)){
            
               $resourceCategory = $category;
            
            } else {
            
               $status = $categoryObj->existInList($newcat);
               
               if($status == 0)
               {
                  $status = $categoryObj->addCategory($newcat, $category, $uid);
                  
                  if($status) {
                     
                     $resourceCategory = $categoryObj->existInList($newcat);
                  }
               } else {
               
                  $resourceCategory = $status;
               }
            }
         } else {
         
            $status = $categoryObj->existInList($newcat);
            
            if($status == 0 )
            {
               $status = $categoryObj->addCategory($newcat, 0, $uid);
            
               if($status) {
                     
                  $resourceCategory = $categoryObj->existInList($newcat);
               }
            } else {
            
               $resourceCategory = $status;   
            }
         }
                 
         $now = mktime();
         
         $flag = $ctime.$uid;
         
                  
         $url = strtolower($url);
         
         $prefix = substr($url, 0, strpos($url,':'));
         
         if(strcmp($prefix,'http') && strcmp($prefix,'ftp') && strcmp($prefix, 'news') )
         {
            $url = 'http://'.$url;
         }
         
         $params = array( 'RESOURCE_TITLE'        => $title,
                          'RESOURCE_LOCATION'     => $url,
                          'RESOURCE_CATEGORY'     => $resourceCategory,
                          'RESOURCE_RATING'       => $rating,
                          'RESOURCE_DESCRIPTION'  => $description,
                          'RESOURCE_ADDED_BY'     => $uid,
                          'CREATE_TS'             => $now,    
                          'FLAG'                  => $flag
                        );
          
         $resourceObj = new IrmResource($this->dbi);
         
         $status = $resourceObj->modifyResource($params, $resource_id);
         
         if ($status)
         {  
            $keywords = strtolower($keywords);
         
            $keywordArray = explode(" ", $keywords);
            
            $keywordStatus = $resourceObj->deleteKeywords($resource_id);
            
            $keywordStatus = $resourceObj->addKeywords($keywordArray, $resource_id);
            
            $menuTemplate = new Template($this->getTemplateDir());
            $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
            $menuTemplate->set_block('fh', 'mainBlock', 'main');
            
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_MOD_SUCCESSFUL'));
            
            $message = $menuTemplate->parse('main', 'mainBlock', false);
             
         } else {
         
            $menuTemplate = new Template($this->getTemplateDir());
            $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
            $menuTemplate->set_block('fh', 'mainBlock', 'main');
            
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_MOD_FAILED'));
            
            $message = $menuTemplate->parse('main', 'mainBlock', false);
         }
         
         $this->displayWithTheme($message);
      }

      
      function delete()
      {
         
         $resource = $this->getRequestField('resource');

         global $IRM_STATUS_TEMPLATE;
         
         if($resource < 1)
      	 {
            $this->alert("RESOURCE_NAME_MISSING");
            return;
      	 }
      	 
      	 $resourceObj = new IrmResource($this->dbi);
      	 
      	 $keywordStatus = $resourceObj->deleteKeywords($resource);
         
         if($keywordStatus)
         {
            $status = $resourceObj->deleteResource($resource);
         }
         
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         
         if($status)
         {
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_DEL_SUCCESSFUL'));

         } else {
          
            $menuTemplate->set_var('STATUS_MESSAGE', $this->getMessage('RESOURCE_DEL_FAILED'));
         }
         $message = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->displayWithTheme($message);
      }
      
      
      function displayDescription($templateFile = null)
      {
         $rid = $this->getRequestField('rid');
      	 
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
      
         $resourceObj = new IrmResource($this->dbi);
         
         $resorceInfo = $resourceObj->getResourceInfo($rid);
         
         $menuTemplate->set_var('TITLE', $resorceInfo->RESOURCE_TITLE);
         
         $menuTemplate->set_var('DES', $resorceInfo->RESOURCE_DESCRIPTION);
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->displayWithTheme($output);         
      }
      
      
      function displayWithTheme( $output = null)
      {
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
        
         $themeTemplate = new Template($THEME_TEMPLATE_DIR);
         
         $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
         $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
         $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
         $themeTemplate->set_block('mmainBlock', 'printBlock', 'prnblock');
         $themeTemplate->set_var('printBlock', '&nbsp;');
         $themeTemplate->parse('prnblock', 'printBlock',false);

         $themeTemplate->set_block('mmainBlock', 'pageBlock', 'pblock');
         $themeTemplate->set_var('pblock', null);
         
         $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
         
         $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
         $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
         $themeTemplate->set_var('PHOTO', $photo);

         $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));	
         
         $themeTemplate->set_var('SERVER_NAME', $this->get_server());
         $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
    
         $themeTemplate->set_var('CONTENT_BLOCK', $output);

         $themeTemplate->parse('cnblock', 'contentBlock');
         $themeTemplate->parse('mmblock', 'mmainBlock');
         $themeTemplate->pparse('output', 'fh');
      }
      
      
      function authorize()
      {
         return true;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   $thisApp = new irmReourceMngr(
                             array( 'app_name'     => $APPLICATION_NAME,
                                    'app_version'  => '1.0.0',
                                    'app_type'     => 'WEB',
                                    'app_db_url'   => $IRM_DB_URL,
                                    'app_debugger' => $OFF,
                                    'app_auto_authorize' => TRUE,
                                    'app_auto_connect' => TRUE,
                                    'app_auto_chk_session' => TRUE
                                  )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
