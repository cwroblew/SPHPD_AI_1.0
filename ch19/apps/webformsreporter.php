<?php
   
   require_once "webforms.conf";
   require_once $FORMDATA_CLASS;
   //require_once 'Cache/Function.php';
   

   class webFormsReporter extends PHPApplication {

      function run()
      { 
          $frmID = $this->getRequestField('frmID');
          $sort = $this->getRequestField('sort');          
          $toggle = $this->getRequestField('toggle');
          $startTS = mktime(0,0,0, $this->getRequestField('startMon'), $this->getRequestField('startDay'), $this->getRequestField('startYr'));
          $startTS = ($startTS < 0) ? mktime(0,0,0,date('n'),date('j'),date('Y')) : $startTS;          
          $endTS = mktime(23,59,59, $this->getRequestField('endMon'), $this->getRequestField('endDay'), $this->getRequestField('endYr'));
          $endTS = ($endTS < 0) ? mktime(23,59,59,date('n'),date('j'),date('Y')) : $endTS;
          $this->showReport($frmID, $startTS, $endTS, $sort, $toggle);
      }


      function showReport($fid, $start, $end, $sort, $toggle)
      {  
         $template = new Template($this->getTemplateDir());
         $template->set_file('fh', REPORT_TEMPLATE);

         $template->set_block('fh', 'mainBlock', 'main');
         $template->set_block('mainBlock', 'frmBlock', 'frm');
         $template->set_block('mainBlock', 'expoBlock', 'expo');

         $template->set_block('mainBlock', 'rowBlock', 'row');
         $template->set_block('mainBlock', 'colHdnBlock', 'colhdn');
         $template->set_block('rowBlock', 'colBlock', 'col');         
         
         $template->set_block('mainBlock', 'stmonBlock', 'stmon');
         $template->set_block('mainBlock', 'stdayBlock', 'stday');
         $template->set_block('mainBlock', 'styrBlock', 'styr');
         
         $template->set_block('mainBlock', 'enmonBlock', 'enmon');
         $template->set_block('mainBlock', 'endayBlock', 'enday');
         $template->set_block('mainBlock', 'enyrBlock', 'enyr');
         
         $template->parse('expo', 'expoBlock', false);

         if (!(in_array($fid, array_keys($GLOBALS['KNOWN_FORMS']))))
         {
            $msg = $this->getErrorMessage('FORM_NOT_SELECTED');	
            $template->set_var('expo', null);
         }
         else if (($start < 0) || ($end < 0) || ($start >= $end))
         {
            $msg = $this->getErrorMessage('RANGE_NOT_VALID');		
         }
         else
         {  
            $FormData = new FormData($this->dbi, $fid);
            //$cache = new Cache_Function();

            //$dataArr = $cache->call('FormData->getFormData', $start, $end, $sort, $toggle, $fid);
            $dataArr = $FormData->getFormData($start, $end, $sort, $toggle);
         }
         
         $frmArr = $GLOBALS['KNOWN_FORMS'];
         asort($frmArr);
         reset($frmArr);
         while(list($id, $name) = each($frmArr))
         {
            $template->set_var(array(
                                     'FRM_ID'   =>  $id,
                                     'FRM_NAME' =>  substr($name, 0, strpos($name,'.')),
                                     'FRM_SEL'  =>  ($id == $fid) ? 'selected' : null
                                    )
                              );
            $template->parse('frm', 'frmBlock', true);	
         }
         
         for ($i=1; $i<=12; $i++)
         {
            $template->set_var(array(
                                     'STMON'      => $i,
                                     'ENMON'      => $i,
                                     'STMON_LBL'  => date("M", mktime(0,0,0,$i,1,2002)),
                                     'ENMON_LBL'  => date("M", mktime(0,0,0,$i,1,2002)),
                                     'STMONSEL'   => (date('n', $start) == $i) ? 'selected' : null,
                                     'ENMONSEL'   => (date('n', $end) == $i) ? 'selected' : null
                                    )
                              );            
            $template->parse('stmon', 'stmonBlock', true);
            $template->parse('enmon', 'enmonBlock', true);
         }
         
         for ($i=1; $i<=31; $i++)
         {
            $template->set_var(array(
                                     'STDAY'    => $i,
                                     'ENDAY'    => $i,
                                     'STDAYSEL' => (date('j', $start) == $i) ? 'selected' : null,
                                     'ENDAYSEL' => (date('j', $end) == $i) ? 'selected' : null
                                    )  
                               );            
            $template->parse('stday', 'stdayBlock', true);
            $template->parse('enday', 'endayBlock', true);
         }
         
         for ($i=MIN_YEAR; $i<=MAX_YEAR; $i++)
         {
            $template->set_var(array(    
                                     'STYR'    => $i,
                                     'ENYR'    => $i,            
                                     'STYRSEL' => (date('Y', $start) == $i) ? 'selected' : null,
                                     'ENYRSEL' => (date('Y', $end) == $i) ? 'selected' : null
                                     )
                              );            
            $template->parse('styr', 'styrBlock', true);
            $template->parse('enyr', 'enyrBlock', true);
         }        
         
         if (!empty($dataArr))
         {
            $template->set_var('COLSPAN', $fldCnt = count($dataArr[0]));
            while(list($fieldName, $value) = each($dataArr[0]))
            {
               $template->set_var('COL_HDN', stripslashes($fieldName));
               $template->set_var('TDWIDTH', (100/$fldCnt));	
               $template->parse('colhdn', 'colHdnBlock', true);
            }
            reset($dataArr);
            foreach($dataArr as $row)
            {
               $template->set_var('col', null);
               $template->set_var('ROWCOLOR', (++$i%2) ? ODD_COLOR : EVEN_COLOR);
               while(list($fieldName, $value) = each($row))
               {
                   $template->set_var('COL_VAL', stripslashes($value));                    
                   $template->parse('col', 'colBlock', true);
               }
               $template->parse('row', 'rowBlock', true);
            }
         }
         else
         {
            if (!empty($msg))
            {
               $template->set_var('COL_VAL', $msg);   	
            }
            else
            {
               $template->set_var('COL_VAL', $this->getErrorMessage('DATASET_EMPTY'));   		
            }
            $template->set_var('COL_HDN', null);
            $template->set_var('ROWCOLOR', DEFAULT_COLOR);
            $template->parse('colhdn', 'colHdnBlock', false);
            $template->parse('col', 'colBlock', false);
            $template->parse('row', 'rowBlock', false);
         }
         
         $template->set_var(array(
                                  'REPORTER'  => REPORTER,
                                  'EXPORTER'  => CSV_EXPORTER,
                                  'FID'       => $fid,
                                  'TOGGLE'    => empty($toggle) ? 'desc' : null
                                 )
                           );         
         $template->parse('main', 'mainBlock', false);
         $template->pparse('output', 'fh');
      }
    
  


   }//class

   $thisApp = new webFormsReporter(
                                    array('app_name'              => APPLICATION_NAME,
                                          'app_version'           => '1.0.0',
                                          'app_type'              => 'WEB',
                                          'app_auto_connect'      => TRUE,
                                          'app_auto_authorize'    => FALSE,
                                          'app_auto_chk_session'  => FALSE,
                                          'app_debugger'          => $OFF,
                                          'app_db_url'            => FORM_DB_URL,
                                         )
                                    );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>