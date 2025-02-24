<?php

   require_once "irm.conf";
   
   class irmResourceTrackMngr extends PHPApplication {

      function run()
      {
         global $SESSION_USER_ID;       
          
         $this->uid = $SESSION_USER_ID;
       
         $this->keepTrack();
      }

      
      function keepTrack()
      {
          $rid = ($this->getRequestField('rid'));
          
          $now = mktime();
          
          $params = array(  'RESOURCE_ID'  => $rid,
                            'VISITOR_ID'   => $this->uid,
                            'VISIT_TS'    => $now,
                         );
                 
         $resourceObj = new IrmResource($this->dbi);
         
         $status = $resourceObj->trackResourceVisit($params);
        
         if($status)
         {
            $resourceUrl = $resourceObj->getResourceUrl($rid);
           
            if(!empty($resourceUrl))
            {
               header("Location:".$resourceUrl); 
            }
         }
      
      }
      

      function authorize()
      {
         return true;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;

   $thisApp = new irmResourceTrackMngr(
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
