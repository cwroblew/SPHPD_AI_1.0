<?php

   require_once "home.conf";

   require_once $INTRANET_USER_CLASS;

   /* Session variables must be defined before session_start() method is called */
   $SESSION_USERNAME = null;
   $SESSION_USER_ID = null;
   $SESSION_PASSWORD = null;


   class IntranetUserPreferenceApp extends PHPApplication {

      function run()
      {
          global $SESSION_USERNAME, $SESSION_USER_ID, $SESSION_THEME;
          global $INTRANET_DB_URL;
          global $TEMPLATE_DIR;
          
          $pref = $this->getRequestField('pref');
          $skin = $this->getRequestField('skin');
          $autotip = $this->getRequestField('autotip');


          if (! $this->authorize($SESSION_USERNAME))
          {
             $this->alert('UNAUTHORIZED_ACCESS');
             exit;
          }

          // At this point user is authorized
          $this->uid = $this->getSessionField('SESSION_USER_ID');
          $themeObj = new Theme($this->dbi, null,'preference');

          $this->theme = $themeObj->getUserTheme($this->uid);
          $this->themeObj = $themeObj;

          $pref = (!isSet($pref)) ?  'No Update' : substr(strtolower($pref),0,3);

          if (! strcmp($pref, 'upd'))
          {

              $themeObj = new Theme($this->dbi);
              $IntranetUserObj = new IntranetUser($this->dbi);

              $this->debug("Theme ID $this->theme USER_ID = $this->uid<BR>");

              $themeObj->updateTheme($skin, $this->uid);


              $storedTheme = $themeObj->getUserTheme($this->uid);

              if ($storedTheme != $skin)
              {
              	 // update failed because preference row did not exist
              	 // so insert new row
                $themeObj->addTheme($skin, $this->uid);
                $IntranetUserObj->addAutoTip($this->uid, $autotip);
              }

              $this->theme = $themeObj->getUserTheme($this->uid);

              $IntranetUserObj->updateAutoTip($this->uid, $autotip);

              $msg = $this->getMessage('PREFERENCES_UPDATED');
              $this->update = true;

          }

          $this->displayMenu();

     }


      function authorize()
      {
          return TRUE;
      }

      function displayMenu()
      {
          $this->debug("Display IntranetUser home for USER_ID $this->uid");
          global $HOME_TEMPLATE,
                 $THEME_TEMPLATE;
          global $THEME_TEMPLATE_DIR,
                 $PREF_MNGR,
                 $PREFERENCE_TEMPLATE,
                 $REL_TEMPLATE_DIR,
                 $APP_PREFERENCE_MNGR_TITLE;
          global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
          
          $pref = $this->getRequestField('pref');



          $themeTemplate = new Template($THEME_TEMPLATE_DIR);
          $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
          $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
          $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
          
          $themeTemplate->set_var('SERVER_NAME', $this->get_server());
          $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
          $themeTemplate->set_var('LEFT_NAVIGATION', $this->themeObj->getLeftNavigation($THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme])));

          

          $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
          $photo = file_exists($photoFile) ? sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID()) : sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
          $themeTemplate->set_var('PHOTO', $photo);

          $template = new Template($this->getTemplateDir());

          $template->set_file('fh1', $PREFERENCE_TEMPLATE);
          $template->set_block('fh1', 'mainBlock', 'mblock');
          $template->set_block('mainBlock', 'themeBlock', 'thblock');

          $themeObj = new Theme($this->dbi);
          $userTheme = $themeObj->getUserTheme($this->uid);
          $themeArr = $themeObj->getAllThemes();

          while (list($key, $value) = each($themeArr))
          {
             	$template->set_var('THEME_NAME', $value);
             	$template->set_var('THEME_ID', $key);

             	if (! strcmp($key, $userTheme))
             	{
             		$template->set_var('CH_VAL', 'selected');

                }
                else $template->set_var('CH_VAL', '');
             	$template->parse('thblock', 'themeBlock', true);
          }

          $IntranetUser = new IntranetUser($this->dbi, $this->uid);

          $pref = $IntranetUser->getPreferences($this->uid);

          if (! empty($pref['autotip']) && $pref['autotip']==1) {
              $template->set_var('TIP_STATUS1', 'checked');
          } else {
            $template->set_var('TIP_STATUS1', '');
          }

          if (! empty($pref['autotip']) && $pref['autotip']==0) {
              $template->set_var('TIP_STATUS2', 'checked');
          } else {
              $template->set_var('TIP_STATUS2', '');
          }

          $template->set_var('PREF_MNGR', $PREF_MNGR);

          if (isset($this->update) && $this->update)
          {
             $template->set_var('UPDATE_MSG',
                                 $this->getMessage('PREFERENCES_UPDATED')
                               );
          }
          else $template->set_var('UPDATE_MSG', '');

          $themeTemplate->set_var('CONTENT_BLOCK',
                                  $template->parse('mblock', 'mainBlock'));

          $template->parse('contentBlock', 'fh1');

          $themeTemplate->parse('cnblock', 'contentBlock');
          $themeTemplate->parse('mmblock', 'mmainBlock');
          $themeTemplate->pparse('output', 'fh');

      }

   }//class

   global $INTRANET_DB_URL;
   $thisApp = new IntranetUserPreferenceApp(
                            array( 'app_name'             => $APPLICATION_NAME,
                                  'app_version'           => '1.0.0',
                                  'app_type'              => 'WEB',
                                  'app_db_url'            => $INTRANET_DB_URL,
                                  'app_auto_connect'      => TRUE,
                                  'app_auto_chk_session'  => FALSE,
                                  'app_auto_authorize'    => FALSE,
                                  'app_debugger'          => $OFF
                                 )
                        );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();
?>
