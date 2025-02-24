<?php     

   require_once "ecampaign.conf";
   require_once $ECAMPAIGN_LIST_CLASS;

   /* Session variables must be defined before session_start()
      method is called */

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   class ecampaignListMngr extends PHPApplication {

      function run()
      {
          global $ECAMPAIGN_DB_URL;
          
          $cmd = $this->getRequestField('cmd');
          
          $cmd = strtolower($cmd);

          if (empty($cmd) || !strcmp($cmd, 'add'))
          {
              $this->addDriver();

          } else if(!strcmp($cmd, 'modify')) {

                $this->modifyDriver();

          } else {

             $this->delList();
          }
     }

     function addDriver()
     {
          $step = $this->getRequestField('step');

          if (empty($step))
          {
             $this->displayAddListMenu();
          } else if ($step == 2){
             $this->addList();
          } else if ($step == 3){
             $this->addDatabaseFieldMap();
          }
      }

      function modifyDriver()
      {
          $step = $this->getRequestField('step');

          if (empty($step))
          {
             $this->displayModifyListMenu();
          } else if ($step == 2){
             $this->modifyList();
          } else if ($step == 3){
             $this->modifyDatabaseFieldMap();
          }
      }


      function authorize()
      {
          return TRUE;
      }


      function displayAddListMenu()
      {
          global $ECAMPAIGN_ADD_LIST_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $REL_APP_PATH,
                 $ECAMPAIGN_MNGR,
                 $ECAMPAIGN_LIST_MNGR;



          $baseURL  = sprintf("%s%s",$this->server, $REL_TEMPLATE_DIR);
          $today    = mktime();
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_ADD_LIST_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          $template->set_var('ECAMPAIGN_LIST_MNGR', $ECAMPAIGN_LIST_MNGR);
          $template->set_var('ECAMPAIGN_MNGR', $ECAMPAIGN_MNGR);
          $template->set_var('APP_PATH', $REL_APP_PATH);
          $template->set_var('TODAY', $today);
          $template->set_var('BASE_URL', $baseURL);

          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');

       }


       function displayModifyListMenu()
       {
           global $ECAMPAIGN_MOD_LIST_TEMPLATE,
                  $REL_TEMPLATE_DIR,
                  $REL_APP_PATH,
                  $ECAMPAIGN_MNGR,                  
                  $ECAMPAIGN_LIST_MNGR;
                  
           $list_id = $this->getRequestField('list_id');       

           if (empty($list_id))
           {
                $this->alert('LIST_NO_LIST_CHOSEN');
           }

           $listObject = new EcampaignList($this->dbi);
           $result = $listObject->getEcampaignListInfo($list_id);

           $baseURL = sprintf("%s%s",$this->server, $REL_TEMPLATE_DIR);

           $today    = mktime();
           $template = new Template($this->getTemplateDir());
           $template->set_file('fh', $ECAMPAIGN_MOD_LIST_TEMPLATE);
           $template->set_block('fh','mainBlock', 'main');
           $template->set_var(array
                                   (
                                    'ECAMPAIGN_LIST_MNGR'  =>  $ECAMPAIGN_LIST_MNGR,
                                    'ECAMPAIGN_MNGR'       => $ECAMPAIGN_MNGR,
                                    'APP_PATH'             => $REL_APP_PATH,
                                    'TODAY'                => $today,
                                    'LIST_ID'              => $result->LIST_ID,
                                    'NAME'                 => $result->NAME,
                                    'DB_NAME'              => $result->DB_NAME,
                                    'DB_HOST'              => $result->DB_HOST,
                                    'DB_USER'              => $result->DB_USER,
                                    'TODAY'                => $result->NAME,
                                    'PASSWORD'             => $result->DB_PASSWD,
                                    'TYPE'                 => $result->DB_TYPE,
                                    'TB_NAME'              => $result->DB_TABLE,
                                    'BASE_URL'             => $baseURL
                                   )
                              );

           $template->parse('main','mainBlock', false);
           $template->pparse('output', 'fh');
       }


       function modifyList()
       {
                global $ECAMPAIGN_MNGR;
                
                $step = $this->getRequestField('step');
                $list_id = $this->getRequestField('list_id');
                $name = $this->getRequestField('name');
                $db_name = $this->getRequestField('db_name');
                $db_host = $this->getRequestField('db_host');
                $db_user = $this->getRequestField('db_user');
                $db_pass = $this->getRequestField('db_pass');
                $db_type = $this->getRequestField('db_type');
                $db_table = $this->getRequestField('db_table');

                if (empty($list_id) || empty($name) || empty($db_name) || empty($db_host) || empty($db_user) || empty($db_type) || empty($db_table))
                {
                  $this->alert('MOD_ECAMPAIGN_LIST_REQ_MISSING');
                }
           
                $this->takeMap($list_id, $name, $db_host, $db_user, $db_pass, $db_type, $db_name, $db_table, NULL, 'mod');
               
       }


       function modifyDatabaseFieldMap()
       {
          global $ECAMPAIGN_MNGR; 
          $step = $this->getRequestField('step');
          $list_id = $this->getRequestField('list_id');
          $custid = $this->getRequestField('custid');
          $custfname = $this->getRequestField('custfname');
          $custlname = $this->getRequestField('custlname');
          $custeaddr = $this->getRequestField('custeaddr');
          $custage = $this->getRequestField('custage');
          $custincome = $this->getRequestField('custincome');
          $custsex = $this->getRequestField('custsex');

          
          $EcampaignListObj=new EcampaignList($this->dbi);
          
          $params = array('LIST_ID' => $list_id,
                          'REC_ID'  => $custid,
                          'FIRST'   => $custfname,
                          'LAST'    => $custlname,
                          'EMAIL'   => $custeaddr,
                          'AGE'     => $custage,
                          'INCOME'  => $custincome,
                          'SEX'     => $custsex
			);
	
	  $status = $EcampaignListObj->modifyMapList($params);
	
	  if ($status)
          {
              $this->show_status($this->getMessage('LIST_MODIFIED_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);
          }
          else {

              $this->show_status($this->getMessage('LIST_MODIFIED_FAILED'),
                                 $ECAMPAIGN_MNGR);
          }		
       }

       
       function delList()
       {
           global $ECAMPAIGN_MNGR;
           $list_id = $this->getRequestField('list_id');

           if (empty($list_id))
           {
               $this->alert('LIST_NO_LIST_CHOSEN');
           }

           $listObject = new EcampaignList($this->dbi, $list_id);

           $status = $listObject->deleteList();

           if ($status)
           {
              $this->show_status($this->getMessage('LIST_DELETE_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);

           } else {

              $this->show_status($this->getMessage('LIST_DELETE_FAILED'),
                                 $ECAMPAIGN_MNGR);
           }

       }

       function takeMap($listid, $listname, $db_host, $db_user, $db_pass, $db_type, $db_name, $db_table, $list_id, $mode)
       {

          global $ECAMPAIGN_MAPPING_TEMPLATE, $TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $ECAMPAIGN_MNGR, $REL_APP_PATH, $ECAMPAIGN_DB_URL, $ECAMPAIGN_LIST_MNGR;
          global $TABLE_DOES_NOT_EXIST;
          $cmd = $this->getRequestField('cmd');

          $baseURL = sprintf("%s%s",$this->server, $REL_TEMPLATE_DIR);


          $clientDBURL = strtolower($db_type). '://' .$db_user. ':' . $db_pass . '@' .$db_host .'/'. $db_name;


          $dbiObj = new DBI($clientDBURL);

          if(!$dbiObj->connected)
          {
             $this->debug("Not connected to $clientDBURL");
             $listObj = new EcampaignList($this->dbi);
             $listObj->deleteList($list_id);
             $this->show_status($this->getMessage('CONNECT_DATABASE_FALIED'),
                                    $ECAMPAIGN_MNGR);
             return;

          }

          $result = $dbiObj->query("SELECT * FROM $db_table");

          if ($dbiObj->isError() && $dbiObj->isErrorType($TABLE_DOES_NOT_EXIST))
          {

             $listObj = new EcampaignList($this->dbi);
             $listObj->deleteList($list_id);

             $this->show_status($this->getMessage('TABLE_DOES_NOT_EXIST'),
                                    $ECAMPAIGN_MNGR);
             return;

          } else {

             $tableInfoArr = $result->tableInfo();
          }

          
          $today = time();
          $ecampaignListObj = new EcampaignList($this->dbi);
          if (!strcmp($mode,'add'))
          {
          	$result = $ecampaignListObj->addNewEcampaignList($listname,
                                                              $db_host,
                                                              $db_user,
                                                              $db_pass,
                                                              $db_type,
                                                              $db_name,
                                                              $db_table,
                                                              $today,
                                                              $this->getUID()
                                                             );
             
             if (!$result)
             {
                $this->show_status($this->getMessage('LIST_UPLOAD_FAILED'), $ECAMPAIGN_MNGR);
                return;
             }                                                             
             $listid = $result;
          }
          else if (!strcmp($mode, 'mod'))
          {
          	
            
            $result = $ecampaignListObj->modEcampaignList($listid,
                                                          $listname,
                                                          $db_name,
                                                          $db_host,
                                                          $db_user,
                                                          $db_pass,
                                                          $db_type,
                                                          $db_table
                                                         );
             if (!$result)
             {
                $this->show_status($this->getMessage('LIST_UPLOAD_FAILED'), $ECAMPAIGN_MNGR);
                return;
             }        
          }
          
          
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh', $ECAMPAIGN_MAPPING_TEMPLATE);
          $template->set_block('fh','mainBlock', 'main');
          
          
          $listObj = new EcampaignList($this->dbi, $listid);
          
          $fieldArr = array('REC_ID', 'LAST','FIRST','EMAIL','AGE','INCOME','SEX');
          
          
          while (list($key, $value) = each($fieldArr))
          {
              $template->set_block('mainBlock', strtolower($value).'Block', substr(strtolower($value), 0, 2).'block');
              
              
              foreach ($tableInfoArr as $fieldinfo)
              {
                  $template->set_var('FIELD_NAME', $fieldinfo['name']);
                  if (!strcmp($listObj->map($value), $fieldinfo['name'])) 
                  { 
                  	$template->set_var('CHOSEN'.($key+1), 'SELECTED'); 
                  }
                  else
                  {
                  	$template->set_var('CHOSEN'.($key+1), ''); 
                  }
                  $template->parse(substr(strtolower($value), 0, 2).'block', strtolower($value).'Block', true);
                  
              }
          
          }


          $template->set_var(array
                                  (
                                   'ECAMPAIGN_LIST_MNGR'  => $ECAMPAIGN_LIST_MNGR,
                                   'ECAMPAIGN_MNGR'       => $ECAMPAIGN_MNGR,
                                   'APP_PATH'             => $REL_APP_PATH,
                                   'TODAY'                => $today,
                                   'LIST_ID'              => $listid,
                                   'BASE_URL'             => $baseURL,
                                   'MAP_CMD'              => ucfirst($cmd)
                                  )
                             );
                                 
          $template->parse('main','mainBlock', false);
          $template->pparse('output', 'fh');
       }


       function addList()
       {
          
          global $ECAMPAIGN_MNGR;
          
          $listname = $this->getRequestField('listname');
          $db_host = $this->getRequestField('db_host');
          $db_user = $this->getRequestField('db_user');
          $db_pass = $this->getRequestField('db_pass');
          $db_type = $this->getRequestField('db_type');
          $db_table = $this->getRequestField('db_table');
          $db_name = $this->getRequestField('db_name');
          $today = $this->getRequestField('today');
          $step = $this->getRequestField('step');

          if (empty($listname) || empty($db_host) || empty($db_user) || empty($db_type) || empty($db_table))
          {
              $this->alert('ADD_ECAMPAIGN_LIST_REQ_MISSING');
          }

          else
          {

             
             //if ($result)
             //{
               $this->takeMap(null ,$listname, $db_host, $db_user, $db_pass, $db_type, $db_name, $db_table, NULL, 'add');
             //} else{
             //  $this->show_status($this->getMessage('LIST_UPLOAD_FAILED'), $ECAMPAIGN_MNGR);
             //}
          }
       }


       function addDatabaseFieldMap()
       {
           global $ECAMPAIGN_MNGR;
           $custid = $this->getRequestField('custid');
           $custlname = $this->getRequestField('custlname');
           $custfname = $this->getRequestField('custfname');
           $custeaddr = $this->getRequestField('custeaddr');
           $custage = $this->getRequestField('custage');
           $custincome = $this->getRequestField('custincome');
           $custsex = $this->getRequestField('custsex');
           $list_id = $this->getRequestField('list_id');

           $listObj = new EcampaignList($this->dbi);
           $result = $listObj->addMapping($list_id,
                                          $custid,
                                          $custfname,
                                          $custlname,
                                          $custeaddr,
                                          $custage,
                                          $custincome,
                                          $custsex
                                         );

           if ($result)
           {
              $this->show_status($this->getMessage('LIST_UPLOAD_SUCCESSFUL'),
                                 $ECAMPAIGN_MNGR);
           }else{
            
              $this->show_status($this->getMessage('LIST_UPLOAD_FAILED'),
                                 $ECAMPAIGN_MNGR);
           }
       }


   }//class

   global $ECAMPAIGN_DB_URL;

   $thisApp = new ecampaignListMngr(
                                    array( 'app_name'     => $APPLICATION_NAME,
                                           'app_version'  => '1.0.0',
                                           'app_type'     => 'WEB',
                                           'app_db_url'   => $ECAMPAIGN_DB_URL,
                                           'app_debugger' => $OFF,
                                           'app_auto_connect' => TRUE,
                                           'app_auto_chk_session' => TRUE,
                                           'app_auto_authorize' => FALSE
                                         )
                                   );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
