<?php

   require_once "user_mngr.conf";

   require_once $USER_CLASS;

   class userPasswordApp extends PHPApplication {

      function run()
      {

          $this->changePasswordDriver();

     }

      function changePasswordDriver()
      {
          $step = $this->getRequestField('step');

          if (!$step)
          {

             global $USERMNGR_PWD_CHANGE_TEMPLATE,
                    $USERMNGR_CHANGE_PWD_APP;

             print $this->showScreen($USERMNGR_PWD_CHANGE_TEMPLATE,
                                     'change_pwd',
                                     $USERMNGR_CHANGE_PWD_APP);
          } else {

             $this->changePassword();

          }
      }

      function checkPassword($pwd1, $pwd2)
      {

          global $MIN_PASSWORD_SIZE, $DUMMY_PASSWD;

          $this->emptyError($pwd1, 'PASSWORD1_MISSING');
          $this->emptyError($pwd2, 'PASSWORD2_MISSING');

          if (strcmp($pwd1, $pwd2))
          {
             $this->alert('PASSWORD_MISMATCH');

          } else if (!strcmp($pwd1, $DUMMY_PASSWD) ||
                     strlen($pwd1) < $MIN_PASSWORD_SIZE) {

             $this->alert('INVALID_PASSWORD');

          }

      }

      function changePassword()
      {
          $password1 = $this->getRequestField('password1');
          $password2 = $this->getRequestField('password2');
          
          global $USERMNGR_MNGR, $APP_MENU;

          $this->checkPassword($password1, $password2);

          $salt = user_mngr_passwd . phpchr(rand(64, 90)) . chr(rand(64, 90));

          $cryptPassword = crypt($password1, $salt);

          $hash = array(
                          'PASSWORD' => $cryptPassword,
                       );

          $userObj = new User($this->dbi, $this->getUID());
          $userObj->getUserInfo();

          $status = $userObj->updateUser($hash);

          if ($status)
          {
             $this->show_status($this->getMessage('USER_MODIFY_SUCCESSFUL'),
                                $APP_MENU);
          } else {
             $this->show_status($this->getMessage('USER_MODIFY_FAILED'),
                                $APP_MENU);
          }

      }


      function change_pwd(&$t)
      {
      	 global $uid, $chk;
      	 $t->set_var('ACTION','reset');
      	 $t->set_var('USER_ID', $uid);
      	 $t->set_var('CHECKSUM', $chk);
         $t->set_var('BASE_URL', $this->base_url);

      	 return TRUE;
      }


      function authorize()
      {
      	 $userObj = new User($this->dbi, $this->getUID());

      	 return $userObj->isUser();


      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $APP_DB_URL;

   $thisApp = new userPasswordApp(
                             array('app_name'           => $APPLICATION_NAME,
                                   'app_version'        => '1.0.0',
                                   'app_type'           => 'WEB',
                                   'app_auto_authorize' => TRUE,
                                   'app_auto_connect'  => TRUE,
                                   'app_auto_chk_session' => FALSE,
                                   'app_db_url'         => $APP_DB_URL,
                                   'app_debugger'       => $ON
                                   )
                            );

   // $thisApp->buffer_debugging();

   $thisApp->run();

   // $thisApp->dump_debuginfo();

?>
