<?php

   error_reporting(E_ALL);

   require_once "user_mngr.conf";

   require_once $USER_CLASS;

   class userForgottenPwdApp extends PHPApplication {

      function run()
      {

          $this->resetPasswordDriver();

      }

      function resetPasswordDriver()
      {
          global $step;

          if (!$step)
          {
             global $USERMNGR_PWD_REQUEST_TEMPLATE, $USERMNGR_FORGOTTEN_APP;

             print $this->showScreen($USERMNGR_PWD_REQUEST_TEMPLATE, 'get_username', $USERMNGR_FORGOTTEN_APP);

          } else if ($step == 2) {


            $this->sendEmail();

          } else if ($step == 3){

             global $USERMNGR_PWD_RESET_TEMPLATE,
                    $USERMNGR_FORGOTTEN_APP;

             print $this->showScreen($USERMNGR_PWD_RESET_TEMPLATE,
                                     'reset_pwd',
                                     $USERMNGR_FORGOTTEN_APP);
          } else {

             $this->resetPassword();

          }
      }

      function sendEmail()
      {
          global $username,
                 $USERMNGR_FORGOTTEN_APP,
                 $USERMNGR_PWD_EMAIL_TEMPLATE,
                 $USERMNGR_PWD_EMAIL_SUBJECT,
                 $USERMNGR_PWD_EMAIL_FROM,
                 $DEFAULT_DOMAIN,
                 $AUTHENTICATION_URL,
                 $CHAR_SET;

          $this->emptyError($username, 'USERNAME_MISSING');

          if (!strstr($username,'@'))
          {
             $username = $username . '@' . $DEFAULT_DOMAIN;
          }


          $message = $this->showScreen($USERMNGR_PWD_EMAIL_TEMPLATE,
                                       'email',
                                       $USERMNGR_FORGOTTEN_APP);

          if (! $message)
          {
             $this->alert('USER_NOT_FOUND');
          }
          $headers  = "To: $username\r\n";
          $headers .= "From: User Manager\r\n";
          $headers .= "Subject: Resetting Your Forgotton Password\r\n";
          $headers .= "Content-Type: text/html;$CHAR_SET\r\n";
          $headers .= "X-Priority: 1 (High)\r\n";

          $status = mail($username,
                         $USERMNGR_PWD_EMAIL_SUBJECT,
                         $message,
                         $headers);


          if ($status)
          {
              $this->show_status($this->getMessage('PWD_EMAIL_SENT'), $AUTHENTICATION_URL);
          } else {
              $this->show_status($this->getMessage('PWD_EMAIL_NOT_SENT'), $AUTHENTICATION_URL);
          }
      }

      function getCheckSum($uid)
      {
          global $SECRET;
          return $uid << 8 + $SECRET;
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
      

      function resetPassword()
      {
          global $user_id,
                 $chk,
                 $password1,
                 $password2,
                 $AUTHENTICATION_URL;


          $calculatedChecksum = $this->getCheckSum($user_id);

          if ($calculatedChecksum != $chk)
          {
              $this->alert('INVALID_REQUEST');
          }

          $this->checkPassword($password1, $password2);

          $salt = user_mngr_forgotten_pwd . phpchr(rand(64, 90)) . chr(rand(64, 90));

          $cryptPassword = crypt($password1, $salt);

          $hash = array(
                          'PASSWORD' => $cryptPassword,
                       );

          $userObj = new User($this->dbi, $user_id);

          $userObj->getUserInfo();


          $status = $userObj->updateUser($hash);

          if ($status)
          {
             $this->show_status($this->getMessage('USER_MODIFY_SUCCESSFUL'),
                                $AUTHENTICATION_URL);
          } else {
             $this->show_status($this->getMessage('USER_MODIFY_FAILED'),
                                $AUTHENTICATION_URL);
          }

      }

      function email(&$t)
      {

          global $username, $USERMNGR_FORGOTTEN_APP;

          $userObj = new User($this->dbi);

          $uid = $userObj->getUserIDByName($username);

          if (!$uid)
          {
             return FALSE;
          }

          $chksum = $this->getCheckSum($uid);
          $appURL = sprintf("%s?uid=%d&chk=%d&step=%s",
                                         $this->getFQAN($USERMNGR_FORGOTTEN_APP),
                                         $uid,
                                         $chksum,
                                         3);

          $t->set_var('PASSWORD_URL', $appURL);

          return TRUE;

      }

      function get_username(&$t)
      {
         $t->set_var('ACTION','reset');
         $t->set_var('BASE_URL', $this->base_url);

         return TRUE;
      }

      function reset_pwd(&$t)
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
         return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $APP_DB_URL;

   $thisApp = new userForgottenPwdApp(
                             array('app_name'           => $APPLICATION_NAME,
                                   'app_version'        => '1.0.0',
                                   'app_type'           => 'WEB',
                                   'app_auto_authorize' => FALSE,
                                   'app_auto_connect'   => TRUE,
                                   'app_auto_chk_session' => FALSE,
                                   'app_db_url'         => $APP_DB_URL,
                                   'app_debugger'       => $ON
                                   )
                            );

   //$thisApp->buffer_debugging();

   $thisApp->run();

   //$thisApp->dump_debuginfo();

?>
