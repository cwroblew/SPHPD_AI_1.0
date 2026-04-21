<?php

/*
   Known Issues
   1. Internationalization

*/
   require_once "help.conf";
   
   class helpApp extends PHPApplication {

      function run()
      {
         $cmd = $this->getCommand();
 
         // If valid command then run it
         if ($cmd)
         {
            $this->$cmd();

         } else {

            $this->alert('INVALID_REQUEST');
         }
      }
 
      function getCommand()
      {

         // Determine which command is being requested. Default is showHelp

         $cmd = 'showHelp';

         $app = (! empty($_REQUEST['app'])) ? $_REQUEST['app'] : NULL;

         if (! $app)
         {
            return NULL;
         }

         if(! empty($_REQUEST['keyword']))
         {
              $cmd = 'doSearch';
         }

         
         return $cmd;
      }

      function showHelp()
      {
         $sections = array();

         $contents = NULL;
         $template = NULL;

         // Get the user supplied information about which application help
         // to display 
         $info =  $this->getAppInfo();

         /// Create help object for the chosen application
         $helpObj = new Help( array( 'app' => $info['app'], 'map' => $info['map'], 'force' => FALSE));

         // If a section is supplied, see if the section is a valid section of the
         // the chosen application
         if(!empty($info['section']) && $helpObj->isSection($info['section']))
         {
            $contents = $helpObj->getSectionContents($info['section']);

         // else we will show a TOC style page with links to all sections 
         } else {

            $contents = $helpObj->getTOCContents();
         }
         
         // Get section list
         $contents['section_list'] = $helpObj->getSectionNumberList();

         $this->displayOutput($contents);
      }

      function displayOutput($contents = null)
      {

         $template = new Template();
         $template->set_file('fh', $contents['template']);
         $template->set_block('fh', 'mainBlock', 'main');

         // Set BASE URL
         $template->set_var('base_url' , $this->getServer() . $contents['base_url']);
         $template->set_var('app' , $_REQUEST['app']);
         $template->set_var('TOC_LINK' , $contents['toc_link']);

         //Define search result specific blocks
         if (! strcmp($contents['output'], 'search_results'))
         {
             $template->set_block('mainBlock', 'historyBlock', 'history');

         // Define display section blocks
         } else if (! strcmp($contents['output'], 'show_section')) 
         {
             $template->set_var('TOC_LINK' , $contents['toc_link']);
             $template->set_block('mainBlock', 'prevBlock', 'previous');
             $template->set_block('mainBlock', 'nextBlock', 'next');
         }
   
         // If we have [section_links] then insert them
         if (! empty($contents['section_links']))
         {
             $template->set_block('mainBlock', 'sectionBlock', 'section');
             foreach($contents['section_links'] as $sectionNumber => $sectionLink)
             {
                $template->set_var('SECTION_LINK' , $sectionLink);
                $template->set_var('SECTION_NUMBER' , $sectionNumber);
                $template->set_var('SECTION_NAME' , $contents['sections'][$sectionNumber]);
                $template->parse('section', 'sectionBlock', true);
             }
         }


         // Display search history (if any)
         if (! empty($contents['recent_search']))
         {
             foreach($contents['recent_search'] as $historyKeywords => $historyLink)
             {
                $template->set_var('HISTORY_LINK' , $historyLink);
                $template->set_var('HISTORY_KEYWORD' , $historyKeywords);
                $template->parse('history', 'historyBlock', true);
             }
         } else {

             $template->set_var('history' , NULL);
         }

         // When displaying search result, insert match count info
         if (! empty($contents['match_count'])) 
         {
             $template->set_var('MATCH_COUNT' , $contents['match_count']);
         }

         if (! empty($contents['body'])) 
         {
             $template->set_var('BODY' , $contents['body']);

         } else {

             $template->set_var('BODY' , $this->getMessage('SECTION_CONTENTS_MISSING'));
         }


         if (! empty($contents['previous_section'])) 
         {
             $template->set_var('PREVIOUS_SECTION' , $contents['previous_section']);
             $template->parse('previous', 'prevBlock', false);

         } else {
             $template->set_var('previous' , NULL);
         }

         if (! empty($contents['next_section'])) 
         {
             $template->set_var('NEXT_SECTION' , $contents['next_section']);
             $template->parse('next', 'nextBlock', false);

         }  else {

             $template->set_var('next' , NULL);

         }

         $template->parse('main', 'mainBlock', false);

         $document = $template->parse('output', 'fh');

         // If the current page has embedded links to other sections we need to
         // make them work
         $search = array();
         $replacement = array();

         foreach($contents['section_list'] as $sectionNumber)
         {
             $link = '/' . $sectionNumber . '.html/';
             array_push($search,  $link);
             array_push($replacement,  $_SERVER['PHP_SELF'] . 
                                       '?app=' . $_REQUEST['app'] . 
                                       '&section=' . $sectionNumber);
         }

         $document = preg_replace($search, $replacement, $document);

         echo $document;

      }

      function getAppInfo()
      {
         global $APP_HELP_MAP;

         $info = array();
         $app = (! empty($_REQUEST['app']))   ? $_REQUEST['app'] : null;
         $section = (! empty($_REQUEST['section']))   ? $_REQUEST['section'] : null;

         $info['app'] = $app;
         $info['section'] = $section;
         $info['map'] = (! empty($APP_HELP_MAP[$app])) ? $APP_HELP_MAP[$app] : null;

         return $info;
         
      }

      function doSearch()
      {

         // Get the user supplied information into a hash
         $info =  $this->getAppInfo();

         // Create a help object and init it with user supplied app and map information
         $helpObj = new Help( array( 'app' => $info['app'], 'map' => $info['map'], 'force' => FALSE));

         // Get the user supplied keyword in lower case format
         $keyword = (!empty($_REQUEST['keyword'])) ? strtolower($_REQUEST['keyword']) : null;

         // Remove any escape slashes added by PHP
         $keyword = stripslashes($keyword);

         // If search results into matches show the matched section in a TOC style page
         if ($helpObj->search($keyword))
         {
            $keyword = $helpObj->getKeywordString();
            $contents = $helpObj->getSearchResults();

            // Get section list
            $contents['section_list'] = $helpObj->getSectionNumberList();
 
            $this->displayOutput($contents);
            return TRUE;

         } else {

            $this->alert('NO_MATCH_FOUND');
            return FALSE;
         }
          
      }

      function authorize()
      {
         return TRUE;
      }

   }//class
   
   $thisApp = new helpApp(array
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
