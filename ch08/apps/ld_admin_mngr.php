<?php

   require_once "ld.conf";
   require_once $CATEGORY_CLASS;
   require_once $DOC_CLASS;
   require_once $RESPONSE_CLASS;
   require_once $THEME_CLASS;
   require_once $MESSAGE_CLASS;
   
   global $SESSION_USER_ID;
   class prAdminMngr extends PHPApplication {

     function run()
     {
          
          $cmd = $this->getRequestField('cmd');
          
          $this->uid = $this->getUID();
          
          $themeObj = new Theme($this->dbi,null,'ld_tool');

          $this->themeObj = $themeObj;

          $this->theme = $themeObj->getUserTheme($this->uid);
          
          $cmd = strtolower(substr($cmd, 0, 3));
          
          if (!strcmp($cmd, 'del'))
          {
             $this->deleteDriver();	
          }          
          else if (!strcmp($cmd, 'add'))
          {
             $this->addDriver();
          }
          else if (!strcmp($cmd, 'mod'))
          {
             $this->modifyDriver();
          }
          else if (!strcmp($cmd, 'reo'))
          {
             $this->reorderDriver();	
          }
          
     }
     
     function reorderDriver()
     {
        $step = $this->getRequestField('step');
        if (!$this->isAdmin)
        {
           $this->alert('UNAUTHORIZED_ACCESS');
           exit;	
        }
        if ($step == 1)
        {
           $this->displayReorderMenu();	
        }
        else if ($step == 2)
        {
           $this->updateOrders();	
        }
        
     }
     
     function deleteDriver()
     {
        $obj = $this->getRequestField('obj');
        if (!strcmp($obj, 'doc'))
        {
           $this->deleteDoc();	
        }
        else if (!strcmp($obj, 'response'))
        {
           $this->deleteResponse();	
        }
        else if (!strcmp($obj, 'category'))
        {
           $this->deleteCategory();	
        }
     }
     
     function addDriver()
     {
        $obj = $this->getRequestField('obj');
        if (!strcmp($obj, 'doc'))
        {
           $this->addDoc();	
        }
        if (!strcmp($obj, 'category'))
        {
           $this->addCategory();	
        }	
     }
     
     function modifyDriver()
     {
        $obj = $this->getRequestField('obj');
        if (!strcmp($obj, 'doc'))
        {
           $this->modifyDoc();	
        }
        if (!strcmp($obj, 'category'))
        {
           $this->modifyCategory();	
        }	
     }
     
     function addDoc()
     {
        $step = $this->getRequestField('step');
        if (empty($step))
        {
           $this->displayAddModDocMenu('Add');	
        }
        else if ($step == 2)
        {
           $this->storeDoc();	
        }
     }
     
     function modifyDoc()
     {
        
        $step = $this->getRequestField('step');
        $nid = $this->getRequestField('nid');
        if (empty($step))
        {
           if (!empty($nid))
           {
              $this->displayAddModDocMenu('Modify');	
           }
           else
           {
              $this->alert('SELECT_DOC');	
           }   
        }
        else if ($step == 2)
        {
           $this->updateDoc();	
        }
     }
     
     function addCategory()
     {
        $step = $this->getRequestField('step');
        if (empty($step))
        {
           $this->displayAddModCategoryMenu('Add');	
        }
        else if ($step == 2)
        {
           $this->storeCategory();	
        }
     }
     
     function modifyCategory()
     {
        $step = $this->getRequestField('step');
        $cid = $this->getRequestField('cid');
        if (empty($step))
        {
           if (!empty($cid))
           {
              $this->displayAddModCategoryMenu('Modify');	
           }
           else
           {
              $this->alert('SELECT_CAT');	
           }   
        }
        else if ($step == 2)
        {
           $this->updateCategory();	
        }
     }
     
     function storeDoc()
     {
        $cid = $this->getRequestField('cid');
        $pub_date = $this->getRequestField('pub_date');
        $heading = $this->getRequestField('heading');
        $body = $this->getRequestField('body');
        $cat = $this->getRequestField('cat');

        
        if (empty($cid) && !empty($cat))
        {
            $catObj = new Category($this->dbi);
            $cid = $catObj->getCategoryIDByName($cat);	
        }
        
        
        
        $docObj = new Doc($this->dbi);
        
        if (empty($cid) || empty($heading) || empty($body))
        {
           $this->alert('INPUT_MISSING');	
           exit;
        }
        list($pubMonth, $pubDay, $pubYear) = explode('/', $pub_date);
        
        if (!checkDate($pubMonth, $pubDay, $pubYear)/* || mktime(0,0,0, $pubMonth, $pubDay, $pubYear) < mktime()*/)
        {
           $this->alert('PUB_DATE_INCORRECT');
           exit;
        }
        $curHr = date("H", mktime());
        $curMin = date("i", mktime());
        $curSec = date("s", mktime());
         
        
        $params = array(
                        'DOC_ID'      =>    'null',
                        'CAT_ID'       =>    $cid,
                        'HEADING'      =>    $heading,
                        'BODY'         =>    $body,
                        'PUBLISH_DATE' =>    mktime($curHr, $curMin, $curSec, $pubMonth, $pubDay, $pubYear)
                       );
         
         $status = $docObj->addDoc($params);
         if ($status)
          {             
             $this->showStatusMessage($this->getMessage('DOC_ADDED'));
             $msgObj = new Message($this->dbi);
             global $ANNOUNCE_LD_ADDED_TEMPLATE, $LD_DETAILS_MNGR, $REL_APP_PATH;
             $template = new Template($this->getTemplateDir());
             $template->set_file('fh', $ANNOUNCE_LD_ADDED_TEMPLATE);
             $template->set_block('fh', 'mainBlock', 'main');
             $catObj = new Category($this->dbi, $cid);
             $template->set_var('CATEGORY', $catObj->getCategoryName());
             $template->set_var('DOCUMENT_TITLE', stripslashes($heading));
             $template->set_var('LD_DETAILS_MNGR', $LD_DETAILS_MNGR);
             $template->set_var('REL_APP_PATH', $REL_APP_PATH);
             $template->set_var('DOC_ID', $status);
             
             $msg = $template->parse('main', 'mainBlock', false);
             $msg_id  = $msgObj->addMessage(LD_ADD_TITLE." (".stripslashes($heading).")", mktime(), $msg, mktime(), $this->getUID(), $this->isAdmin ? 1 : 0);
             $msgObj->addViewer($msg_id, $catObj->getViewers($cid));             
          }
          else
          {
             
             $this->showStatusMessage($this->getMessage('DOC_NOT_ADDED'));		
          }
     }
     
     function displayReorderMenu()
     {
        global $LD_REORDER_CAT_TEMPLATE;
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', $LD_REORDER_CAT_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        $template->set_block('catBlock', 'ordernumBlock', 'order');
        
        $catObj = new Category($this->dbi);
        
        $cats = $catObj->getCategories();
        $topOrder = $catObj->getHighestOrder();
        while(list($cid, $cname) = each($cats))
        {
           
           $template->set_var('CAT_ID', $cid);	
           $template->set_var('CAT_NAME', $cname);
           $template->set_var('order', null);
           
           for($i = 1; $i<=$topOrder; $i++)
           {
             if ($i == $catObj->getCategoryOrder($cid))
             {
                $template->set_var('CHOSEN', 'selected');	
             }
             else
             {
                $template->set_var('CHOSEN', null);		
             }
             $template->set_var('ORDER_NUM', $i);
             $template->parse('order', 'ordernumBlock', true);	
           }
           $template->parse('cat', 'catBlock', true);
        }
        global $LD_ADMIN_MNGR, $REL_APP_PATH;
        $template->set_var('REL_APP_PATH', $REL_APP_PATH);        
        $template->set_var('LD_ADMIN_MNGR', $LD_ADMIN_MNGR);        
        $this->showStatusMessage($template->parse('main', 'mainBlock', false));
     }
     
     function updateOrders()
     {
     	$order = $this->getRequestField('order');
     	
     	//$this->dump_array($order);
     	if (count(array_unique($order)) < count($order))
     	{
     	   $this->alert('ORDERS_NOT_CORRECT');	
     	}
     	$catObj = new Category($this->dbi);
     	$status = $catObj->updateCategoryOrders($order);
     	$this->showStatusMessage($this->getMessage($status ? 'REORDER_DONE' : 'REORDER_NOT_DONE'));
     	$this->generateDocNavigator();
     }
     
     function storeCategory()
     {
        $cat_name = $this->getRequestField('cat_name');
        $cat_order = $this->getRequestField('cat_order');
        $cat_desc = $this->getRequestField('cat_desc');
        $publishers = $this->getRequestField('publishers');
        $viewers = $this->getRequestField('viewers');
        
        $catObj = new Category($this->dbi);
        
        if (empty($cat_name) || empty($cat_order) || empty($publishers) || empty($viewers))
        {
           $this->alert('INPUT_MISSING');	
           exit;
        }
        
        $params = array(
                        'CAT_ID'       =>    'null',
                        'CAT_NAME'     =>    $cat_name,
                        'CAT_DESC'     =>    $cat_desc,
                        'CAT_ORDER'    =>    $cat_order,
                       );
         
         $status = $catObj->addCategory($params);
         if ($status)
          {
             
             $catObj->addCategoryPublishers($status, $publishers);
             $catObj->addCategoryViewers($status, $viewers);
             $this->showStatusMessage($this->getMessage('CAT_ADDED'));
             $this->generateDocNavigator();
             
             
          }
          else
          {
             
             $this->showStatusMessage($this->getMessage('CAT_NOT_ADDED'));		
          }
     }
     
     function updateDoc()
     {
        $nid = $this->getRequestField('nid');
        $cid = $this->getRequestField('cid');
        $pub_date = $this->getRequestField('pub_date');
        $heading = $this->getRequestField('heading');
        $body = $this->getRequestField('body');
        $cat = $this->getRequestField('cat');
        
        
        if (empty($cid) && !empty($cat))
        {
            $catObj = new Category($this->dbi);
            $cid = $catObj->getCategoryIDByName($cat);	
        }
        
        $docObj = new Doc($this->dbi);
        
        if (empty($cid) || empty($heading) || empty($body))
        {
           $this->alert('INPUT_MISSING');	
           exit;
        }
        list($pubMonth, $pubDay, $pubYear) = explode('/', $pub_date);
        
        if (!checkDate($pubMonth, $pubDay, $pubYear)/* || mktime(0,0,0, $pubMonth, $pubDay, $pubYear) < mktime()*/)
        {
           $this->alert('PUB_DATE_INCORRECT');
           exit;
        }
        $curHr = date("H", mktime());
        $curMin = date("i", mktime());
        $curSec = date("s", mktime());
        $params = array(
                        'DOC_ID'      =>    $nid,
                        'CAT_ID'       =>    $cid,
                        'HEADING'      =>    $heading,
                        'BODY'         =>    $body,
                        'PUBLISH_DATE' =>    mktime($curHr, $curMin, $curSec, $pubMonth, $pubDay, $pubYear)
                       );
         
         $status = $docObj->modifyDoc($params);
         if ($status)
          {
             
             $this->showStatusMessage($this->getMessage('DOC_MODIFIED'));	
             
             $msgObj = new Message($this->dbi);
             global $ANNOUNCE_LD_MOD_TEMPLATE, $LD_DETAILS_MNGR, $REL_APP_PATH;
             $template = new Template($this->getTemplateDir());
             $template->set_file('fh', $ANNOUNCE_LD_MOD_TEMPLATE);
             $template->set_block('fh', 'mainBlock', 'main');
             $catObj = new Category($this->dbi, $cid);
             $template->set_var('CATEGORY', stripslashes($catObj->getCategoryName()));
             $template->set_var('DOCUMENT_TITLE', stripslashes($heading));
             $template->set_var('LD_DETAILS_MNGR', $LD_DETAILS_MNGR);
             $template->set_var('REL_APP_PATH', $REL_APP_PATH);
             $template->set_var('DOC_ID', $nid);
             
             $msg = $template->parse('main', 'mainBlock', false);
             $msg_id = $msgObj->addMessage(LD_UPDATE_TITLE." (".stripslashes($heading).")", mktime(), $msg, mktime(), $this->getUID(), $this->isAdmin ? 1 : 0);
             $msgObj->addViewer($msg_id, $catObj->getViewers($cid));
          }
          else
          {
             
             $this->showStatusMessage($this->getMessage('DOC_NOT_MODIFIED'));		
          }
     }
     
     function updateCategory()
     {
        $cid = $this->getRequestField('cid');
        $cat_name = $this->getRequestField('cat_name');
        $cat_order = $this->getRequestField('cat_order');
        $cat_desc = $this->getRequestField('cat_desc');
        $publishers = $this->getRequestField('publishers');
        $viewers = $this->getRequestField('viewers');
        
        
        
        
        $catObj = new Category($this->dbi);
        
        if (empty($cid) || empty($cat_name) || empty($cat_order))
        {
           $this->alert('INPUT_MISSING');	
           exit;
        }
        
        $params = array(
                        'CAT_ID'       =>    $cid,
                        'CAT_NAME'     =>    $cat_name,
                        'CAT_DESC'     =>    $cat_desc,
                        'CAT_ORDER'    =>    $cat_order
                       );
         
         $status = $catObj->modifyCategory($params);
         if ($status)
          {
             $catObj->deleteCategoryPublishers($cid);
             $catObj->deleteCategoryViewers($cid);
             $catObj->addCategoryPublishers($cid, $publishers);
             $catObj->addCategoryViewers($cid, $viewers);             
             $this->showStatusMessage($this->getMessage('CAT_MODIFIED'));	
             $this->generateDocNavigator();
          }
          else
          {             
             $this->showStatusMessage($this->getMessage('CAT_NOT_MODIFIED'));		
          };
     }
     
     function generateDocNavigator()
     {
         global $LD_CATEGORY_NAV_DIR, $LD_CATEGORY_NAV_TEMPLATE, $LD_CATEGORY_NAV_OUTFILE;
         global $REL_APP_PATH, $LD_MNGR;
         
         $catObj = new Category($this->dbi);
         $categories = $catObj->getCategories();
         
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', $LD_CATEGORY_NAV_TEMPLATE);
         $template->set_block('fh', 'mainBlock', 'main');
         $template->set_block('mainBlock', 'catNavLineBlock', 'line');
         $template->set_block('catNavLineBlock', 'catBlock', 'cat');
         $template->set_var('REL_APP_PATH', $REL_APP_PATH);
         $template->set_var('LD_MNGR', $LD_MNGR);
         
         $totalCounter = 0;
         if (!empty($categories))
         {  
             $numCategories = sizeof($categories);
             $numLines = ceil((sizeof($categories))/CAT_PER_LINE);
             for($i=1;$i<=$numLines;$i++)
             {
                $template->set_var('cat', null);
                for ($j=1;$j<=CAT_PER_LINE;$j++)
                {
                   
                   $cname = array_pop($categories);
                   if(!empty($cname))
                   {
                      $totalCounter++;
                      $template->set_var('LINK_CAT_NAME', preg_replace("/\s/","+",stripslashes($cname)));
                      $template->set_var('CAT_NAME', stripslashes($cname));
                      
                      $template->set_var('SEPARATOR', ($j == CAT_PER_LINE || $totalCounter == $numCategories) ? null : SEPARATOR);
                      $template->parse('cat', 'catBlock', true);
                   }   
                }
                $template->parse('line', 'catNavLineBlock', true);
             }
             
         }
         else
         {
            $template->set_var('line', null);
         }
         
         $content = $template->parse('main', 'mainBlock', false);
         $fp = fopen($LD_CATEGORY_NAV_DIR.'/'.$LD_CATEGORY_NAV_OUTFILE, "w");
         fwrite($fp, $content);
     }

     
     function displayAddModDocMenu($cmd)
     {
        global $ADD_MOD_DOC_TEMPLATE, $LD_ADMIN_MNGR;
        global $LD_MNGR, $LD_DETAILS_MNGR, $LD_ADMIN_MNGR;
        global $THEME_TEMPLATE;
        global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
        global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
        
        $nid = $this->getRequestField('nid');
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
        $template->set_file('fh', $ADD_MOD_DOC_TEMPLATE);
        
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        
        $catObj = new Category($this->dbi);
        $categories = $catObj->getCategories();
        
        $cat = $this->getRequestField('cat');
        if (!empty($cat))
        {
          $selCid = $catObj->getCategoryIDbyName($cat);
          $template->set_var('CATNAME', $cat);
        }  
        
        if (!empty($categories))
        {
            $template->set_var('cat', null);
            while (list($cid, $cname) =  each($categories))
            {
            	$template->set_var('CAT_ID', $cid);
            	$template->set_var('CAT_NAME', stripslashes($cname));
            	if (isset($selCid) && $selCid == $cid)
            	{
            	   $template->set_var('CHOSEN', 'selected');
            	   $template->set_var('DIS_OPTION', 'DISABLED');
            	}
            	else
            	{
            	   $template->set_var('CHOSEN', null);	
            	}   
            	$template->parse('cat', 'catBlock', true);
            }
        }
        $template->set_var('HEADING', null);
        $template->set_var('BODY', null);
        $template->set_var('PUB_DATE', date("m/d/Y",mktime()));
           
        	
        global $REL_APP_PATH;
        $template->set_var('LD_ADMIN_MNGR', $REL_APP_PATH.'/'.$LD_ADMIN_MNGR);
        
        if (!empty($nid))
        {
           if (sizeof($nid)>1)
           {
              $this->alert('MOD_ONE_AT_A_TIME');
              exit;
           }
           $docObj = new Doc($this->dbi, $nid[0]);
           
           $template->set_var('NID', $nid[0]);
           $template->set_var('HEADING', $docObj->getHeading());
           $template->set_var('BODY', $docObj->getBody());
           $template->set_var('PUB_DATE', date("m/d/Y",$docObj->getPublishDate()));
           if (!empty($categories))
           {
            reset($categories);
            $template->set_var('cat', null);
            while (list($cid, $cname) =  each($categories))
            {
            	
            	$template->set_var('CAT_ID', $cid);
            	$template->set_var('CAT_NAME', $cname);
            	
            	if ($docObj->getCategory() == $cid)
            	{
            	 $template->set_var('CHOSEN', 'selected');
            	}
            	else
            	{
            	 $template->set_var('CHOSEN', null);	
            	} 
            	$template->parse('cat', 'catBlock', true);
            }
          }
           
        }
        
        $template->set_var('CMD', $cmd);
        
        $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);

          //$template->set_var('USER_NAME', ucfirst($thisUser->getName()));

          $themeTemplate->set_var('CONTENT_BLOCK',
                                  $template->parse('mblock', 'mainBlock'));

          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');
        
        //$template->parse('main', 'mainBlock', false);
        //$template->pparse('output', 'fh');
        	
     }
     
     
     /********/
     function displayAddModCategoryMenu($cmd)
     {
        global $ADD_MOD_CATEGORY_TEMPLATE, $LD_ADMIN_MNGR;
        global $THEME_TEMPLATE;
        global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
        global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
         
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
        $template->set_file('fh', $ADD_MOD_CATEGORY_TEMPLATE);
        
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'pubBlock', 'pub');
        $template->set_block('mainBlock', 'viewBlock', 'view');
        
        
        
        $template->set_var('CAT_DESC', null);
        $template->set_var('CAT_NAME', null);
        $cObj = new Category($this->dbi);
        $lastOrder = $cObj->getHighestOrder();
        $template->set_var('CAT_ORDER', $lastOrder+1);
        
        
        if (!empty($cid))
        {
           if (sizeof($cid)>1)
           {
              $this->alert('MOD_ONE_AT_A_TIME');
              exit;
           }
           $catObj = new Category($this->dbi, $cid[0]);
           $publishers = $catObj->getPublishers();
           $viewers = $catObj->getViewers();	
           $template->set_var('CID', $cid[0]);
           $template->set_var('CAT_NAME', stripslashes($catObj->getCategoryName()));
           $template->set_var('CAT_DESC', stripslashes($catObj->getCategoryDesc()));
           $template->set_var('CAT_ORDER', $catObj->getCategoryOrder());
           
        }
        
        $authDBI = new DBI(USER_DB_URL);
        $userObj = new User($authDBI);
        $users = $userObj->getUserList();
        asort($users);
        reset($users);
        $pub_every = false;
        if (!empty($publishers) && in_array(0, $publishers))
        {
          
           $template->set_var('PUB_EVERY_CHOSEN', 'SELECTED');
           $pub_every = true;
        }
        $view_every = false;
        if (!empty($viewers) && in_array(0, $viewers))
        {
           $template->set_var('VIEW_EVERY_CHOSEN', 'SELECTED');
           $view_every = true;
        }
        
        while(list($uid, $uname) = each($users))
        {
           list($name, $host) = explode('@', $uname);
           $template->set_var('PUB_UID', $uid);	
           $template->set_var('VIEW_UID', $uid);
           if(!empty($publishers))
           {   
              if (in_array($uid, $publishers))
              {
                 $template->set_var('PUB_CHOSEN', $pub_every ? null : 'SELECTED');	
              }
              else
              {
                 $template->set_var('PUB_CHOSEN', null);		
              }
           }   
           else
           {
              $template->set_var('PUB_CHOSEN', null);		
           }
           if(!empty($viewers))
           {   
              if (in_array($uid, $viewers))
              {
                 $template->set_var('VIEW_CHOSEN',  $view_every ? null : 'SELECTED');	
              }
              else
              {
                 $template->set_var('VIEW_CHOSEN', null);		
              }
           }   
           else
           {
              $template->set_var('VIEW_CHOSEN', null);		
           }
           $template->set_var('PUB_NAME', ucfirst($name));
           $template->set_var('VIEW_NAME', ucfirst($name));
           $template->parse('view', 'viewBlock', true);
           $template->parse('pub', 'pubBlock', true);
        }
           
        global $REL_APP_PATH;
        $template->set_var('LD_ADMIN_MNGR', $REL_APP_PATH.'/'.$LD_ADMIN_MNGR);
        
        
        
        $template->set_var('CMD', $cmd);
        
        $themeTemplate->set_var('SERVER_NAME', $this->get_server());
        $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);

          //$template->set_var('USER_NAME', ucfirst($thisUser->getName()));

        $themeTemplate->set_var('CONTENT_BLOCK',
                                $template->parse('mblock', 'mainBlock'));

        $themeTemplate->parse('cnblock', 'contentBlock');
        $themeTemplate->parse('mmblock', 'mmainBlock');
        $themeTemplate->pparse('output', 'fh');
        	
     }
     /********/
     
     
     function deleteDoc()
     {
        $nid = $this->getRequestField('nid');
        if (empty($nid))
        {
           $this->alert('SELECT_DOC');	
        }
        $docObj = new Doc($this->dbi);
        
        
        foreach($nid as $docID)
        {
           $heading = $docObj->getHeading($docID);
           $status = $docObj->deleteDoc($docID);
           if ($status)
           {
              $status = $docObj->deleteResponsesByDocID($docID);
              $msgObj = new Message($this->dbi);
              $msgID = $msgObj->getMsgIDbyMessageTitle(LD_ADD_TITLE." (".stripslashes($heading).")");
              if ($msgID)
              {
              	$msgObj->deleteMessage($msgID);
              }	
              $msgID = $msgObj->getMsgIDbyMessageTitle(LD_UPDATE_TITLE." (".stripslashes($heading).")"); 
              if ($msgID)
              {
                $msgObj->deleteMessage($msgID);
              }   
           }
        }
         if ($status)
          {
             $this->showStatusMessage($this->getMessage('DOC_DELETED'));	
             
          }
          else
          {
             $this->showStatusMessage($this->getMessage('DOC_NOT_DELETED'));		
          }
     }
     
     function deleteCategory()
     {
        $cid = $this->getRequestField('cid');
        if (empty($cid))
        {
           $this->alert('SELECT_CAT');	
        }
        $catObj = new Category($this->dbi);
        $docObj = new Doc($this->dbi);
        
        foreach($cid as $catID)
        {
           $docArr = $docObj->getDocesByCatID($catID);
           $status = $catObj->deleteCategory($catID);
           if ($status)
           {
              if (!empty($docArr))
              {	
                 $status = $catObj->deleteDocesByCatID($catID);
                 if ($status)
                 {
                     while(list($nid, $doc) = each($docArr))
                     {
                        $status = $docObj->deleteResponsesByDocID($nid);  
                     }   
                 }
              }   
           }
        }
         if ($status)
          {
             $this->showStatusMessage($this->getMessage('CAT_DELETED'));	
             $this->generateDocNavigator();
          }
          else
          {
             $this->showStatusMessage($this->getMessage('CAT_NOT_DELETED'));		
          }
     }
     
     function deleteResponse()
     {
        $rid = $this->getRequestField('rid');
        
        if (empty($rid))
        {
           $this->alert('SELECT_RESPONSE');	
        }
        $respObj = new Response($this->dbi);
        $status = false;
        
        foreach($rid as $respID)
        {
           $status .= $respObj->deleteResponse($respID);
        }
         if ($status)
          {
             $this->showStatusMessage($this->getMessage('RESP_DELETED'));	
          }
          else
          {
             $this->showStatusMessage($this->getMessage('RESP_NOT_DELETED'));		
          }
     }
     
     function showStatusMessage($statusMessage)
     {
         global $THEME_TEMPLATE;
         global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
         global $SESSION_AUTO_TIP_SHOWN;
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
     
     function authorize()
     {
         $cat = $this->getRequestField('cat');
         if ($this->getUID() > 0)
          {
             $user_dbi = new DBI(USER_DB_URL);
             $userObj = new User($user_dbi, $this->getUID());
             
             if ($userObj->getType() == LD_ADMIN_TYPE)
             {
                $this->isAdmin = true;
                return true;
             }
             else
             {
                if (!empty($cat))
                {
                    $catObj = new Category($this->dbi);
                    $cid = $catObj->getCategoryIDbyName($cat);
                    if (!$catObj->isPublishable($cid, $this->getUID()))
                    {
                       return false;	
                    }
                    else
                    {
                       return true;	
                    }	
                }
             }
          }
          
     }
     
     



   }//class
   
   

   
   $thisApp = new prAdminMngr(
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
