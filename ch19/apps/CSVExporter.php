<?php 

   require_once "webforms.conf";
   require_once $FORMDATA_CLASS;   

   class CSVExporter extends PHPApplication {

      function run()
      { 
      	 $fid = $this->getRequestField('fid');
      	 $mode = $this->getRequestField('mode');
         $this->processRequest($fid, $mode);
      }
      
      function dblquote($str)
      {
          return "\"" . $str . "\"";
      }
      
      function processRequest($fid, $mode)
      {
         if (!(in_array($fid, array_keys($GLOBALS['KNOWN_FORMS']))))
         {
            $this->alert('FORM_NOT_SELECTED');	
            return;
         }
         $frmData = new FormData($this->dbi, $fid);
         
         $frmName = substr($GLOBALS['KNOWN_FORMS'][$fid], 0, strpos($GLOBALS['KNOWN_FORMS'][$fid],'.'));
         if ($mode == DOWNLOAD_TYPE_LATEST)
         {
            $lastDLMaxRec = $frmData->getLastDLRecordID();
            $dataArr = $frmData->getDataAfterRecordID($lastDLMaxRec);            
            $fileName = $frmName.$lastDLMaxRec;
         }
         else
         {
            $dataArr = $frmData->getFormData();
            $fileName = $frmName;
         }
         
         if(!empty($dataArr))
         {
            $fp = fopen ($GLOBALS['ROOT_PATH'].$GLOBALS['REL_APP_PATH']."/temp/".$fileName.".csv", "w+");
            while(list($fieldName, $value) = each($dataArr[0]))
            {
               fwrite($fp, $this->dblquote(stripslashes($fieldName)).",");
            }
            reset($dataArr);
            foreach($dataArr as $row)
            {
               fwrite($fp, "\n");
               $lastID = $row->id;
               while(list($fieldName, $value) = each($row))
               {
               	  fwrite($fp, $this->dblquote(stripslashes($value)).",");
               }	
            }
            $frmData->updateDownloadTrack($lastID); 
            header("Location: temp/".$fileName.".csv");
         }  
         else
         {
            $this->alert('DATASET_EMPTY');	
         }
      }

   }//class

   $thisApp = new CSVExporter(
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