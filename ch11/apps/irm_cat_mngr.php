<?php

   require_once "irm.conf";
   
   class irmCategoryMngr extends PHPApplication {

      function run()
      {
         $cmd = strtolower($this->getRequestField('cmd'));
         
         global $IRM_CAT_HOME_TEMPLATE, $IRM_MENU_TEMPLATE;
         
         global $SESSION_USER_ID;       
         
         global $INTRA_DB_URL;
          
         $this->uid = $SESSION_USER_ID;
                
         $authDBI = new DBI($INTRA_DB_URL);
         
         $themeObj = new Theme($authDBI, null, 'irm');

         $this->themeObj = $themeObj;

         $this->theme = $themeObj->getUserTheme($this->uid);

         $cmd = strtolower($cmd);
          
         if ( !strcmp($cmd, 'add')) {

            $this->addDriver();
            
         }else if( !strcmp($cmd, 'modify')) {
         	 
            $this->modifyDriver();
         
         }else if( !strcmp($cmd, 'delete')) {
         	
            $this->deleteCategory();
        
         } else {
         
            $this->showMenu($IRM_CAT_HOME_TEMPLATE);
         }
      }

     
     function addDriver()
     {  
         $step = ($this->getRequestField('step'));

         if($step == 1){
  
            global $IRM_CAT_ADD_MODTEMPLATE;

            $this->displayAddCategoryMenu($IRM_CAT_ADD_MODTEMPLATE);

         } else if($step == 2) {

            $this->addCategory();
         }
      }
      
      
      function modifyDriver()
      {
         $step = ($this->getRequestField('step'));
                  
         if($step == 1){   
         
            global $IRM_CAT_ADD_MODTEMPLATE;

            $this->displayModifyCategoryMenu($IRM_CAT_ADD_MODTEMPLATE);

         }else if($step == 2){

            $this->modifyCategory();
         }
      }

 
      function populateCategory($template = null, $blockName = null, $selectValue = 0)
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
            
               if(!strcmp($blockName, 'jsblock'))
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
      
      
      function populateSubCategory($template = null, $cat_id = null, $blockName = null)
      {
         $subcatagoryObj = new IrmCategory($this->dbi);
            
         $subcategoryList = array();
            
         $subcategoryList = $subcatagoryObj->getSubCategoryList($cat_id);
             
         $subcategoryIndex = 0;
               
         if(empty($subcategoryList))
         {
            return null;
            
         }else{
            $subCategory = NULL;            
            while (list($subcategoryid, $subcategoryname) = each ($subcategoryList))
            {
               if(!strcmp($blockName, 'jsblock'))
               {
                  $template->set_var('SUBCATEGORY_INDEX' , $subcategoryIndex++);
            
                  $template->set_var('SUBCATEGORY_ID', $subcategoryid);
            
                  $template->set_var('SUBCATEGORY_NAME', $subcategoryname);
            
                  $subCategory .= $template->parse('jsscblock', 'jsSubCategoryBlock', true);
               
               } else if(!strcmp($blockName, 'scblock')){
               
                  $template->set_var('SUBCATEGORY_VALUE', $subcategoryid);
                  
                  $template->set_var('SUBCATEGORY_NAME', $subcategoryname);
                   
                  $subCategory .= $template->parse('scblock', 'subcategoryBlock', true);
               }   
            }
         }
         return $subCategory;
      }
      

      function showMenu($templateFile =null)
      { 
      	 global $IRM_CAT_MNGR, $REL_APP_PATH;
      	           
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         $menuTemplate->set_block('mainBlock', 'subcategoryBlock', 'scblock');
         $menuTemplate->set_block('mainBlock', 'jsCategoryBlock', 'jscblock');
         $menuTemplate->set_block('jsCategoryBlock', 'jsSubCategoryBlock', 'jsscblock');
         
         $menuTemplate->set_var( array( 'CAT_SELECT_VALUE' => 0,
                                        'CAT_SELECT_NAME'  => 'Select a category',
                                        'SUBCATEGORY_NAME' => 'Select a Category first',
                                        'SUBCATEGORY_ID'   => 0,
                                        'IRM_CAT_MNGR'     => $IRM_CAT_MNGR,
                                        'APP_PATH'         => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                      )
                               );
         
         
         $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock'));
         
         $menuTemplate->set_var('jscblock', $this->populateCategory($menuTemplate, 'jsblock'));
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);         
         
      }


      function displayAddCategoryMenu($templateFile = null)
      {
      	 global $IRM_CAT_MNGR, $REL_APP_PATH;
      	          
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');

         $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock')); 

         $menuTemplate->set_var( array( 'VAL'              => null,
                                        'CAT_SELECT_VALUE' => 0,
                                        'CAT_SELECT_NAME'  => 'Select a category',
                                        'CMD'              => 'add',
                                        'IRM_CAT_MNGR'     => $IRM_CAT_MNGR,
                                        'APP_PATH'         => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                      )
                               );
                  
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }


      function addCategory()
      {
         $newcategory = $this->getRequestField('newcategory');
         $p_category = $this->getRequestField('p_category');

         if ( empty($newcategory) )
         {
            $this->alert('CATEGORY_NAME_MISSING');
            return;	
         }        
         
         $uid = $this->getUID();
         
         $categoryObj = new IrmCategory($this->dbi);
         
         $status = $categoryObj->existInList($newcategory);
         
         if($status == 0) {
         	
            if($p_category != 0) {
            	
               $status = $categoryObj->addCategory($newcategory, $p_category, $uid);
            	
            } else {
               $status = $categoryObj->addCategory($newcategory, 0, $uid);
            }
            
            if($status) {
            
               $message = $this->getMessage('CATEGORY_ADD_SUCCESSFUL');
            	
            } else {
            
                $message = $this->getMessage('CATEGORY_ADD_FAILED');
            }
         } else {
         
            $message = $this->getMessage('CATEGORY_EXISTS');
         }
         
         $this->showPage($message);
      }


      function displayModifyCategoryMenu($templateFile = null)
      {
         $category = $this->getRequestField('category');
         $subcategory = $this->getRequestField('subcategory');
         
         global $REL_APP_PATH, $IRM_CAT_MNGR;
         
         if( empty($category) )
         {
            $this->alert('CATEGORY_NAME_MISSING');
            return;	
         }
        
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         
         $catObj = new IrmCategory($this->dbi);
         
         if($subcategory == 0) {

            $catName = $catObj->getCategoryName($category);
                   
            $menuTemplate->set_var( array( 'VAL'              => $catName,
                                           'CAT_SELECT_VALUE' => 0,
                                           'CAT_SELECT_NAME'  => 'Select a category',
                                         )
                                  );
           $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock', 0)); 
         
         } else {
         
            $catName = $catObj->getCategoryName($subcategory);
            
            $mainCatName = $catObj->getCategoryName($category);
                   
            $menuTemplate->set_var( array( 'VAL'              => $catName,
                                           'CAT_SELECT_VALUE' => $category,
                                           'CAT_SELECT_NAME'  => $mainCatName,
                                         )
                                  );
                                  
            $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock',$category)); 
         }
         
         $menuTemplate->set_var( array( 'CMD'              => 'modify',
                                        'SELECAT'          => ($subcategory == 0)?$category:$subcategory,
                                        'IRM_CAT_MNGR'     => $IRM_CAT_MNGR,
                                        'APP_PATH'         => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                      )
                               );
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }


      function modifyCategory()
      {
         $newcategory = $this->getRequestField('newcategory');
         $p_category = $this->getRequestField('p_category');
         $selectedCat = $this->getRequestField('selectedCat');
         
         if ( empty($newcategory) )
         {
            $this->alert('CATEGORY_NAME_MISSING');
            return;	
         }        
         
         $uid = $this->getUID();
         
         $categoryObj = new IrmCategory($this->dbi);
         
         $lastName = $categoryObj->getCategoryName($selectedCat);
         
         if(!strcmp($lastName, $newcategory))
         {
            $status = 0;
            
         } else {
         	
            $status = $categoryObj->existInList($newcategory);
         }
         
         if($status == 0) {
            	
            if($p_category != 0) {
                
               $subcatlist = $categoryObj->getSubCategoryList($selectedCat);	
               
               if(empty($subcatlist))
               {
                  $status = $categoryObj->modifyCategory($selectedCat, $newcategory, $p_category, $uid);
               
               } else {
                
                  $this->alert('CATEGORY_HAS_SUBCATEGORY');
                  return;	
               }
            	
            } else {
         
               $status = $categoryObj->modifyCategory($selectedCat, $newcategory, 0, $uid);
            }
            
            if($status) {
            
               $message = $this->getMessage('CATEGORY_MOD_SUCCESSFUL');;
            	
            } else {
            
                $message = $this->getMessage('CATEGORY_MOD_FAILED');;
            }
         } else {
           
            $message = $this->getMessage('CATEGORY_EXISTS');
         }
         
         $this->showPage($message);
      }
    

      function deleteCategory()
      {
         $category = $this->getRequestField('category');
         $subcategory = $this->getRequestField('subcategory');

         global $IRM_STATUS_TEMPLATE;
         
         if( empty($category) )
         {
            $this->alert('CATEGORY_NAME_MISSING');
            return;	
         }
               
         if($subcategory == 0)         // this part delete main category
         {
            $subcatagoryObj = new IrmCategory($this->dbi);

            $subcategoryList = array();

            $subcategoryList = $subcatagoryObj->getSubCategoryList($category);
           
            if( !$subcategoryList)
            {
               $reourceObj = new IrmResource($this->dbi);
               
               $resourceList = $reourceObj->getNumOfResourceInCat($category);
               
               if($resourceList->NUM != 0)
               {
                  $this->alert('CATEGORY_HAS_RESOURCE');
                  return;	
               }
               
               $status = $subcatagoryObj->deleteCategory($category);
               
               if ($status)
               {
                  $message = $this->getMessage('CATEGORY_DELETE_SUCCESSFUL');
              
               } else {

                  $message = $this->getMessage('CATEGORY_DELETE_FAILED');
               }
            } else{
            
               $this->alert('CATEGORY_HAS_SUBCATEGORY');
               return;
            }
      
         } else                        // this part delete subcategory
         {
            $catagoryObj = new IrmCategory($this->dbi);
            
            $reourceObj = new IrmResource($this->dbi);
            
            $reourceList = array();
             
            $reourceList = $reourceObj->getNumOfResourceInCat($subcategory);
              
            if( $reourceList->NUM == 0)
            {
               $status = $catagoryObj->deleteCategory($subcategory);
               
               if ($status)
               {
                  $message = $this->getMessage('CATEGORY_DELETE_SUCCESSFUL');
               }
               else {

                  $message = $this->getMessage('CATEGORY_DELETE_FAILED');
               }
            } else{
               
               $this->alert('CATEGORY_HAS_RESOURCE');
               return;
            }  
         }  
         
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $IRM_STATUS_TEMPLATE);
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         
         if($status)
         {
            $menuTemplate->set_var('STATUS_MESSAGE',$message);

         } else {
          
            $menuTemplate->set_var('STATUS_MESSAGE', $message);
         }
         $message = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($message);
      }

      function authorize()
      {
         return true;
      }

   }//class

   

   $thisApp = new irmCategoryMngr(

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
