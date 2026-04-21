<?php

    class ThemeTemplate{

        function ThemeTemplate($id = null,
                               $blocks =null
                               )
        {

           global $THEME_TEMPLATE_DIR, $THEME_TEMPLATE, $PRINT_TEMPLATE;
           global $print;

           $this->blocks = $blocks;
           $this->templateInfo  = $templateInfo;

           $this->theme = $id;

           $this->template = new Template($THEME_TEMPLATE_DIR);

           if (!$print)
           {

              $this->template->set_file('fh', $THEME_TEMPLATE[$this->theme]);

           } else {

              $this->template->set_file('fh', $PRINT_TEMPLATE[$this->theme]);
           }

           // Create a list of blocks
           $blockID = 1;
           while(list($parentTag, $name) = each($this->blocks))
           {
              list($parentOrder, $parent) = explode(':', $parentTag);
              $blockTag = sprintf("block%03d",$blockID++);
              $this->template->set_block($parent, $name, $blockTag);
              $this->blockTags[$name] = $blockTag;

           }

          $this->set_var('LEFT_NAVIGATION', $this->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));
       }

       function set_var($var = null, $value = null)
       {

          $this->template->set_var($var, $value);
       }

       function parse($name = null, $loop = null)
       {

       	  $status = $this->template->parse($this->blockTags[$name], $name, $loop);


       }

       function finishBlock($blockName = null)
       {

          /*

            When using blocks in loop, this method must be called to
            ensure that publish() method does not parse a block wiht
            parse() method parameter "append" (concat) set to FALSE.
          */

          $blks= $this->blocks;

          while(list($key, $value) = each($blks))
          {

             if (!strcmp($blockName,$value))
             {
             	$this->blocks[$key] = null;
             }
          }

       }

       function removeBlock($blockName = null)
       {
          $this->set_var($this->blockTags[$blockName], null);
          $this->finishBlock($blockName);
       }


       function publish($data = null)
       {

          // Set key=values for replacement
          // Force key to be uppercase
          while(list($key , $val) = each($data))
          {

              $this->template->set_var(strtoupper($key),$val);
          }

          $reverseBlocks = array_reverse($this->blocks);
          while(list($parentTag, $name) = each($reverseBlocks))
          {

             if ($this->blocks[$parentTag] != null)
             {
                $this->template->parse($this->blockTags[$name], $name, true);
             }
          }

          $this->template->pparse('output', 'fh');

       }


       function getLeftNavigation($dir = null)
       {
       	  $this->leftNavigationPage = sprintf("%s/%s_left_nav.ihtml", $dir, $this->page);


       	  if (! file_exists($this->leftNavigationPage))
       	  {
       	     $this->leftNavigationPage = sprintf("%s/default_left_nav.ihtml", $dir);
       	  }


           $fp = fopen($this->leftNavigationPage, "r");
           if ($fp)
           {
              $contents = fread($fp, filesize($this->leftNavigationPage));
              fclose($fp);
           }


           return $contents;
       }

    }

?>