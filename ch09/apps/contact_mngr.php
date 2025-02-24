<?php
   
   require_once "contact.conf";
   require_once $THEME_CLASS;
   require_once $CATEGORY_CLASS;
   require_once $CONTACT_CLASS;
   require_once $MESSAGE_CLASS;
   
   class contactMngr extends PHPApplication {

     function run()
     {    
        $cmd = strtolower($this->getRequestField('cmd'));

        $themeObj = new Theme($this->dbi,null,'pr_tool');

        $this->themeObj = $themeObj;
        
        $this->theme = $themeObj->getUserTheme($this->getUID());

        if (!strcmp($cmd, 'admin'))
        {
           $this->displayContactMngrHome(CONTACT_HOME_TEMPLATE);          
        }
        else if (!strcmp($cmd, 'add'))
        {
           $this->addDriver();
        }
        else if (!strcmp($cmd, 'modify'))
        {
           $this->modifyDriver();	
        }
        else if (!strcmp($cmd, 'delete'))
        {
           $this->deleteContact();	
        }        
        else if (!strcmp($cmd, 'detail'))
        {
           $this->showDetail();	
        }
        else if (!strcmp($cmd, 'mail'))
        {
           if ($this->isAdmin)
           {
              $this->mailDriver();
           }
           else
           {
              $this->alert('UNAUTHORIZED_ACCESS');	
           }   
        }
        else
        {
           $this->searchDriver();	
        }
     }
     
     function authorize()
     {
        $cmd = strtolower($this->getRequestField('cmd'));
     	
     	$this->setUserType();
     	if (empty($cmd) || !strcmp($cmd, 'detail') || !strcmp($cmd, 'search'))
     	{
     	   return true;	
     	}
     	else 
     	{
     	   return isset($this->isAdmin) ? $this->isAdmin : FALSE;
     	}	
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
     
     function mailDriver()
     {
        $step= $this->getRequestField('step');
        if ($step == 1 || empty($step))
        {
           $this->displayMailMenu();
        }
        else if ($step == 2)
        {
           $this->mailToContact();	
        }
        else if ($step == 3)
        {
           $this->showMail();		
        }
        	
     }
     
     function displayMailMenu()
     {
        global $REL_APP_PATH;
        
        $cid = $this->getRequestField('cid');
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_MAIL_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $contactObj = new Contact($this->dbi, $cid);
        $contactName = $contactObj->getColumnValue('CONTACT_FIRST').' '.$contactObj->getColumnValue('CONTACT_INITIAL').' '.$contactObj->getColumnValue('CONTACT_LAST');
        $contactEmail = strtok(($contactObj->getColumnValue('EMAIL')), ",");
        $contactEmailExt = substr(strstr($contactObj->getColumnValue('EMAIL'), ','),1);
        $template->set_var('MAIL_TO', $contactName);
        $template->set_var('MAIL_ADDR', $contactEmail);
        $template->set_var('EMAIL_EXT', $contactEmailExt);
        $template->set_var('CONTACT_ID', $cid);
        $template->set_var('CONTACT_MNGR', $REL_APP_PATH.'/'.CONTACT_MNGR);
        $template->set_var('TS', mktime());
        $this->showContents($template->parse('main', 'mainBlock'));
     }
     
     function mailToContact()
     {
        $cid = $this->getRequestField('cid');
        $cc = $this->getRequestField('cc');
        $sub = $this->getRequestField('sub');
        $body = $this->getRequestField('body');
        $ts = $this->getRequestField('ts');
        
        if (empty($body))
        {
           $this->alert('NO_MAIL_BODY_SPECIFIED');	
        }
        
        $contactObj = new Contact($this->dbi, $cid);
        $from = CONTACT_MNGR_EMAIL_ADDR;
        $to = strtok(($contactObj->getColumnValue('EMAIL')), ",");
        
        
        $headers = "From: $from\r\n";

        $headers .= "Cc: $cc\r\n";
        
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

        $flag = $this->getUID().$ts;
        
        $contactObj->storeMail($cid, $cc, $sub, stripslashes($body), mktime(), $flag);
        
        mail($to, stripslashes($sub), stripslashes($body), $headers);
        
        $this->showContents($this->getMessage('MAIL_SENT'));
     }
     
     function showMail()
     {
        $mid = $this->getRequestField('mid');
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_MAIL_DETAIL_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        
        $mailContactObj = new Contact($this->dbi);
        $mailDetails = $mailContactObj->getMailDetails($mid);
        
        $contactObj = new Contact($this->dbi, $mailDetails->CONTACT_ID);
        $to = $contactObj->getColumnValue('CONTACT_FIRST').' '.$contactObj->getColumnValue('CONTACT_INITIAL').' '.$contactObj->getColumnValue('CONTACT_LAST');
        
        $template->set_var(array
                               (
                                'DATE' => date("M-d-Y", $mailDetails->SEND_TS),
                                'TO'   => stripslashes($to),
                                'CC'   => stripslashes($mailDetails->CC_TO),
                                'SUB'  => stripslashes($mailDetails->SUBJECT),
                                'BODY' => stripslashes($mailDetails->BODY)
                               )
                          );
        
        $template->parse('main', 'mainBlock', false);
        
        $template->pparse('output', 'fh');
        
     }
     
     function showDetail()
     {
        $cid = $this->getRequestField('cid');
        if (empty($cid))
        {
           $this->alert('SELECT_CONTACT');	
        }
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_DETAILS_TEMPLATE);        
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'remBlock', 'rem');
        $template->set_block('mainBlock', 'adminBlock', 'admin');
        $template->set_block('adminBlock', 'mailBlock', 'mailb');
        
        $contactObj = new Contact($this->dbi, $cid);
        $keywordArr = $contactObj->getKeywords();
        $keys = empty($keywordArr) ? null : implode(',', $keywordArr);
        global $REL_APP_PATH;
        $template->set_var(array(
                                 'CONTACT_NAME'  =>  stripslashes($contactObj->getColumnValue('CONTACT_FIRST').' '.$contactObj->getColumnValue('CONTACT_INITIAL').' '.$contactObj->getColumnValue('CONTACT_LAST')),
                                 'CONTACT_EMAIL' =>  strlen($contactObj->getColumnValue('COMPANY_NAME')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('EMAIL')),
                                 'COMPANY_NAME'  =>  strlen($contactObj->getColumnValue('COMPANY_NAME')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('COMPANY_NAME')),
                                 'COMPANY_ADDR'  =>  strlen($contactObj->getColumnValue('COMPANY_ADDRESS')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('COMPANY_ADDRESS')),
                                 'HOME_ADDR'     =>  strlen($contactObj->getColumnValue('HOME_ADDRESS')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('HOME_ADDRESS')),
                                 'PHONE'         =>  strlen($contactObj->getColumnValue('PHONE')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('PHONE')),
                                 'FAX'           =>  strlen($contactObj->getColumnValue('FAX')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('FAX')),
                                 'URL'           =>  strlen($contactObj->getColumnValue('URL')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('URL')),
                                 'REFERENCE'     =>  strlen($contactObj->getColumnValue('REFERENCE')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('REFERENCE')),
                                 'SOURCE'        =>  strlen($contactObj->getColumnValue('SOURCE')) <= 0 ? 'N/A' : stripslashes($contactObj->getColumnValue('SOURCE')),
                                 'KEYWORDS'      =>  strlen($keys) <= 0 ? 'N/A' : stripslashes($keys),
                                 'CONTACT_MNGR'  =>  $REL_APP_PATH.'/'.CONTACT_MNGR,
                                 'CONTACT_ID'    =>  $cid
                                )
                          );
                          
        $rems = $contactObj->getReminders();
        if (!empty($rems))
        {
            while (list($rid, $rinfo) = each($rems))
            {
               $template->set_var(
                                  array
                                  (
                                   'REMIND_ABOUT'    =>   $rinfo->REMIND_ABOUT,
                                   'DAY_WEEK'        =>   date('D', 'on '.($rinfo->REMIND_DATE)),
                                   'DAY_MON'         =>   date('jS', ($rinfo->REMIND_DATE)),
                                   'MON'             =>   date('M', ($rinfo->REMIND_DATE)),
                                   'YEAR'            =>   date('Y', ($rinfo->REMIND_DATE)),  
                                  )
                                 );	
               $template->parse('rem', 'remBlock', true);
            }
        }
        else
        {
            $template->set_var(
                               array
                               (
                                'REMIND_ABOUT'    =>   'No Reminder to Show',
                                'DAY_WEEK'        =>   null,
                                'DAY_MON'         =>   null,
                                'MON'             =>   null,
                                'YEAR'            =>   null,  
                               )
                              );	
            $template->parse('rem', 'remBlock');                 
        }
        if (isset($this->isAdmin) && $this->isAdmin)
        {
           $mails = $contactObj->getMails($cid);
           if (!empty($mails))
           {
              while (list($mid, $minfo) = each($mails))
              {
                 $template->set_var(
                                    array
                                    (
                                     'DATE'            =>   date("M-d-Y", $minfo->SEND_TS),
                                     'MAIL_ID'         =>   $minfo->MAIL_ID,
                                     'SUB'             =>   empty($minfo->SUBJECT) ? 'No Subject' : 'sub: '.stripslashes($minfo->SUBJECT),
                                     'NO_MAIL'         =>   null 
                                    )
                                   );	
                 $template->parse('mailb', 'mailBlock', true);
              }
           }
           else
           {
               $template->set_var(
                                  array
                                  (
                                   'DATE'        =>   null,
                                   'MAIL_ID'     =>   null,
                                   'SUB'         =>   null,
                                   'NO_MAIL'     =>  'No mail to show'
                                   
                                  )
                                 );	
               $template->parse('mailb', 'mailBlock');                 
           }
           $template->parse('admin', 'adminBlock', false);
        }
        else
        {
           $template->set_var('admin', null);	
        }
        
        
        $this->showContents($template->parse('mblock', 'mainBlock'));
     }

     function displayContactMngrHome($templateFile = null, $mainMenu = null)
     {
        global $REL_APP_PATH;
        
        $cat = $this->getRequestField('cat');
        $subcat = $this->getRequestField('subcat');
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', $templateFile);
        
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        $template->set_block('mainBlock', 'subcatBlock', 'subcat');
        $template->set_block('mainBlock', 'contactBlock', 'contact');
        
        $template->set_var('contact', null);
        $template->set_var('PROMPT', 'Select a category first');
        $template->set_var('CONT_PROMPT', 'Select category and subcategory first');
        $template->set_var('SEL_CAT_ID', null);
        
        $catObj = new Category($this->dbi);
        
        $cats = $catObj->getParentCategories();
        if (!empty($cats))
        {
           while (list($catID, $cat_name) = each($cats))
           {
              $template->set_var(array
                                      (
                                       'CAT_ID'     => $catID,
                                       'CAT_NAME'   => $cat_name,
                                       'CAT_CHOSEN' => ($catID == $cat) ? 'selected' : null
                                      )                                    
                                );    
              $template->parse('cat', 'catBlock', true);
           }
        }
        else
        {
           $template->set_var('cat', null);	
        }   
        if ($cat > 0)
        {           
           $template->set_var('SEL_CAT_ID', $cat);
           $template->set_var('PROMPT', 'Select a subcategory');
           $template->set_var('CONT_PROMPT', 'Select a subcategory first');
           $subCats = $catObj->getSubCategories($cat);
           if (!empty($subCats))
           {
              while (list($subCatID, $subCatName) = each($subCats))
              {
                 $template->set_var(array( 
                                          'SUBCAT_ID'     =>  $subCatID,
                                          'SUBCAT_NAME'   =>  $subCatName,
                                          'SUBCAT_CHOSEN' =>  ($subCatID == $subcat) ? 'selected' : null
                                         )
                                    );                 
                 $template->parse('subcat', 'subcatBlock', true);
              }
           }
           else
           {
              $template->set_var('subcat', null);
           }           
        }
        else
        {
           $template->set_var('subcat', null);
        }
        if ($subcat > 0 && $cat> 0)
        {
           $template->set_var('SEL_CAT_ID', $subcat);
           $template->set_var('CONT_PROMPT', 'Select a Contact');
           $contactObj = new Contact($this->dbi);
           $contacts = $contactObj->getContactsByCatID($subcat);
           if (!empty($contacts))
           {
               while(list($cid, $info) = each($contacts))
               {
                  $template->set_var('CONTACT_ID', $cid);	
                  $template->set_var('CONTACT_NAME', $info->CONTACT_FIRST.' '.$info->CONTACT_INITIAL.' '.$info->CONTACT_LAST);
                  $template->parse('contact', 'contactBlock', true);
               }
           }
           else
           {
              $template->set_var('contact', null);	
           }
        }
        $template->set_var('PARENT_CAT', $cat);
        $template->set_var('CONTACT_MNGR', $REL_APP_PATH.'/'.CONTACT_MNGR);
        $template->set_var('CONTACT_CAT_MNGR', $REL_APP_PATH.'/'.CONTACT_CAT_MNGR);
        $this->showContents($template->parse('mblock', 'mainBlock'));
     }
     
     function deleteContact()
     {
     	$cid = $this->getRequestField('cid');
     	if (empty($cid))
     	{
     	   $this->alert('SELECT_CONTACT');
     	   exit;
     	}
     	$contactObj = new Contact($this->dbi);
     	$motds = $contactObj->getRelatedMOTDs($cid);
     	global $INTRANET_DB_URL;
        $intraDB = new DBI($INTRANET_DB_URL);
        $msgObj = new Message($intraDB);
           
     	if (!empty($motds))
     	{
     	   foreach($motds as $mid)
     	   {
     	      $msgObj->deleteMessage($mid);
     	      $msgObj->deleteViewers($mid);	
     	   }
     	}
     	
     	$status = $contactObj->deleteContact($cid);
     	
     	$this->showContents($this->getMessage($status ? 'CONTACT_DELETED' : 'CONTACT_NOT_DELETED'));
     }
     
     function addDriver()
     {
        $step = $this->getRequestField('step');
        if($step == 1 || empty($step))
        {
           $this->displayAddModifyMenu('add');	
        }
        else if($step == 2)
        {
           $this->addContact();	
        } 
     }
     
     function addContact()
     {
        $cat = $this->getRequestField('cat');
        $subcat = $this->getRequestField('subcat');
        $first = $this->getRequestField('first');
        $last = $this->getRequestField('last');
        $initial = $this->getRequestField('initial');
        $company_name = $this->getRequestField('company_name');
        $home_addr = $this->getRequestField('home_addr');
        $company_addr = $this->getRequestField('company_addr');
        $keyword = $this->getRequestField('keyword');
        $email = $this->getRequestField('email');
        $email_ext = $this->getRequestField('email_ext');
        $phone = $this->getRequestField('phone');
        $phone_ext = $this->getRequestField('phone_ext');
        $fax = $this->getRequestField('fax');
        $fax_ext = $this->getRequestField('fax_ext');
        $reference = $this->getRequestField('reference');
        $source = $this->getRequestField('source');
        $url = $this->getRequestField('url');
        $ts = $this->getRequestField('ts');
        $rem1 = $this->getRequestField('rem1');
        $rem2 = $this->getRequestField('rem2');
        $rem3 = $this->getRequestField('rem3');
        $rem4 = $this->getRequestField('rem4');
        $rem5 = $this->getRequestField('rem5');
        $date1 = $this->getRequestField('date1');
        $date2 = $this->getRequestField('date2');
        $date3 = $this->getRequestField('date3');
        $date4 = $this->getRequestField('date4');
        $date5 = $this->getRequestField('date5');
        
        
        $params = array(
                                     'CONTACT_ID'        =>    'null', 
                                     'CAT_ID'            =>    empty($subcat) ? $cat : $subcat, 
                                     'CONTACT_FIRST'     =>    $first,
                                     'CONTACT_INITIAL'   =>    $initial,
                                     'CONTACT_LAST'      =>    $last,                                                               
                                     'EMAIL'             =>    $email.(empty($email_ext) ? null : ','.$email_ext), 
                                     'PHONE'             =>    $phone.(empty($phone_ext) ? null : ','.$phone_ext), 
                                     'FAX'               =>    $fax.(empty($fax_ext) ? null : ','.$fax_ext), 
                                     'URL'               =>    $url, 
                                     'COMPANY_NAME'      =>    $company_name, 
                                     'COMPANY_ADDRESS'   =>    $company_addr, 
                                     'HOME_ADDRESS'      =>    $home_addr, 
                                     'SOURCE'            =>    $source, 
                                     'REFERENCE'         =>    $reference,
                                     'FLAG'              =>    $ts.$this->getUID()
                       );
                       
         $contactObj = new Contact($this->dbi);
         $cid = $contactObj->addContact($params);
         if ($cid)
         {
            if (!empty($keyword))
            {
               $contactObj->addKeywords($cid, $keyword);	
            }
            for ($i=1;$i<=5;$i++)
            {
               $rem_about = 'rem'.$i;
               $rem_date  = 'date'.$i;
               if (!empty($$rem_about) && !empty($$rem_date))
               {
                  list($remMon, $remDay, $remYr) = explode('/', $$rem_date);
                  $remDate = mktime (0, 0, 0, $remMon, $remDay, $remYr);
                  
                  
                  
                  $template = new Template($this->getTemplateDir());
                  $template->set_file('fh',REMINDER_MSG_TEMPLATE);
                  $template->set_block('fh', 'mainBlock', 'main');
                  $template->set_var('CONTENTS', $$rem_about);
                  $template->set_var('CONTACT_ID', $cid);
                  $template->set_var('CONTACT_NAME', $first.' '.$initial.' '.$last);
                  global $REL_APP_PATH;
                  $template->set_var('CONTACT_URL', $REL_APP_PATH.'/'.CONTACT_MNGR);
                  
                  $msg = $template->parse('main', 'mainBlock');
                  
                  global $INTRANET_DB_URL;
                  $intraDB = new DBI($INTRANET_DB_URL);
                  $msgObj = new Message($intraDB);
                  $msg_id  = $msgObj->addMessage("REMINDER ". $i ." FROM CONTACT MNGR (related to <font color=white >".$first.' '.$initial.' '.$last."</font>)", $remDate, $msg, mktime().$i, $this->getUID(), 1);
                  $msgObj->addViewer($msg_id, array($this->getUID()));
                  
                  $contactObj->addReminder($cid, $this->getUID(), $$rem_about, $remDate, $msg_id);
               }
            }   
         }
         $this->showContents($this->getMessage(($cid) ? 'CONTACT_ADDED' : 'CONTACT_NOT_ADDED'));
     }
     
     function modifyContact()
     {
        $cid = $this->getRequestField('cid');
        $cat = $this->getRequestField('cat');
        $subcat = $this->getRequestField('subcat');
        $first = $this->getRequestField('first');
        $last = $this->getRequestField('last');
        $initial = $this->getRequestField('initial');
        $company_name = $this->getRequestField('company_name');
        $home_addr = $this->getRequestField('home_addr');
        $company_addr = $this->getRequestField('company_addr');
        $keyword = $this->getRequestField('keyword');
        $email = $this->getRequestField('email');
        $email_ext = $this->getRequestField('email_ext');
        $phone = $this->getRequestField('phone');
        $phone_ext = $this->getRequestField('phone_ext');
        $fax = $this->getRequestField('fax');
        $fax_ext = $this->getRequestField('fax_ext');
        $reference = $this->getRequestField('reference');
        $source = $this->getRequestField('source');
        $url = $this->getRequestField('url');
        $ts = $this->getRequestField('ts');
        $rem1 = $this->getRequestField('rem1');
        $rem2 = $this->getRequestField('rem2');
        $rem3 = $this->getRequestField('rem3');
        $rem4 = $this->getRequestField('rem4');
        $rem5 = $this->getRequestField('rem5');
        $date1 = $this->getRequestField('date1');
        $date2 = $this->getRequestField('date2');
        $date3 = $this->getRequestField('date3');
        $date4 = $this->getRequestField('date4');
        $date5 = $this->getRequestField('date5');
        
        
        
                
        $params = array(
                                     'CONTACT_ID'         =>    $cid, 
                                     'CAT_ID'             =>    empty($subcat) ? $cat : $subcat, 
                                     'CONTACT_FIRST'      =>    $first,
                                     'CONTACT_INITIAL'    =>    $initial,
                                     'CONTACT_LAST'       =>    $last,
                                     'EMAIL'              =>    $email.(empty($email_ext) ? null : ','.$email_ext), 
                                     'PHONE'              =>    $phone.(empty($phone_ext) ? null : ','.$phone_ext), 
                                     'FAX'                =>    $fax.(empty($fax_ext) ? null : ','.$fax_ext), 
                                     'URL'                =>    $url, 
                                     'COMPANY_NAME'       =>    $company_name, 
                                     'COMPANY_ADDRESS'    =>    $company_addr, 
                                     'HOME_ADDRESS'       =>    $home_addr, 
                                     'SOURCE'             =>    $source, 
                                     'REFERENCE'          =>    $reference,
                                     'FLAG'               =>    $ts.$this->getUID()
                       );
        $contactObj = new Contact($this->dbi);
        $status = $contactObj->modifyContact($params);
        if ($status)
        {
           if (!empty($keyword))
           {
           	$contactObj->modifyKeywords($cid, $keyword);	
           }
           //mkmk del all rem related to this contact, and msgs and msg-viewers.
           
           $contactObj->deleteRemindersByContactID($cid);
           
           $motds = $contactObj->getRelatedMOTDs($cid);
     	   global $INTRANET_DB_URL;
           $intraDB = new DBI($INTRANET_DB_URL);
           $msgObj = new Message($intraDB);
              
     	   if (!empty($motds))
     	   {
     	      foreach($motds as $mid)
     	      {
     	         $msgObj->deleteMessage($mid);
     	         $msgObj->deleteViewers($mid);	
     	      }
     	   }
            
           for ($i=1;$i<=5;$i++)
            {
               $rem_about = 'rem'.$i;
               $rem_date  = 'date'.$i;
               
               if (!empty($$rem_about) && !empty($$rem_date))
               {
                  list($remMon, $remDay, $remYr) = explode('/', $$rem_date);
                  $remDate = mktime (0, 0, 0, $remMon, $remDay, $remYr);
                  $template = new Template($this->getTemplateDir());
                  $template->set_file('fh',REMINDER_MSG_TEMPLATE);
                  $template->set_block('fh', 'mainBlock', 'main');
                  $template->set_var('CONTENTS', $$rem_about);
                  $template->set_var('CONTACT_ID', $cid);
                  $template->set_var('CONTACT_NAME', $first.' '.$initial.' '.$last);
                  global $REL_APP_PATH;
                  $template->set_var('CONTACT_URL', $REL_APP_PATH.'/'.CONTACT_MNGR);
                  
                  $msg = $template->parse('main', 'mainBlock');
                  
                  global $INTRANET_DB_URL;
                  $intraDB = new DBI($INTRANET_DB_URL);
                  $msgObj = new Message($intraDB);
                  $msg_id  = $msgObj->addMessage("REMINDER ". $i ." FROM CONTACT MNGR (related to <font color=white >".$first.' '.$initial.' '.$last."</font>)", $remDate, $msg, mktime().$i, $this->getUID(), 1);
                  $msgObj->addViewer($msg_id, array($this->getUID()));
                  $contactObj->addReminder($cid, $this->getUID(), $$rem_about, $remDate, $msg_id);
               }
            }	
        }         
        $this->showContents($this->getMessage(($status) ? 'CONTACT_MODIFIED' : 'CONTACT_NOT_MODIFIED'));         
     }
     
     function modifyDriver()
     {
        $step = $this->getRequestField('step');
        if($step == 1 || empty($step))
        {
           $this->displayAddModifyMenu('modify');	
        }
        else if($step == 2)
        {
           $this->modifyContact();	
        } 
     }
     
     function displayAddModifyMenu($mode)
     {
       	global $REL_APP_PATH;
       	
       	$cat = $this->getRequestField('cat');
       	$cid = $this->getRequestField('cid');
       	
       	$template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_INFO_ADD_MOD_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        $template->set_block('mainBlock', 'subcatBlock', 'subcat');
        
        $template->set_var(array
                               (
                               'MODE'         => ucwords($mode),
                               'CONTACT_MNGR' => $REL_APP_PATH.'/'.CONTACT_MNGR,
                               'PROMPT'       => 'Select a category first',
                               'TS'           => mktime()
                               )
                          ); 
        
        $catObj = new Category($this->dbi);
        $remArr = array();
        if(!strcmp($mode, 'modify'))
        {
           if (empty($cid))
           {
              $this->alert('SELECT_CONTACT');	
              exit;
           }
           $contactObj = new Contact($this->dbi, $cid);
           $modCatID = $contactObj->getColumnValue('CAT_ID');
           $modFirst = $contactObj->getColumnValue('CONTACT_FIRST');
           $modLast  = $contactObj->getColumnValue('CONTACT_LAST');
           $modInitial = $contactObj->getColumnValue('CONTACT_INITIAL');
           $modEmail = strtok(($contactObj->getColumnValue('EMAIL')), ",");
           $modEmailExt = substr(strstr($contactObj->getColumnValue('EMAIL'), ','),1);
           $modPhone = strtok(($contactObj->getColumnValue('PHONE')), ",");
           $modPhoneExt = substr(strstr($contactObj->getColumnValue('PHONE'), ','),1);
           $modFax = strtok(($contactObj->getColumnValue('FAX')), ",");
           $modFaxExt = substr(strstr($contactObj->getColumnValue('FAX'), ','),1);
           $modURL = $contactObj->getColumnValue('URL');
           $modCompanyName = $contactObj->getColumnValue('COMPANY_NAME');
           $modCompanyAddr = $contactObj->getColumnValue('COMPANY_ADDRESS');
           $modHomeAddr = $contactObj->getColumnValue('HOME_ADDRESS');
           $modSource = $contactObj->getColumnValue('SOURCE');
           $modReference = $contactObj->getColumnValue('REFERENCE');
           $keywordArr = $contactObj->getKeywords($cid);
           $modKeywords = empty($keywordArr) ? null : implode(',', $keywordArr);
           $remArr = $contactObj->getReminders($cid);
           $template->set_var('CID', $cid);
        }
        if ($cat <=0 && !empty($cid))
        {
           $cat = $catObj->getParentOf($modCatID);	
        }
        
        $cats = $catObj->getParentCategories();
        if (!empty($cats))
        {
           while (list($catID, $cat_name) = each($cats))
           {
              $template->set_var(array
                                      (
                                       'CAT_ID'     => $catID,
                                       'CAT_NAME'   => $cat_name,
                                       'CAT_CHOSEN' => ($catID == $cat) ? 'selected' : null
                                      )                                    
                                );    
              $template->parse('cat', 'catBlock', true);
           }
        }
        else
        {
           $template->set_var('cat', null);	
        }   
        if ($cat > 0)
        {
           $template->set_var('PROMPT', 'Select a subcategory');
           $subCats = $catObj->getSubCategories($cat);
           if (!empty($subCats))
           {
              while (list($subCatID, $subCatName) = each($subCats))
              {
                 $template->set_var(array( 
                                          'SUBCAT_ID'     =>  $subCatID,
                                          'SUBCAT_NAME'   =>  $subCatName,
                                          'SUBCAT_CHOSEN' =>  ((isset($subcat) && $subCatID == $subcat) 
                                                                || ( isset($modCatID) && $subCatID == $modCatID)) 
                                                                   ? 'selected' : null
                                         )
                                    );
                 $template->parse('subcat', 'subcatBlock', true);
              }
           }
           else
           {
              $template->set_var('subcat', null);
           }           
        }
        else
        {
           $template->set_var('subcat', null);
        }
        
        $template->set_var(array(   
                                 'FIRST'        => isset($modFirst) ? $modFirst : null,
                                 'INITIAL'      => isset($modInitial) ? $modInitial : null,
                                 'LAST'         => isset($modLast) ? $modLast : null,
                                 'COMPANY_NAME' => isset($modCompanyName) ? $modCompanyName : null,
                                 'HOME_ADDR'    => isset($modHomeAddr) ? $modHomeAddr : null,
                                 'COMPANY_ADDR' => isset($modCompanyAddr) ? $modCompanyAddr : null,
                                 'EMAIL'        => isset($modEmail) ? $modEmail : null,
                                 'EMAIL_EXT'    => isset($modEmailExt) ? $modEmailExt : null,
                                 'PHONE'        => isset($modPhone) ? $modPhone : null,
                                 'PHONE_EXT'    => isset($modPhoneExt) ? $modPhoneExt : null,
                                 'FAX'          => isset($modFax) ? $modFax : null,
                                 'FAX_EXT'      => isset($modFaxExt) ? $modFaxExt : null,
                                 'REFERENCE'    => isset($modReference) ? $modReference : null,
                                 'SOURCE'       => isset($modSource) ? $modSource : null,
                                 'URL'          => isset($modURL) ? $modURL : null,
                                 'KEYWORD'      => isset($modKeywords) ? $modKeywords : null,
                                )
                          );
                          
        for($i=1;$i<=5;$i++)
        {
           $template->set_var('REM_ABOUT'.$i, empty($remArr[$i-1]) ? null : stripslashes($remArr[$i-1]->REMIND_ABOUT));
           $template->set_var('DATE'.$i, empty($remArr[$i-1]->REMIND_DATE) ? date("m/d/Y",mktime()) : date("m/d/Y", $remArr[$i-1]->REMIND_DATE));
           $template->set_var('CHOSEN'.$i, empty($remArr[$i-1]) ? 'disabled' : null);
        }
        $this->showContents($template->parse('mblock', 'mainBlock'));
     }
     
     function searchDriver()
     {
        $step = $this->getRequestField('step');
        if ($step == 1 || empty($step))
        {
           $this->displaySearchMenu();	
        }
        else if ($step == 2)
        {
           $this->displaySearchResult();	
        }
     }
     
     function displaySearchResult()
     {
     	$whereCondition = NULL;
     	$keyword_exists = FALSE;
     	$cat = $this->getRequestField('cat');
     	$subcat = $this->getRequestField('subcat');
     	$companyName = $this->getRequestField('companyName');
     	$contactName = $this->getRequestField('contactName');
     	$keyword = $this->getRequestField('keyword');
     	
     	$template = new Template($this->getTemplateDir());
     	$template->set_file('fh', CONTACT_SEARCH_RESULT_TEMPLATE);
     	$template->set_block('fh', 'mainBlock', 'main');
     	$template->set_block('mainBlock', 'contactBlock', 'contact');
     	$catObj = new Category($this->dbi);
     	$contactObj = new Contact($this->dbi);
     	
     	if ($cat > 0)
     	{
     	   if($subcat <= 0)	
     	   {
     	      $subcats = $catObj->getSubCategories($cat);
     	      if (!empty($subcats))
     	      {
     	         $whereCondition .= "(";
     	         while(list($subCatID, $subCatName) = each($subcats))
     	         {
     	            $whereCondition .= (empty($whereCondition ) || !strcmp($whereCondition,'(') ? "" : " OR") . " CAT_ID = $subCatID"; 	
     	         }
     	         $whereCondition .= ")";
     	      }
     	      else
     	      {
     	         $whereCondition = "CAT_ID = ".$cat;	
     	      }
     	   }
     	   else
     	   {
     	   	$whereCondition .= (empty($whereCondition ) ? "" : " OR") . " CAT_ID = $subcat";
     	   }
     	}
     	if (!empty($companyName))
     	{
     	   $companyName = $this->dbi->quote('%'.addslashes($companyName.'%'));
     	   $whereCondition .= (empty($whereCondition ) ? "" : " AND") . " COMPANY_NAME LIKE $companyName";	
     	}
     	if (!empty($contactName))
     	{
     	   /*
     	   $breakDowns = explode(' ', $contactName);     	   
     	   $first = $this->dbi->quote(addslashes($breakDowns[0]));
     	   $initial = (sizeof($breakDowns) == 2) ? "''" : $this->dbi->quote(addslashes($breakDowns[1]));
     	   $last = (sizeof($breakDowns) > 2) ? $this->dbi->quote(addslashes($breakDowns[2])) : $this->dbi->quote(addslashes($breakDowns[1]));
     	   */
     	   $contactName = $this->dbi->quote(addslashes('%'.$contactName.'%'));
     	   
     	   $whereCondition .= (empty($whereCondition) ? "" : " AND") . " (CONTACT_FIRST LIKE $contactName OR CONTACT_LAST LIKE $contactName OR CONTACT_INITIAL LIKE $contactName)";	
     	}
     	if (!empty($keyword))
     	{
     	   $keyword_exists = true;
     	   $keys = explode(',', $keyword);
     	   $whereCondition .= !empty($whereCondition) ? " AND (" : "(";
     	   foreach($keys as $key)
     	   {
     	      $key = $this->dbi->quote(addslashes($key));
     	      $whereCondition .= (empty($whereCondition) || !strcmp(substr($whereCondition,-1),'(') ? "" : " OR") . " KEYWORD LIKE $key"; 	
     	   }
     	   $whereCondition .= ")";
     	}
     	$contacts = $contactObj->searchContact($whereCondition, $keyword_exists);
     	if (!empty($contacts))
     	{
     	   foreach($contacts as $info)
     	   {
     	      $subCatName = stripslashes($catObj->getCategoryName($info->CAT_ID));
     	      $catName = stripslashes($catObj->getCategoryName($catObj->getParentOf($info->CAT_ID)));
     	      $template->set_var('COMPANY_NAME', stripslashes($info->COMPANY_NAME));
     	      $template->set_var('CONTACT_NAME', stripslashes($info->CONTACT_FIRST).' '.stripslashes($info->CONTACT_INITIAL).' '.stripslashes($info->CONTACT_LAST));     	   
     	      $template->set_var('SUBCATEGORY', $subCatName);
     	      $template->set_var('CATEGORY', $catName);
     	      $template->set_var('CONTACT_ID', $info->CONTACT_ID);
     	      $template->parse('contact', 'contactBlock', true);	
     	   }
     	   global $REL_APP_PATH;
     	   $template->set_var('CONTACT_MNGR', $REL_APP_PATH.'/'.CONTACT_MNGR);
     	   $this->showContents($template->parse('mblock', 'mainBlock'));
     	}
     	else
     	{
     	   $this->showContents($this->getMessage('SEARCH_NOT_FOUND'));
     	}   
     	
     }
     
     function displaySearchMenu()
     {
        $cat = $this->getRequestField('cat');
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', CONTACT_SEARCH_INPUT_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'catBlock', 'cat');
        $template->set_block('mainBlock', 'subcatBlock', 'subcat');
        $template->set_var('PROMPT', 'Select a category first');
        
        $catObj = new Category($this->dbi);        
        $cats = $catObj->getParentCategories();
        
        if (!empty($cats))
        {
           while (list($catID, $cat_name) = each($cats))
           {
              $template->set_var(array
                                      (
                                       'CAT_ID'     => $catID,
                                       'CAT_NAME'   => $cat_name,
                                       'CAT_CHOSEN' => ($catID == $cat) ? 'selected' : null
                                      )                                    
                                );    
              $template->parse('cat', 'catBlock', true);
           }
        }
        else
        {
           $template->set_var('cat', null);	
        }   
        
        if ($cat > 0)
        {           
           $template->set_var('PROMPT', 'Select a subcategory');
           $template->set_var('CONT_PROMPT', 'Select a subcategory first');
           $subCats = $catObj->getSubCategories($cat);
           if (!empty($subCats))
           {
              while (list($subCatID, $subCatName) = each($subCats))
              {
                 $template->set_var(array( 
                                          'SUBCAT_ID'     =>  $subCatID,
                                          'SUBCAT_NAME'   =>  $subCatName,
                                          'SUBCAT_CHOSEN' =>  (isset($subcat) && $subCatID == $subcat) ? 'selected' : null
                                         )
                                    );                 
                 $template->parse('subcat', 'subcatBlock', true);
              }
           }
           else
           {
              $template->set_var('subcat', null);
           }           
        }
        else
        {
           $template->set_var('subcat', null);
        }
        
        global $REL_APP_PATH;
        $template->set_var('CONTACT_MNGR', $REL_APP_PATH.'/'.CONTACT_MNGR);
        
        
        
        $this->showContents($template->parse('mblock', 'mainBlock'));
     }
     
     
      
     function showContents($contents)
     {
        global $THEME_TEMPLATE;
        global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
        
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
   
   $thisApp = new contactMngr(array
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
