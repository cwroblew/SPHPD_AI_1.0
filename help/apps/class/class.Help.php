<?php

class Help {

    function Help($params = null)
    {
       $this->_APP = (! empty($params['app'])) ? $params['app'] : null;
       $this->_MAP = (! empty($params['map'])) ? $params['map'] : null;
       $this->_FORCE = (! empty($params['force'])) ? TRUE : FALSE;

       // List of logical keyword operators
       $this->_OPERATORS = array('and', 'or');

       $this->_LOADED = FALSE;

       // Load the map information for the chosen application
       if (! empty($this->_APP) && (! empty($this->_MAP)))
       {
          $this->_LOADED = $this->loadMap();
       }
    }

    function getApp()
    {
       return $this->_APP;
    }

    function getRelHelpDir()
    {
       // Get %DocumentRoot% relative help directory
       return $this->_REL_HELP_DIR;
    }

    function getSectionContents($section = null)
    {
       $contents = array();

       // Set content output source
       $contents['output'] = 'show_section';

       // Create the link for table of contents
       $tocLink = sprintf("%s?app=%s", $_SERVER['PHP_SELF'], $this->getApp());
       $contents['toc_link'] = $tocLink;

       // Create the link for previous section
       $prevSection = $this->getPreviousSection($section);

       if ($prevSection) 
       {
          $prevLink = (!empty($prevSection)) ? sprintf("%s&section=%s",$tocLink, $prevSection) : NULL;
          $contents['previous_section'] = $prevLink;
       } else {
          $contents['previous_section'] = NULL;
       }

       // Create the link to next section
       $nextSection = $this->getNextSection($section);

       if ($nextSection) 
       {
           $nextLink = (!empty($nextSection)) ? sprintf("%s&section=%s",$tocLink, $nextSection) : NULL;
           $contents['next_section'] = $nextLink;
       } else {
           $contents['next_section'] = NULL;
       }
       
       // Load page contents;
       $contents['body'] = $this->_loadFile($this->getFQPNofSection($section));

       // Search for src="images  background="images and replace with 
       // relative path to the help directory
       $search  = array(
                         '/src="images/', 
                         '/src=images/', 
                         '/background="images/',
                         '/background=images/'
                       );

       $replace = array(
			 '/src="' . $this->getRelHelpDir() . '/images',
			 '/src=' . $this->getRelHelpDir() . '/images',
			 '/background="' . $this->getRelHelpDir() . '/images',
			 '/background=' . $this->getRelHelpDir() . '/images'
                       );

       $contents['body'] = trim(preg_replace($search,$replace,$contents['body']));

       // Get section page template
       $contents['template'] = $this->getSectionTemplate($section);

       // Set BASE URL path
       $contents['base_url'] = $this->getBaseURL($contents['template']);

       return $contents;

    }

    function getPreviousSection($section = null)
    {
        $totalSections = count(array_keys($this->_SECTIONS));

        $thisSectionIndex = $this->_indexOfSection($section);

         if ($thisSectionIndex > 0)
         {
              return $this->getSectionAtIndex($thisSectionIndex-1);
         }

         return NULL;
    }


    function getNextSection($section = null)
    {
        $totalSections = count(array_keys($this->_SECTIONS));

        $thisSectionIndex = $this->_indexOfSection($section);

         if ($thisSectionIndex < $totalSections)
         {
              return $this->getSectionAtIndex($thisSectionIndex+1);
         }

         return NULL;
    }

    function getSectionAtIndex($index = null)
    {
       
        $list = array_keys($this->_SECTIONS);
        return (($index >=0 && $index <= count($list))) ? $list[$index] : NULL;

    }

    function _indexOfSection($section = null)
    {
        $list = array_keys($this->_SECTIONS);

        $index = null;

        for($i=0; $i<count($list); $i++)
        {
           if (!strcmp($list[$i], $section)) {
               $index = $i;
               break;
           }
        }

        return $index;
    }
   
    function getTOCContents()
    {

       $contents = array();

       // Set content output source
       $contents['output'] = 'show_toc';

       $sections=$this->getSectionHash();

       if (empty($sections))
       {
          return $contents;
       }

       $contents['sections'] =  $sections;

       // Create the link for table of contents
       $tocLink = sprintf("%s?app=%s", $_SERVER['PHP_SELF'], $this->getApp());

       // create a link to the help application for each section
       $sectionLinks = array();

       foreach($sections as $id => $name)
       {
         $sectionLinks[$id] = sprintf("%s&section=%s", $tocLink, $id);
       }

       // Store the section link array in the content hash
       $contents['section_links'] = $sectionLinks;

       // Get section page template
       $contents['template'] = $this->getTOCTempalte();

       // Set BASE URL path
       $contents['base_url'] = $this->getBaseURL($contents['template']);

       return $contents;
    }


    function isLoaded()
    {
       return $this->_LOADED;
    }

    function isSection($section = null)
    {
       $hash = $this->getSectionHash();
       return (! empty($hash[$section])) ? TRUE : FALSE;
    }

    function search($kword = null)
    {

       // Get the keywords in a list
       $keywordList = $this->getKeywordList($kword);

       // Get the list of sections for current app
       $allSections = $this->getSectionList();

       // Initialize matched sections to empty array
       $matchedSections = array();

       // Get a count of keywords in the keyword list
       $keywordCount = count($keywordList);

       // Initialize loop variable
       $i = 0;

       // For each keyword search each section keyword cache
       // store matched sections in $matchedSections array
       while($i< $keywordCount )
       {
          // Get the current keyword from the keyword list
          $keyword = $keywordList[$i];

          // Set local matched list to empty array
          $foundSections = array();

          // If the current keyword is really AND operator
          // we do not search for it. Instead, we advance the keyword
          // to the next keyword and search only those sections that
          // have already matched from the previous keywords. This effectively
          // implements the AND operation.
          if (!strcmp($keyword, 'and'))
          {
              $i++;

              // Set the keyword to the next keyword
              $keyword = $keywordList[$i];

              // Search for the new keyword (i.e. second operand of AND)
              // in *only* already matched sections 
              $foundSections = $this->getKeywordMatch($keyword, $matchedSections);

              // Set result in main matched section list
              $matchedSections = $foundSections;

          } else {

             // If the current keyword is not an AND operator, than we have an OR
             // (implied or explicit) relationship between the current keyword and the previous (if any)
             // in such case we search all the sections

             $foundSections = $this->getKeywordMatch($keyword, $allSections);

             // Now we merge the newly found matches for the keyword with previous matches
             // and we remove duplicates 
             $matchedSections = array_values(array_unique(array_merge($matchedSections, $foundSections)));

          }

          $i++;
       }

       // If we have found matches than we set object attributes
       if (count($matchedSections))
       {
          $matchedSections = array_values(array_unique($matchedSections));
          $this->_SEARCH_RESULT = $matchedSections;
          $this->_SEARCH_MATCH_COUNT = count($matchedSections);
       }

       // Return TRUE if matches found else return FALSE
       return (count($matchedSections) > 0) ? TRUE : FALSE;
    }

    function getSearchMatchCount()
    {
       return $this->_SEARCH_MATCH_COUNT;
    }


    function getSearchResults()
    {
       // Initialize contents hash
       $contents = array();

       // Set content output source
       $contents['output'] = 'search_results';

       //Store the keyword string in contents hash
       $contents['keyword_string'] = $this->getKeywordString();

       //Store matched section list in contents hash
       $contents['sections'] = $this->getSectionHash($this->_SEARCH_RESULT);

       // Create the link for table of contents
       $linkPrefix = sprintf("%s?app=%s", $_SERVER['PHP_SELF'], $this->getApp());

       // create a link to the help application for each section
       $sectionLinks = array();

       foreach($contents['sections'] as $id => $name)
       {
         $sectionLinks[$id] = sprintf("%s&section=%s", $linkPrefix, $id);
       }

       // Create the link for table of contents
       $tocLink = sprintf("%s?app=%s", $_SERVER['PHP_SELF'], $this->getApp());
       $contents['toc_link'] = $tocLink;

       // Store the section link array in the content hash
       $contents['match_count'] = count($this->_SEARCH_RESULT);

       // Store the section link array in the content hash
       $contents['section_links'] = $sectionLinks;

       // Get section page template
       $contents['template'] = $this->getSearchResultTempalte();

       // Set BASE URL path
       $contents['base_url'] = $this->getBaseURL($contents['template']);

       // Get most recent search keyword list (if any)
       $contents['recent_search'] = $this->getRecentSearchList();

       // Update most recent search keyword list with current keyword info
       $this->updateRecentSearchList($this->getKeywordString());


       return $contents;

    }

    function getRecentSearchList($asis = FALSE)
    {
       $hash = array();

        // Get the FQPN of search history file
       $historyFile = $this->getFQPNSearchHistoryFile();

       $serializedHistory = $this->_loadFile($historyFile);

       if (! empty($serializedHistory))
       {
          $history = unserialize($serializedHistory);
          if (! $asis)
          {
             foreach($history as $k => $v)
             {
                list($timestamp, $link) = explode(',', $v);
                $hash[$k] = $link;
             }
          } else {
             $hash = $history;
          }
       }

       return $hash;
    }
  
    function updateRecentSearchList($keywords = null)
    {

        $outHash = array();

        // If no keyword is supplied then no need to update the search history
        if (empty($keywords))
        {
            return FALSE;
        }

        // Read the existing search history hash
        $hash = $this->getRecentSearchList(TRUE);

        // If current keywords already exists then no need to update
        if (! empty($hash[$keywords]))
        {
           return FALSE;
        }

         // if the size of history exceeds permitted size
         // trim it by removing older entries
         if (count($hash) > SEARCH_HISTORY_SIZE) 
         {
            foreach($hash as $k => $v)
            { 
               list($timestamp, $link) = explode(',', $v);
               $order[$timestamp] = $k;
            }

            sort($order);
            reset($order);

            for($i=0;$i<SERACH_HISTORY_SIZE;$i++)
            {
               $keyword = $order[$i];
               $outHash[$keyword] = $hash[$keyword];
            }
         } else {

            $outHash = $hash;
         }

        $outHash[$keywords] = time() . ',' . $_SERVER['REQUEST_URI'];

        $this->writeSearchHistory($outHash);

        return TRUE;
    }

    function getKeywordMatch($keyword = null, $sections = null)
    {

       // Lowercase the keyword
       $keyword = strtolower($keyword);

       $matchedSections = array();

       // search for keyword in the keyword file
       foreach($sections as $thisSection)
       {
          //Load the keyword file for the current section.
          $keywordCacheFile = $this->getKeywordFile($thisSection);

          if (file_exists($keywordCacheFile))
          {
             $cache = $this->readKeywordCacheFile($keywordCacheFile);
             if ($cache[$keyword])
             {
                 array_push($matchedSections, $thisSection);
             }
          }
       }

       return $matchedSections;
    }

    function getSectionNumberList()
    {
        // return a list of section numbers
        return array_keys($this->_SECTIONS);
    }

    function getSectionHash($sections = null)
    {
      // If a section list is provided, create hash
      // section_number=section_title for just the given sections
      // else create for all sections

      if (! empty($sections))
      {
          $hash = array();
          foreach ($sections as $k)
          {
            $hash[$k] = $this->_SECTIONS[$k];
          }
          return $hash;

      } else {
         return $this->_SECTIONS;
      }
 
    }

    function getSectionList()
    {
      return array_keys($this->_SECTIONS);
    }

    function getKeywordString()
    {
       return (implode(' ', $this->_KEYWORDS));
    }

    function getKeywordList($keyword)
    {

       $seen = array();
       $list = array();

       $this->_KEYWORDS = array();

       // Remove duplicates and return keyword list
       
       $list = explode(' ', trim($keyword));

       // Make an unique list of keywords 
       // Remove all explicit OR as OR is always implied
       // between two given keywords

       for($i=0; $i<count($list); $i++)
       {
           $e = $list[$i];

           // if explicit OR operator, skip it
           if (!strcmp($e, 'or'))
           {
              continue;
           }

           // If keyword is not already in our _KEWORDS list
           // or it is an AND operator then add it in the _KEYWORDS list
           if (! $seen[$e] || !strcmp($e, 'and'))
           {
              array_push($this->_KEYWORDS, $e);
              $seen[$e] = TRUE;
           }
       }

       //return unique keywords

       return $this->_KEYWORDS;
    }

    function getSections()
    {
       return (! empty($this->_SECTIONS)) ? $this->_SECTIONS : null;
    }

    function getHelpDir()
    {
       return (! empty($this->_HELP_DIR)) ? $this->_HELP_DIR : null;
    }


    function makeKeywordIndex($sectionName = null, $sectionNumber = null)
    {

       // Find the fully qualified path name (FQPN) of the help file
       $helpFile = $this->getFQPNofSection($sectionNumber); 
       
       if (! file_exists($helpFile))
       {
          return FALSE;
       }


       // if the keyword index cache exists then we need to know
       // if the index cache is up-to-date based on help file
       // modification time and index cache creation time.
       // If help file modification timestamp newer than the
       // the cache index modification timestamp, we need to reindex
       // else nothing needs to be done

       // Create a keyword cache file name for this section
       $keywordCacheFile = $this->getKeywordFile($sectionNumber);
 
       if (file_exists($keywordCacheFile))
       {
           // check to see if help file modification timestamp is newer
           // than the index modification timestamp
           $helpFileModifyTimeStamp = filemtime($helpFile);
           $keywordIndexCreateTimeStamp = filemtime($keywordCacheFile);

           // Help file is older or (equal to) index timestamp than the index
           // need not be created.
           // However, if force mode is TRUE then we always build index.
           if (! $this->_FORCE &&  $helpFileModifyTimeStamp <= $keywordIndexCreateTimeStamp)
           {
               return TRUE;
           }
       }

       // Get a list of words in the help file
       $words = $this->_getWords($helpFile);

       // Remove the excluded words
       $words = $this->_removeExcludedWords($words);

 
       return $this->writeKeywordCacheFile($words, $keywordCacheFile);

    }

    function getFQPNSearchHistoryFile()
    {
        return sprintf("%s/%s", $this->getHelpDir(), SEARCH_HISTORY_FILE);
    }

    /*
        Purpose: returns the fully qualified path name of a help section by 
        section number
    */
    function getFQPNofSection($sectionNumber = null)
    {
       $path = sprintf("%s/%s.html", $this->_HELP_DIR, $sectionNumber); 
       return $path;
    }

    function getHelpTemplateDir()
    {
       return sprintf("%s/%s", $this->getHelpDir(), $this->_REL_TEMPLATE_DIR);
    }

    function getDefaultSectionTemplate()
    {
       $file = sprintf("%s/%s", $this->getHelpTemplateDir(), $this->_DEFAULT_SECTION_TEMPLATE);
       return  (file_exists($file)) ? $file : GLOBAL_DEFAULT_SECTION_TEMPLATE_FQPN;
    }

    function getTOCTempalte()
    {
       $file = sprintf("%s/%s", $this->getHelpTemplateDir(), $this->_TOC_TEMPLATE);
       return  (file_exists($file)) ? $file : GLOBAL_DEFAULT_TOC_TEMPLATE_FQPN;
    }

    function getSearchResultTempalte()
    {
       $file = sprintf("%s/%s", $this->getHelpTemplateDir(), $this->_SEARCH_RESULT_TEMPLATE);
       return  (file_exists($file)) ? $file : GLOBAL_DEFAULT_SEARCH_RESULT_TEMPLATE_FQPN;
    }


    function getSectionTemplate($section = null)
    {
       
       // Find section specific template (if any)
       if (! empty( $this->_TEMPLATES[$section]))
       {
           $template = sprintf("%s/%s", $this->getHelpTemplateDir(), $this->_TEMPLATES[$section]);

           if (file_exists($template))
           {
              return $template;
           } 
       } 


       // Otherwise, return the default section template
       return $this->getDefaultSectionTemplate();
    }

    function getBaseURL($template = null)
    {
       $base_url = dirname($template);
       $root = ROOT_PATH;
       $base_url = preg_replace("|$root|", '', $base_url) . 'class.Help.php/';
       return $base_url;
    }

    function loadMap()
    {
       $mapFile = $this->getMapFile();

       // if the map file does not exist than return FALSE
       if (!file_exists($mapFile))
       {
           return FALSE;
       }


       // Load the map file
       require_once $mapFile;

       // if not a fqpn then prepend the main help dir
       // and also assume that %DocumentRoot% relative help dir
       // would be relative from REL_HELP_DIR defined in help.conf
       if ( ! preg_match("/^\//", $HELP_DIR))
       {
           $this->_HELP_DIR = ROOT_PATH . '/' . $HELP_DIR;
           $this->_REL_HELP_DIR = REL_ROOT_PATH . '/' . $this->getApp();

       } else {

           $this->_HELP_DIR = $HELP_DIR;
           $this->_REL_HELP_DIR = $REL_HELP_DIR;
       }

       //Load relative template directory
       $this->_REL_TEMPLATE_DIR = $REL_TEMPLATE_DIR;

       //Load default Section template file name
       $this->_DEFAULT_SECTION_TEMPLATE = $DEFAULT_SECTION_TEMPLATE;

       //Load Table of Contents Template
       $this->_TOC_TEMPLATE = $TOC_TEMPLATE;

       //Load Search Result Template
       $this->_SEARCH_RESULT_TEMPLATE = $SEARCH_RESULT_TEMPLATE;

       // Load specific section template (if any);
       $this->_TEMPLATES = $TEMPLATES;

       $this->_SECTIONS = $SECTIONS;

       return TRUE;
    }

    function getMapFile()
    {
       
       if(!empty($this->_MAP))
       {
           return sprintf("%s/%s",HELP_MAP_DIR, $this->_MAP);
       }

       return null;
    }

    function readKeywordCacheFile($file)
    {

       if (!$fp = fopen($file, 'r')) {
           return FALSE;
       }

       $data = fread ($fp, filesize ($file));

       fclose($fp);

       $cache = unserialize($data);

       return $cache;
       
    }

    function writeKeywordCacheFile($words, $file)
    {

      $cache = array();

      foreach ($words as $singleWord)
      {
         $cache[$singleWord] = TRUE;
      }

      $out = serialize($cache);

      if (!$fp = fopen($file, 'w')) 
      {
          print "Cannot open file ($file)";
          return FALSE;
      }

      if (!fwrite($fp, $out)) 
      {
         print "Cannot write to file ($file)";
         return FALSE;
      }

      fclose($fp);

      return TRUE;
    }

    function writeSearchHistory($hash = null)
    {

      // Get the FQPN of search history file
      $file = $this->getFQPNSearchHistoryFile();

      // Serialized the hash
      $out = serialize($hash);

      if (!$fp = fopen($file, 'w'))
      {
          print "Cannot open file ($file)";
          return FALSE;
      }

      if (!fwrite($fp, $out))
      {
         print "Cannot write to file ($file)";
         return FALSE;
      }

      fclose($fp);

      return TRUE;
    }



    function getKeywordFile($sectionNumber = null)
    {
       return sprintf("%s/%s.kwrd", $this->_HELP_DIR, $sectionNumber);
    }

    function _removeExcludedWords($words)
    {

       $newWords = array();

       $excludeWords = $this->_getWords(EXCLUDED_WORD_FILE);
       foreach ($words as $thisWord)
       {
          if (!in_array($thisWord, $excludeWords))
          {
              array_push($newWords, $thisWord);
          }
       }
 
       return $newWords;
    }

    function _getWords($file)
    {
       $htmlContents = $this->_loadFile($file);
       $textContents = $this->_HTMLtoText($htmlContents);
       $words        = explode(' ', $textContents);
       $words        = $this->_getUniqueWords($words);
       return $words;
    }

    function _getUniqueWords($words = null)
    {

       // Now perform the following operations on each word:
       //   - lowercase each word
       //   - remove any trailing non word character such as (.,;:) etc.
       for($i=0;$i<count($words);$i++)
       {
          $words[$i] = preg_replace('/(^[^\w]+|[^\w]+$)/', '', strtolower($words[$i]));
       } 

       // Get unique words from the contents
       $words =  array_unique($words);

       // Remove array elements that have been NULLed by array_unique
       $words =  array_values($words);

       
       return $words;
    }

    function _loadFile($file = null)
    {

       $contents = NULL;
       if (! file_exists($file))
       {
           return $contents;
       }

       $fd = fopen ($file, "r");
       $contents = fread ($fd, filesize ($file));
 
       fclose ($fd);
         
       return $contents;
    }

   function _HTMLtoText($document)
   {
		
   $search = array("'<script[^>]*?>.*?</script>'si",	//strip out javascript
	"'<[\/\!]*?[^<>]*?>'si",	                // strip out html tags
	"'([\r\n])'",		                        // strip out line breaks
	"'&(quote|#34);'i",		                // replace html entities
	"'&(amp|#38);'i",
	"'&(lt|#60);'i",
	"'&(gt|#62);'i",
	"'&(nbsp|#160);'i",
	"'&(iexcl|#161);'i",
	"'&(cent|#162);'i",
	"'&(pound|#163);'i",
	"'&(copy|#169);'i"
	);

   $replace = array(	
	" ",
	" ",
	" ",
	"\"",
	"&",
	"<",
	">",
	" ",
	chr(161),
	chr(162),
	chr(163),
	chr(169));					

   $text = trim(preg_replace($search,$replace,$document));


   return $text;

   } 

}

?>
