<?php

   require_once "irm.conf";
   
   class irSearchMngr extends PHPApplication {

      function run()
      {
         global $IRM_SEARCH_TEMPLATE, $IRM_SEARCH_RESULT_TEMPLATE;         
         global $INTRA_DB_URL;

         $cmd = strtolower($this->getRequestField('cmd'));
         
         $authDBI = new DBI($INTRA_DB_URL);
         
         if(!strcmp($cmd, 'search'))
         {
            $this->displaySearchResult($IRM_SEARCH_RESULT_TEMPLATE);
            
         } else if( (!strcmp($cmd, 'previous')) || (!strcmp($cmd, 'next')) ){
         
            $this->displaySearResultNextandPrevious($IRM_SEARCH_RESULT_TEMPLATE);
         
         } else if(!strcmp($cmd, 'mostvisited')){
         
            $this->showMostVisitedResource($IRM_SEARCH_RESULT_TEMPLATE);
               
         } else if (!strcmp($cmd, 'topranking')){
         
            $this->showTopRankingResource($IRM_SEARCH_RESULT_TEMPLATE);
            
         } else if (!strcmp($cmd, 'title') || !strcmp($cmd, 'rating') || !strcmp($cmd, 'addedby')) {
         
            $this->sortAndDisplay($IRM_SEARCH_RESULT_TEMPLATE);
            
         } else {
         
            $this->showMenu($IRM_SEARCH_TEMPLATE);
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
            $subCategory = NULL;
            while (list($subcategoryid, $subcategoryname) = each ($subcategoryList))
            {
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
     
       
      function showMenu($templateFile = null)
      {
      	 global $APP_DB_URL, $REL_APP_PATH, $IRM_SEARCH_MNGR, $IRM_RESOURCE_MNGR, $IRM_CAT_MNGR;
      	           
         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'rowBlock', 'rblock');
         $menuTemplate->set_block('rowBlock', 'columnBlock', 'colblock');
         $menuTemplate->set_block('columnBlock', 'subcatBlock', 'subcblock');
         $menuTemplate->set_block('mainBlock', 'categoryBlock', 'cblock');
         $menuTemplate->set_block('mainBlock', 'subcategoryBlock', 'scblock');
         $menuTemplate->set_block('mainBlock', 'jsCategoryBlock', 'jscblock');
         $menuTemplate->set_block('jsCategoryBlock', 'jsSubCategoryBlock', 'jsscblock');
         $menuTemplate->set_block('mainBlock','employeeBlock','eblock');
         $menuTemplate->set_block('mainBlock','visitorBlock','vblock');
         $menuTemplate->set_block('mainBlock','adminBlock', 'ablock');
         
         $resourceObj = new IrmResource($this->dbi);
         
         $catObj = new IrmCategory($this->dbi);
         
         $mainCatList = array();
         
         $mainCatList = $catObj->getCategoryList();
         
         if(!empty($mainCatList))
         {  
            $i=1;
            foreach($mainCatList as $catID=>$catName)
            {  
               $numOfResou = $resourceObj->getNumOfResourceInCat($catID);

               $catResourceTotal = 0; 
            	
               $menuTemplate->set_var('subcblock', null);
               $menuTemplate->set_var('MAIN_CAT', $catName);
               $menuTemplate->set_var('LINK_MAIN_CAT', $catID);
               
               $subCatList = array();
               
               $subCatList = $catObj->getSubCategoryList($catID);
               
               if( !empty($subCatList) )
               {
                  foreach($subCatList as $scatID => $scatName)
                  {
                     $numOfResourceInSubCat = $resourceObj->getNumOfResourceInCat($scatID);
                     
                     $menuTemplate->set_var('NUM_OF_RESOURCE',$numOfResourceInSubCat->NUM);

                     $catResourceTotal += $numOfResourceInSubCat->NUM; 
                     
                     $menuTemplate->set_var('SUB_CAT', $scatName);
                     
                     $menuTemplate->set_var('LINK_SUB_CAT', $scatID);
                    
                     $val = $resourceObj->getNewResource($scatID, (mktime()-NEWRESOURC));
                                            
                     if( $val->NUM > 0) {
                     
                        $menuTemplate->set_var('NEW', 'New');
                     
                     } else {
                     
                        $menuTemplate->set_var('NEW', '');
                     }
                     
                     $menuTemplate->parse('subcblock', 'subcatBlock', true);
                  }
               } else {
                  $menuTemplate->set_var('subcblock', null);
               }
               
               $menuTemplate->set_var('NUM_OF_MAINRESOURCE', $catResourceTotal);
               $menuTemplate->parse('colblock', 'columnBlock', true);
               
               if($i%3 == 0)
               {
                  $menuTemplate->parse('rblock', 'rowBlock', true);
               }
               
               $i++;
            }
            
            $menuTemplate->parse('rblock', 'rowBlock', false);
            
         } else {
         	
            $menuTemplate->set_var('rblock', null);
         }
         
         $totalResource = $resourceObj->getTotalResourceNum();
         
         $menuTemplate->set_var(array( 'CAT_SELECT_VALUE'   => 0,
                                       'CAT_SELECT_NAME'    => 'Category',
                                       'SUBCATEGORY_ID'     => 0,
                                       'SUBCATEGORY_NAME'   => 'Subcategory',
                                       'TOTAL_RESOURCE'     => $totalResource->NUM,
                                       'APP_PATH'           => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                       'IRM_SEARCH_MNGR'    => $IRM_SEARCH_MNGR,
                                       'IRM_RESOURCE_MNGR'  => $IRM_RESOURCE_MNGR,
                                       'IRM_CATEGORY_MNGR'  => $IRM_CAT_MNGR 
                                     )
                               );
         
         $menuTemplate->set_var('cblock', $this->populateCategory($menuTemplate, 'cblock'));
         
         $menuTemplate->set_var('jscblock', $this->populateCategory($menuTemplate, 'jsblock'));
         
         $userList = array();
                  
         $authDBI = new DBI($APP_DB_URL);
         $userObj = new User($authDBI, $this->getUID()); 
         $userList = $userObj->getUserList();
         
         $type = $userObj->getTYPE();
         
         foreach($userList as $key=>$value)
         {
            list($name, $domain) = explode('@', $value);
            
            $menuTemplate->set_var('EMP_ID',$key);
            $menuTemplate->set_var('EMP_NAME', ucwords($name));
            $menuTemplate->parse('eblock', 'employeeBlock', true);
            $menuTemplate->parse('vblock', 'visitorBlock', true);
         }
         
         $menuTemplate->set_var( array( 
                                        'IRM_SEARCH_MNGR'     => $IRM_SEARCH_MNGR,
                                        'APP_PATH'            => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                      )
                                );
         
         if($type == IRM_ADMIN_TYPE )
         {
            $menuTemplate->parse('ablock', 'adminBlock', false);
            
         } else {
         	
            $menuTemplate->set_var('ablock', null);
         }
                                    
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }


      function displaySearchResult($templateFile = null)
      {
         $keywords = ($this->getRequestField('keywords'));
         $category = ($this->getRequestField('category')) ;
         $subcategory = ($this->getRequestField('subcategory'));
         $resourcetype = ($this->getRequestField('resourcetype'));
         $rating = ($this->getRequestField('rating'));
         $pagesize = ($this->getRequestField('pagesize'));
         $visited_by = ($this->getRequestField('visited_by'));
         $create_by = ($this->getRequestField('create_by'));
         
         global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE, $REL_APP_PATH, $IRM_RESOURCE_MNGR,
                $IRM_SEARCH_MNGR, $IRM_RESOURCE_TRACK_MNGR, $APP_DB_URL;

         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock','previousBlock','pblock');
         $menuTemplate->set_block('mainBlock','nextBlock', 'nblock');
         
         session_register('SESSION_SEARCH_LIST');
         session_register('SESSION_PAGE_SIZE');
         
         $_SESSION['SESSION_SEARCH_LIST'] = null;
         $_SESSION['SESSION_PAGE_SIZE'] = null;
        
         $params = array(  'KEYS'                => $keywords,
                           'RESOURCE_CATEGORY'   => ($subcategory != 0)? $subcategory:$category,
                           'RESOURCE_RATING'     => $rating,
                           'RESOURCE_ADDED_BY'   => $create_by,
                           'VISITOR_ID'          => $visited_by,
                        );

         $resourceObj = new IrmResource($this->dbi);         
         
         $resourceList = $resourceObj->searchResource($params);

         if ($resourceList == NULL)
         {
             $this->alert('NO_RESOURCE_FOUND');
             return;
         } 
         
         if(!strcmp($resourcetype, 'FTP Site'))
         {
            $hash = array();
            $j=0;
            
            for($i=0; $i<count($resourceList); $i++)
            {
               $resourceUrl = $resourceObj->getResourceUrl($resourceList[$i]->RESOURCE_ID);
               
               $temp = strpos($resourceUrl, ":");
               
               if(($temp == 3) && !strcmp(substr($resourceUrl, 0, 3), 'ftp') )
               {
                  $hash[$j] =  $resourceList[$i];
                  
                  $j++;     
               }
            }
            $resourceList = $hash;
         }
         
         $authDBI = new DBI($APP_DB_URL);
         $userObj = new User($authDBI);
         
         for($i=0; $i<count($resourceList); $i++)
         {
            $addedBy = $resourceList[$i]->RESOURCE_ADDED_BY;
            $userObj->getUserInfo($addedBy);
            list($name, $domain) = explode('@', $userObj->EMAIL); 
            
            $resourceList[$i]->RESOURCE_ADDED_BY = $name;
         }
         
         $menuTemplate->set_var( array( 'PAGE_SIZE'                 => $pagesize,
                                        'CURRENT'                   => 0,
                                        'APP_PATH'                  => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                        'IRM_RESOURCE_SEARCH_MNGR'  => $IRM_SEARCH_MNGR,
                                        'IRM_RESOURCE_TRACK_MNGR'   => $IRM_RESOURCE_TRACK_MNGR,
                                        'IRM_RESOURCE_MNGR'         => $IRM_RESOURCE_MNGR,
                                        'TOPTITLE'                  => 'Added By',
                                        'SORTED'                    => 'title',
                                        'SORTEDTYPE'                => 1 
                                      )
                               );
         
         if(!empty($resourceList))
         {        
            usort($resourceList,'sortByResourceTitle');
         }
          
         $_SESSION['SESSION_SEARCH_LIST'] = $resourceList;
          
         if(empty($pagesize))
         {
            $pagesize = DEFAULT_PAGE_SIZE;
         }
         
         $_SESSION['SESSION_PAGE_SIZE'] = $pagesize;
                              
         if(count($resourceList)>$pagesize)
         {
            $menuTemplate->parse('nblock','nextBlock',false);
         
         } else {
         	
            $menuTemplate->set_var('nblock', null);
         }
         
         if(!empty($resourceList))
         { 
           $menuTemplate->set_var('rblock', $this->populateResource($menuTemplate, 'rblock', 0));
           
         } else {
         
            $menuTemplate->set_var('rblock', null);
         }
         
         $menuTemplate->set_var('pblock', null);
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }
      
      
      function populateResource($template = null,  $blockName = null, $startingPoint = null)
      { 
      	 if(!isset($startingPoint)) $startingPoint = 0;
      	 global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE;
      	 global $APP_DB_URL; 
            
         $resource = NULL; 
         for($i=0; $i < $_SESSION['SESSION_PAGE_SIZE']; $i++)
         {  
            $j = $startingPoint+$i; 
            
            if(count($_SESSION['SESSION_SEARCH_LIST']) >  $j)
            {
               if(!empty($_SESSION['SESSION_SEARCH_LIST'][$j]->RESOURCE_TITLE))
               { 
                  $template->set_var('RTITLE',  ucwords($_SESSION['SESSION_SEARCH_LIST'][$j]->RESOURCE_TITLE));
                  $template->set_var('RATING', ($_SESSION['SESSION_SEARCH_LIST'][$j]->RESOURCE_RATING==0)?'Undefined':$SESSION_SEARCH_LIST[$j]->RESOURCE_RATING);
                  $template->set_var('R_ID',    $_SESSION['SESSION_SEARCH_LIST'][$j]->RESOURCE_ID);
                  $template->set_var('ADDBY',   $_SESSION['SESSION_SEARCH_LIST'][$j]->RESOURCE_ADDED_BY);  
               
               } else {
               
                  $template->set_var('RTITLE',  $_SESSION['SESSION_SEARCH_LIST'][$j][RESOURCE_TITLE]);
                  $template->set_var('RATING', $_SESSION['SESSION_SEARCH_LIST'][$j][RESOURCE_RATING]);
                  $template->set_var('R_ID',    $_SESSION['SESSION_SEARCH_LIST'][$j][RESOURCE_ID]);
                  $template->set_var('ADDBY',   $_SESSION['SESSION_SEARCH_LIST'][$j][RESOURCE_ADDED_BY]);  
               }
               if($i%2 == 1 ){
               	
               	  $template->set_var('TRBGCOLOR','cfcfcf');
               	  
               } else {
               	
               	  $template->set_var('TRBGCOLOR', 'eeeeee');
               }
               
               $resource .= $template->parse('rblock', 'resourceBlock', true);
            }
         }
         return $resource;
      }
      
      
      function sortAndDisplay($templateFile = null)
      {
         global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE, $REL_APP_PATH, 
                $IRM_SEARCH_MNGR, $IRM_RESOURCE_TRACK_MNGR, $APP_DB_URL, $IRM_RESOURCE_MNGR;
      	 
         $current = ($this->getRequestField('current'));
         $sorted = ($this->getRequestField('sorted'));
         $sorttype = ($this->getRequestField('sorttype'));
         $cmd = strtolower($this->getRequestField('cmd'));



         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock','previousBlock','pblock');
         $menuTemplate->set_block('mainBlock','nextBlock', 'nblock');
         
         $data = $_SESSION['SESSION_SEARCH_LIST'];
         if ($data == NULL)
         {
             $this->alert('NO_RESOURCE_FOUND');
             return;
         }
   
         if(!strcmp($cmd, 'title'))
         {
            if($sorttype == 1 && strcmp($sorted, 'title') )
            {
               usort($data, 'sortByResourceTitle'); 
               $data = array_reverse($data);
            
            } else {
         	
               usort($data, 'sortByResourceTitle'); 
            }
         
         } else if(!strcmp($cmd, 'rating')) {
         	
            if($sorttype == 1 && strcmp($sorted, 'rating') )
            {
               usort($data, 'sortByResourceRating'); 
                           
            } else {
               usort($data, 'sortByResourceRating'); 
               $data = array_reverse($data);
            }  
 
         } else if (!strcmp($cmd, 'addedby') ) {
         
            if(!empty($data[0]->RESOURCE_ID) ){       
            
               if($sorttype == 1 && strcmp($sorted, 'addedby') )
               {
                  usort($data, 'sortByResourceAddedBy'); 
                  $data = array_reverse($data);
               
               } else {
                   
                  usort($data, 'sortByResourceAddedBy'); 
               }
            
            } else {
               
               if($sorttype == 1 && strcmp($sorted, 'addedby') )
               {
                  usort($data, 'sortByResourceVisitor'); 
                  $data = array_reverse($data);
               
               } else {
                   
                  usort($data, 'sortByResourceVisitor'); 
               }
            }
         }
                           
         session_register('SESSION_SEARCH_LIST');
          
         $_SESSION['SESSION_SEARCH_LIST'] = $data; 
         
         if(!empty($_SESSION['SESSION_SEARCH_LIST']))
         {  
            $menuTemplate->set_var('rblock', $this->populateResource($menuTemplate, 'rblock', 0));
           
         } else {
         
            $menuTemplate->set_var('rblock', null);
         }
         
         $menuTemplate->set_var('pblock', null);
         
         if( count($_SESSION['SESSION_SEARCH_LIST'])<= $_SESSION['SESSION_PAGE_SIZE'])
         {
            $menuTemplate->set_var('nblock', null);
             
         } else {
            
            $menuTemplate->parse('nblock','nextBlock',false);
         }
         $menuTemplate->set_var( array( 'CURRENT'                   => 0,
                                        'APP_PATH'                  => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                        'IRM_RESOURCE_SEARCH_MNGR'  => $IRM_SEARCH_MNGR,
                                        'IRM_RESOURCE_TRACK_MNGR'   => $IRM_RESOURCE_TRACK_MNGR,
                                        'TOPTITLE'                  => 'Added By',
                                        'SORTED'                    => 'title',
                                        'SORTEDTYPE'                => $sorttype*(-1),
                                        'IRM_RESOURCE_MNGR'         => $IRM_RESOURCE_MNGR
                                      )
                               );
         if(empty($data[0]->RESOURCE_ID) )
         {
            $menuTemplate->set_var('TOPTITLE', 'Visitor No');
         }
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }
 

      function displaySearResultNextandPrevious($templateFile = null)
      {
      	 global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE, $REL_APP_PATH, $IRM_SEARCH_MNGR, $IRM_RESOURCE_TRACK_MNGR, 
      	        $APP_DB_URL, $IRM_RESOURCE_MNGR;
      	 
         $cmd = strtolower($this->getRequestField('cmd'));
         $current = ($this->getRequestField('current'));
         $sorted = ($this->getRequestField('sorted'));
         $sorttype = ($this->getRequestField('sorttype'));

         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock','previousBlock','pblock');
         $menuTemplate->set_block('mainBlock','nextBlock', 'nblock');
         
         if(!strcmp($cmd, 'next')) {
         
            $current = $current + $_SESSION['SESSION_PAGE_SIZE'];
            
            $menuTemplate->parse('pblock', 'previousBlock', false);
            
            if( count($_SESSION['SESSION_SEARCH_LIST']) < ($current + $_SESSION['SESSION_PAGE_SIZE']) ) { 
               
               $menuTemplate->set_var('nblock', null);
            
            } else {
            
               $menuTemplate->parse('nblock', 'nextBlock', false);
            }
         
         } else {
         
            $current = $current - $_SESSION['SESSION_PAGE_SIZE'];
            
            if($current == 0) {
            	 
               $menuTemplate->set_var('pblock', null);
           
            } else {
            	
               $menuTemplate->parse('pblock', 'previousBlock', false);
            }
            
            $menuTemplate->parse('nblock', 'nextBlock', false);
         }
         $menuTemplate->set_var('rblock', $this->populateResource($menuTemplate, 'rblock', $current));
         
         $menuTemplate->set_var( array( 'CURRENT'                   => $current,
                                        'APP_PATH'                  => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                        'IRM_RESOURCE_SEARCH_MNGR'  => $IRM_SEARCH_MNGR,
                                        'IRM_RESOURCE_TRACK_MNGR'   => $IRM_RESOURCE_TRACK_MNGR,
                                        'TOPTITLE'                  => 'Added By',
                                        'SORTED'                    => $sorted,
                                        'SORTEDTYPE'                => $sorttype,
                                        'IRM_RESOURCE_MNGR'         => $IRM_RESOURCE_MNGR
                                      )
                               );
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }
   
      
      function showTopRankingResource($templateFile = null)
      {      	 
      	 global $REL_APP_PATH, $IRM_RESOURCE_TRACK_MNGR, $APP_DB_URL, $IRM_SEARCH_MNGR, $IRM_RESOURCE_MNGR;
      	 
      	 global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE;

         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock','previousBlock','pblock');
         $menuTemplate->set_block('mainBlock','nextBlock', 'nblock');
         
         $menuTemplate->set_var('pblock', null);
         $menuTemplate->set_var('nblock', null);
               	 
         $topRankingList = array();
         
         session_register('SESSION_SEARCH_LIST');
         session_register('SESSION_PAGE_SIZE');
         
         $_SESSION['SESSION_SEARCH_LIST'] = null;
         $_SESSION['SESSION_PAGE_SIZE'] = null;
         
         $resourceObj = new IrmResource($this->dbi);
         
         $topRankingList = $resourceObj->getTopRankingList(TOPRANKING);

         if (empty($topRankingList)) {
            $this->alert('NO_RESOURCE_FOUND');
            return;
         }

         $authDBI = new DBI($APP_DB_URL);
         $userObj = new User($authDBI);
         
         for($i=0; $i<count($topRankingList); $i++)
         {
            $addedBy = $topRankingList[$i]->RESOURCE_ADDED_BY;
            $userObj->getUserInfo($addedBy);
            list($name, $domain) = explode('@', $userObj->EMAIL); 
            
            $topRankingList[$i]->RESOURCE_ADDED_BY = $name;
         }
         
         usort($topRankingList,'sortByResourceTitle');
          
         $_SESSION['SESSION_SEARCH_LIST'] = $topRankingList;
                  
         $_SESSION['SESSION_PAGE_SIZE'] = DEFAULT_PAGE_SIZE;;
                              
         if(count($topRankingList)>$_SESSION['SESSION_PAGE_SIZE'])
         {
            $menuTemplate->parse('nblock','nextBlock',false);
         
         } else {
         	
            $menuTemplate->set_var('nblock', null);
         }
         
         if(!empty($topRankingList))
         { 
           $menuTemplate->set_var('rblock', $this->populateResource($menuTemplate, 'rblock', 0));
           
         } else {
         
            $menuTemplate->set_var('rblock', null);
         }
         
         $menuTemplate->set_var('pblock', null);
         
         $menuTemplate->set_var( array( 'APP_PATH'                  => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                        'IRM_RESOURCE_TRACK_MNGR'   => $IRM_RESOURCE_TRACK_MNGR,
                                        'TOPTITLE'                  => 'Added By',
                                        'IRM_RESOURCE_SEARCH_MNGR'  => $IRM_SEARCH_MNGR,
                                        'SORTEDTYPE'                => 1,
                                        'IRM_RESOURCE_MNGR'         => $IRM_RESOURCE_MNGR
                                      )
                               );
         
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }

   
      function showMostVisitedResource($templateFile = null)
      {
         global $REL_APP_PATH, $IRM_RESOURCE_TRACK_MNGR, $APP_DB_URL, $IRM_SEARCH_MNGR, $IRM_RESOURCE_MNGR;
         
         global $SESSION_SEARCH_LIST, $SESSION_PAGE_SIZE;

         $menuTemplate = new Template($this->getTemplateDir());
         $menuTemplate->set_file('fh', $templateFile);
         
         $menuTemplate->set_block('fh', 'mainBlock', 'main');
         $menuTemplate->set_block('mainBlock', 'resourceBlock', 'rblock');
         $menuTemplate->set_block('mainBlock','previousBlock','pblock');
         $menuTemplate->set_block('mainBlock','nextBlock', 'nblock');
         
         session_register('SESSION_SEARCH_LIST');
         session_register('SESSION_PAGE_SIZE');
         
         $_SESSION['SESSION_SEARCH_LIST'] = null;
         $_SESSION['SESSION_PAGE_SIZE'] = null;
         
      	 $resourceObj = new IrmResource($this->dbi);
      	 
         $topRankingList = array();
         
         $mostVisitedList = $resourceObj->getMostVisitedResource(MOSTVISITED);
         
         $requiredList = array();
                 
         for($i=0;  $i < count($mostVisitedList); $i++)
         {
            $resourceInfo = $resourceObj->getResourceInfo($mostVisitedList[$i]->RESOURCE_ID);
            $temp = array(); 
            $temp['RESOURCE_TITLE']     = $resourceInfo->RESOURCE_TITLE;
            $temp['RESOURCE_RATING']    = $resourceInfo->RESOURCE_RATING;
            $temp['RESOURCE_ID']        = $mostVisitedList[$i]->RESOURCE_ID;
            $temp['RESOURCE_ADDED_BY']  = $mostVisitedList[$i]->ID;     
            
            $requiredList[$i] = $temp;
         }
               
         $_SESSION['SESSION_SEARCH_LIST'] = $requiredList;
            
         $_SESSION['SESSION_PAGE_SIZE'] = DEFAULT_PAGE_SIZE;;
                              
         if(count($requiredList)>$_SESSION['SESSION_PAGE_SIZE'])
         {
            $menuTemplate->parse('nblock','nextBlock',false);
         
         } else {
         	
            $menuTemplate->set_var('nblock', null);
         }
         
         if(!empty($requiredList))
         { 
           global $APP_DB_URL; 
      	 
            $authDBI = new DBI($APP_DB_URL);
            $userObj = new User($authDBI);
                                                            
            $startingPoint = 0;
            for($i=0; $i < $_SESSION['SESSION_PAGE_SIZE']; $i++)
            {  
               $j = $startingPoint+$i; 
               
               if(count($_SESSION['SESSION_SEARCH_LIST']) >  $j)
               {  
                  $menuTemplate->set_var('RTITLE', ucwords($_SESSION['SESSION_SEARCH_LIST'][$j]['RESOURCE_TITLE']));
                  $menuTemplate->set_var('RATING', $_SESSION['SESSION_SEARCH_LIST'][$j]['RESOURCE_RATING']);
                  $menuTemplate->set_var('R_ID', $_SESSION['SESSION_SEARCH_LIST'][$j]['RESOURCE_ID']);
                  
                  $menuTemplate->set_var('ADDBY', $_SESSION['SESSION_SEARCH_LIST'][$j]['RESOURCE_ADDED_BY']);  
                  
                  if($i%2 == 1 ){
               	
               	     $menuTemplate->set_var('TRBGCOLOR','cfcfcf');
               	  
                  } else {
               	
               	     $menuTemplate->set_var('TRBGCOLOR', 'eeeeee');
                  }
                  $menuTemplate->parse('rblock', 'resourceBlock', true);
               }
            }
         } else {
         
            $menuTemplate->set_var('rblock', null);
         }
         
         $menuTemplate->set_var('pblock', null);
         
         $menuTemplate->set_var( array( 'APP_PATH'                  => sprintf("%s/%s", $this->server, $REL_APP_PATH),
                                        'IRM_RESOURCE_TRACK_MNGR'   => $IRM_RESOURCE_TRACK_MNGR,
                                        'TOPTITLE'                  => 'Visitor No',
                                        'IRM_RESOURCE_SEARCH_MNGR'  => $IRM_SEARCH_MNGR,
                                        'SORTEDTYPE'                => 1,
                                        'IRM_RESOURCE_MNGR'         => $IRM_RESOURCE_MNGR
                                      )
                               );
         $output = $menuTemplate->parse('main', 'mainBlock', false);
         
         $this->showPage($output);
      }
                 
      
            
      function authorize()
      {
         return true;
      }

   }//class
   
   
    function sortByResourceTitle($a,$b)
    {  
       if(!empty($a->RESOURCE_TITLE))
       {
          return strcmp(strtolower($a->RESOURCE_TITLE), strtolower($b->RESOURCE_TITLE));
       
       } else {
         
          return strcmp(strtolower($a[RESOURCE_TITLE]), strtolower($b[RESOURCE_TITLE]));
       }
    }  
    
    
    function sortByResourceAddedBy($a, $b)        
    {
       return strcmp(strtolower($a->RESOURCE_ADDED_BY), strtolower($b->RESOURCE_ADDED_BY));
    }


    function sortByResourceVisitor($a, $b){

       if ($a[RESOURCE_ADDED_BY] > $b[RESOURCE_ADDED_BY]) {
          
          return -1;
       
       } else if ($a[RESOURCE_ADDED_BY] < $b[RESOURCE_ADDED_BY]) {
         
          return 1 ;
       
       }else{
       
          return 0;
       }
    }    

    
    function sortByResourceRating($a, $b)
    {
       if(!empty($a->RESOURCE_RATING))
       {
          if ($a->RESOURCE_RATING > $b->RESOURCE_RATING) {
          
             return -1;
       
          } else if ($a->RESOURCE_RATING < $b->RESOURCE_RATING) {
         
             return 1 ;
       
          }else{
       
             return 0;
          }
       } else {
          if ($a[RESOURCE_RATING] > $b[RESOURCE_RATING]) {
          
             return -1;
       
          } else if ($a[RESOURCE_RATING] < $b[RESOURCE_RATING]) {
         
             return 1 ;
       
          }else{
       
             return 0;
          }
       } 
    }     
          
   $SESSION_PAGE_SIZE = null;   
   $SESSION_SEARCH_LIST = null;
          
   $thisApp = new irSearchMngr(
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
