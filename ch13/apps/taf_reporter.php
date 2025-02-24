<?php
   require_once "taf.conf";
   require_once ACCESS_CONTROL_CLASS;
   require_once FRM_CLASS;
   require_once MSG_CLASS;
   
   class tafReporter extends PHPApplication {

     function run()
     {

        $this->_cmd = $this->getRequestField('cmd');

        if (!strcmp($this->_cmd, 'frmCreator'))
        {           
           $this->generateFormCreatorReport();	
        }
        else
        {
           $this->generateOriginReport();	
        }
     }
     
     
     function generateFormCreatorReport()
     {
        $this->_fid = $_REQUEST['frmID'];            
        $acArr = array(
                       'ACCESS_OBJ'           =>          'FRM',
                       'ALLOW_TBL'            =>          TAF_FRM_OWNER_IP_TBL,
                       'DENY_TBL'             =>          null,
                       'REQUEST_IP'           =>          $_SERVER['REMOTE_ADDR'],
                       'AC_OBJ_ID'            =>          $this->_fid
                      );
        $acObj = new AccessControl($this->dbi, $acArr);

        if (!$acObj->isAccessAllowed() || $acObj->isAccessDenied())
        {
           $this->alert('UNAUTHORIZED_ACCESS', "window.close()");
           return;	
        }
        
        
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', TAF_CREATOR_REPORT_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'origBlock', 'orig');
        $frmObj = new Form($this->dbi, $this->_fid);
        $frmInfo = $frmObj->getFormInfo();
        $template->set_var(array
                                (
                                 'REQ_IP'          =>  $_SERVER['REMOTE_ADDR'],
                                 'FRM_NAME'        =>  $frmInfo['FRM_NAME'],
                                 'ACTIVATE_DATE'   =>  date("M j, Y", $frmInfo['ACTIVATION_TS']),
                                 'TERMINATE_DATE'  =>  date("M j, Y", $frmInfo['TERMINATION_TS']),
                                 'NUM_FRNDS'       =>  $total = count($frmObj->getFriendList()),
                                 'NUM_SUBSCR'      =>  $sub = $frmObj->getNumberOfSubscriber(),
                                 'NUM_UNSUB'       =>  $unsub = $frmObj->getNumberOfUnsubscriber(),
                                 'NUM_NORESP'      =>  $total - ($sub + $unsub),
                                 'AVG_SUBSCR'      =>  $total ? ($sub/$total) * 100 : 0
                                )
                          );
        
        $origins = $frmObj->getOriginSubmissions();               
        if (!empty($origins))
        {
            $rnk = 1;
            while ((list($orig, $num_sub) = each($origins)) && $rnk <= TOP)
            {
               $template->set_var('RANK', $rnk++);   	
               $template->set_var('ORIGIN', $orig);   	
               $template->set_var('SUBMTN', $num_sub);
               $template->set_var('SUBSCR', $frmObj->getNumSubscriptionPerOrigin($orig, $this->_fid));
               $template->parse('orig', 'origBlock', true);
            }
        }
        else
        {
            $template->set_var('RANK', null);   	
            $template->set_var('ORIGIN', null);   	
            $template->set_var('SUBMTN', 'No user to show');
            $template->set_var('SUBSCR', null);
            $template->parse('orig', 'origBlock', false);
        }    
        
        $template->parse('main', 'mainBlock', false);
        $template->pparse('output', 'fh');
     }
     
     function generateOriginReport()
     {
        $this->_fid = $_REQUEST['fid'];
        $this->_origin = $_REQUEST['orig'];           
        list($chkFID, $chkOrig) = explode(":", base64_decode($_REQUEST['cf']));           
        if (($this->_fid != $chkFID) || (strcmp($this->_origin, $chkOrig)))
        {
           $this->alert('UNAUTHORIZED_ACCESS', "window.close()");
           return;
        }
        
        $template = new Template($this->getTemplateDir());
        $template->set_file('fh', TAF_ORIGIN_REPORT_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'main');
        $template->set_block('mainBlock', 'frndBlock', 'frnd');
        
        $template->set_var('ORIGIN', $this->_origin);
        
        $frmObj = new Form($this->dbi, $this->_fid);
        $frmInfo = $frmObj->getFormInfo();
        
        $frnds = $frmObj->getFriendsByOrigin($this->_origin);
        if (!empty($frnds))
        {
           $subMtn = $subScr = 0;
           while(list($email, $name) = each($frnds))
           {
              $subStat = $frmObj->getSubscriptionStatus($email);
              $subMtn += $frmInfo['SCORE_PER_FRIEND_SUBMISSION']; 
              $subScr += ($subStat == 1) ? $frmInfo['SCORE_PER_FRIEND_SUBSCRIPTION'] : 0;
              $template->set_var('EMAIL', $email);	
              $template->set_var('NAME', $name);
              $template->set_var('SUBMTN_SCORE', $frmInfo['SCORE_PER_FRIEND_SUBMISSION']);
              $template->set_var('SUBSCR_SCORE', ($subStat == 1) ? $frmInfo[SCORE_PER_FRIEND_SUBSCRIPTION] : 0);
              $template->set_var('REJ', ($subStat == -1) ? 'Yes' : 'No');
              $template->set_var('NORESP', !empty($subStat) ? 'Yes' : 'No');
              $template->parse('frnd', 'frndBlock', true);
           }
        }
        else
        {
           $template->set_var('EMAIL', null);	
           $template->set_var('NAME', null);
           $template->set_var('SUBMTN_SCORE', null);
           $template->set_var('SUBSCR_SCORE', null);
           $template->set_var('REJ', null);
           $template->set_var('NORESP', 'No Friend submitted so far');
           $template->parse('frnd', 'frndBlock', false);
        }
        $template->set_var('TOTAL_SUBMTN', $subMtn);
        $template->set_var('TOTAL_SUBSCR', $subScr);
        $template->set_var('TOTAL', ($subScr + $subMtn));
        $template->parse('main', 'mainBlock', false);
        $template->pparse('output', 'fh');
     }
     
     
     
   }//class
   
   $thisApp = new tafReporter(array('app_name'               => $APPLICATION_NAME,
                                     'app_version'           => '1.0.0',
                                     'app_type'              => 'WEB',
                                     'app_auto_connect'      => TRUE,
                                     'app_auto_authorize'    => FALSE,
                                     'app_auto_chk_session'  => FALSE,
                                     'app_debugger'          => $OFF,
                                     'app_db_url'            => $TAF_DB_URL,
                               )
                     );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
