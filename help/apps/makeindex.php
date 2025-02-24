<?php

   require_once "help.conf";
 
   class makeHelpIndexApp extends PHPApplication {

      function run()
      {
          $this->makeIndex();
      }

      function makeIndex()
      {
         $errors = array();
         global $APP_HELP_MAP;

         // See if user wants to generate index for specific application(s) or not
         // user can specify in QUERY_STRING app[]=self&app[]=foo or app[]=self type
         // of application selections

         $app = (! empty($_REQUEST['app'])) ? $_REQUEST['app'] : null;

         // Get the hash of app=>map for all or selected apps
         $mapHash = $this->getMapHash($app, $APP_HELP_MAP);

         while(list($app, $mapFile) = each($mapHash))
         {
            echo $this->getMessage('MAKEINDEX_HEADER', 
			           array( 'MAPFILE' => $mapFile, 'APP' => $app)
                                  );

            $helpObj = new Help(
                                 array( 'app' => $app,
                                        'map' => $mapFile,
                                        'force' => FALSE
                                      )
                               );

            // If map file cannot be loaded for this app, continue to next app
            if(! $helpObj->isLoaded())
            {
               echo $this->getMessage('MAKEINDEX_MAP_NOT_FOUND', 
			           array( 'MAPFILE' => $mapFile, 'APP' => $app)
                                  );
               continue;
            }

            // for each section of the application's help create keyword index
            foreach($helpObj->getSections() as $sectionNumber => $sectionName)
            {
               $success = $helpObj->makeKeywordIndex($sectionName, $sectionNumber);

               if ($success)
               {
                  echo $this->getMessage('MAKEINDEX_SECTION_DONE', 
			                 array( 'SECTION_NAME' => $sectionName, 
                                                'SECTION_NUMBER' => $sectionNumber));

               } else {

                  echo $this->getMessage('MAKEINDEX_SECTION_FAILED', 
			                 array( 'SECTION_NAME' => $sectionName, 
                                                'SECTION_NUMBER' => $sectionNumber));

                  array_push($errors, "Index could not be created for $app => $sectionName ($sectionNumber)");
               }
            }

         }

         return (empty($errors)) ? TRUE : FALSE;
      }

      /*
          Get a hash (app=>map) based on selected application
          If no app is selected return the hash from configuration.
          In latter case, we will index all apps. 
      */
      function getMapHash($appList = null, $hash = null)
      {
         if (!empty($appList))
         {
           // Remove duplicates from chosen application list
           if (count($appList) > 1) 
           {
               array_unique($appList);
           } else {
              $newHash[$appList] = $hash[$appList];
              return $newHash;
           }

           // For each chosen application find the map info and store in a new hash
           $newHash = array();
           foreach ($appList as $app)
           {
              if(! empty($hash[$app]))
              {
                  $newHash[$app] = $hash[$app];
              }
           }
 
           return $newHash;

         } else {
           // return all the apps in the APP_HELP_MAP Hash from configuration file
           return $hash;
         }
      }


      function authorize()
      {
          // This method is called automatically by application
          // when app_auto_authorize'=> TRUE when application object
          // is created.
          //
          // Use ACL_ALLOW_FROM and ACL_DENY_FROM

          $currentIP = $_SERVER['REMOTE_ADDR'];

          $aclObj = new ACL(array(
                                  'current_ip' => $currentIP,
                                  'allow_from' => ACL_ALLOW_FROM,
                                  'deny_from'  => ACL_DENY_FROM
                                 )
                            );

          return ($aclObj->isAllowed()) ? TRUE: FALSE;
      }

   }//class
   
   
   $thisApp = new makeHelpIndexApp(array
                                           ('app_name'             =>  $APPLICATION_NAME,
                                            'app_version'           => '1.0.0',
                                            'app_type'              => 'WEB',
                                            'app_auto_connect'      => FALSE,
                                            'app_auto_authorize'    => TRUE,
                                            'app_auto_chk_session'  => FALSE,
                                            'app_debugger'          => $OFF,
                                            'app_db_url'            => NULL,
                                           )
                                     );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
