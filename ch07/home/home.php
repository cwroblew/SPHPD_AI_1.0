<?php

   require_once "home.conf";
   require_once $MESSAGE_CLASS;

   require_once $INTRANET_USER_CLASS;

   /* Session variables must be defined before session_start() method is called */
   
   class IntranetUserHomeApp extends PHPApplication {

      function run()
      {
          global $TEMPLATE_DIR;
          
          $read = $this->getRequestField('read');

          if (! $this->authorize($this->getSessionField('SESSION_USERNAME')))
          {
             $this->alert('UNAUTHORIZED_ACCESS');
          }

           $this->uid = $this->getUID();

           $themeObj = new Theme($this->dbi,null,'home');

           $this->themeObj = $themeObj;

           $this->theme = $themeObj->getUserTheme($this->uid);

           $this->template_dir = $TEMPLATE_DIR;

           if (!empty($read))
           {
              $this->updateMsgTrack();
           }

          // At this point user is authorized
          $this->displayHome();
     }


      function authorize()
      {
          return TRUE;
      }

      function updateMsgTrack()
      {
         $mid = $this->getRequestField('mid');

         $msgObj = new Message($this->dbi);
         $msgObj->updateTrack($this->uid, $mid);
         return true;
      }

      function displayHome()
      {
          global $HOME_MNGR;
          global $HOME_TEMPLATE;
          global $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
          global $REL_TEMPLATE_DIR;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;

          $themeTemplate = new Template($THEME_TEMPLATE_DIR);

          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');

          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);

          $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);


          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));

          
          $template = new Template($this->getTemplateDir());
          $template->set_file('fh1', $HOME_TEMPLATE);
          $template->set_block('fh1', 'mainBlock', 'mblock');
          $template->set_block('mainBlock', 'msgBlock', 'msg');
          
          $template->set_var('msg', null);

          $now = time();

          $template->set_var(array(
                                   'NAME'         => $this->getName(),
                                   'CURRENT_DATE' => date('l M d Y', $now),
                                   'CURRENT_TIME' => date('h:i:s A (T)', $now),
                                   'HOME_MNGR'    => $HOME_MNGR
                                   )
                            );
          
          
          global $LD_CATEGORY_NAV_DIR, $LD_CATEGORY_NAV_OUTFILE;
          
          
          $fp = fopen($LD_CATEGORY_NAV_DIR.'/'.$LD_CATEGORY_NAV_OUTFILE, "rb");
          $contents = fread ($fp, filesize ($LD_CATEGORY_NAV_DIR.'/'.$LD_CATEGORY_NAV_OUTFILE));
          
          $template->set_var('LD_NAV', $contents);

          $msgObj = new Message($this->dbi);
          $msgArr = $msgObj->getMessages($this->uid);
          //$this->dump_array($msgArr);
          global $USER_DB_URL;
          $user_dbi = new DBI($USER_DB_URL);
          
          if (!empty($msgArr))
          {
            foreach($msgArr as $msg)
            {
            	//$this->dump_array($msg);
                if ($msgObj->isViewable($msg->MSG_ID, $this->getUID()))
                {
             	   
             	   $userObj = new User($user_dbi, $msg->AUTHOR_ID);
             	   $auth = $userObj->getEMAIL();             	   
             	   $template->set_var('MSG_DATE', date("M-d-Y",$msg->MSG_DATE));
             	   $template->set_var('MSG_ID', $msg->MSG_ID);
             	   $template->set_var('MSG_TITLE', $this->unhtmlentities(stripslashes($msg->MSG_TITLE)));
             	   $template->set_var('MSG_AUTHOR', $auth);
             	   $template->set_var('MSG_CONTENTS', $this->unhtmlentities(stripslashes($msg->MSG_CONTENTS)));
             	   $template->set_var('TABLE_BGCOLOR', $msg->MSG_TYPE ? ADMIN_MSG_COLOR : STANDARD_MSG_COLOR);
             	   $template->parse('msg', 'msgBlock', true);
             	}   
            }
          }
          else
          {
             $template->set_var('msg', null);
          }

          $thisUser = new IntranetUser($this->dbi, $this->uid);

          $pref = $thisUser->getPreferences($this->uid);

          session_register('SESSION_AUTO_TIP_SHOWN');

          if (!empty($pref['autotip']) && !($this->getSessionField('SESSION_AUTO_TIP_SHOWN')))
          {
          	 $this->debug("Show tip window");
          	 $_SESSION["SESSION_AUTO_TIP_SHOWN"] = 1;
          	 $template->set_var('JS_TIP_SCRIPT', $this->popAutoTip());
          } else {
          	 $template->set_var('JS_TIP_SCRIPT', null);
          }

          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
          $template->set_var('USER_NAME', ucfirst($thisUser->getName()));

          $themeTemplate->set_var('CONTENT_BLOCK',
                                  $template->parse('mblock', 'mainBlock'));

          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');

      }
      
      function unhtmlentities ($string)
      {
	$trans_tbl = get_html_translation_table (HTML_ENTITIES);
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
      }


      function getName()
      {
      	 list($name, $host) = explode('@', $this->getEMAIL());
      	 return ucfirst($name);
      }

      function popAutoTip()
      {
      	  global $TIP_SCRIPT;

          if (! file_exists($TIP_SCRIPT)) return null;

      	  $fp = fopen($TIP_SCRIPT, "r");
      	  if ($fp)
      	  {
      	  	 $contents = fread($fp, filesize($TIP_SCRIPT));
      	  }

      	  return $contents;
      }

   }//class

   global $INTRANET_DB_URL;

   $thisApp = new IntranetUserHomeApp(
                                      array( 'app_name'            => $APPLICATION_NAME,
                                             'app_version'         => '1.0.0',
                                             'app_type'            => 'WEB',
                                             'app_db_url'          => $INTRANET_DB_URL,
                                             'app_auto_connect'    => TRUE,
                                             'app_auto_chk_session' => TRUE,
                                             'app_auto_authorize'  => FALSE,
                                             'app_debugger'        => $OFF
                                           )
                                      );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
