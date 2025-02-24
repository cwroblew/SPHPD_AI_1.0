<?php

   require_once "user_mngr.conf";

   require_once $USER_CLASS;

   class userManagerApp extends PHPApplication {
	  //var $dbi;
	  
      function run()
      {
          global $USERMNGR_MNGR;
          
          $cmd = $this->getRequestField('cmd'); 


          if (! $this->authorize())
          {
              $this->alert('UNAUTHORIZED_ACCESS');
          }

          // At this point user is authorized

          $cmd = strtolower($cmd);

          if (!strcmp($cmd, 'add'))
          {
              $this->addDriver();

          } else if (!strcmp($cmd, 'modify')) {

              $this->modifyDriver();

          } else if (!strcmp($cmd, 'delete')) {

              $this->deleteUser();

          } else {

             global $USERMNGR_MENU_TEMPLATE;

             print $this->showScreen($USERMNGR_MENU_TEMPLATE, 'menu', $USERMNGR_MNGR);

          }
     }

     function modifyDriver()
     {
          $step = $this->getRequestField('step');

          if ($step == 2)
          {
             $this->modifyUser();

          } else {

             global $USERMNGR_USER_TEMPLATE, $USERMNGR_MNGR;

             print $this->showScreen($USERMNGR_USER_TEMPLATE, 'modify_screen', $USERMNGR_MNGR);

          }
      }

      function addDriver()
      {
          $step = $this->getRequestField('step');

          if ($step == 2)
          {
             $this->addUser();

          } else {

             global $USERMNGR_USER_TEMPLATE, $USERMNGR_MNGR;

             print $this->showScreen($USERMNGR_USER_TEMPLATE, 'add_screen', $USERMNGR_MNGR);
          }
      }

      function addUser()
      {
          $username = $this->getRequestField('username'); 
		  $email = $this->getRequestField('email'); 
          $password1 = $this->getRequestField('password1'); 
          $password2 = $this->getRequestField('password2');
          $user_type = $this->getRequestField('user_type');
          $active = $this->getRequestField('active');
          
          global $DEFAULT_DOMAIN,
                 $USERMNGR_MNGR;


          $this->checkInput();

          //if (!strstr($username,'@'))
          //{
          //   $username = $username . '@' . $DEFAULT_DOMAIN;
          //}


          $salt = user_mngr . phpchr(rand(64, 90)) . chr(rand(64, 90));

          $cryptPassword = crypt($password1, $salt);

          $hash = array(
                          'USERNAME'    => strtolower($username),
                          'EMAIL'    => strtolower($email),
                          'PASSWORD' => $cryptPassword,
                          'TYPE'     => $user_type,
                          'ACTIVE'   => $active
                       );

          $userObj = new User($this->dbi);

          $status = $userObj->addUser($hash);

          if ($status)
          {
             $this->show_status($this->getMessage('USER_ADD_SUCCESSFUL'),
                                $USERMNGR_MNGR);
          } else {
             $this->show_status($this->getMessage('USER_ADD_FAILED'),
                                $USERMNGR_MNGR);
          }

      }


      function modifyUser()
      {
          $username = $this->getRequestField('username'); 
		  $email = $this->getRequestField('email'); 
          $password1 = $this->getRequestField('password1'); 
          $password2 = $this->getRequestField('password2');
          $user_type = $this->getRequestField('user_type');
          $active = $this->getRequestField('active');
          $user_id = $this->getRequestField('user_id');
          
          global $USERMNGR_MNGR,
                 $ADMINISTRATIVE_USER,
                 $ROOT_USER,
                 $DUMMY_PASSWD;

          $this->checkInput();

          // If user is ROOT USER then she cannot be deactivated
          if ( ! strcmp($username, $ROOT_USER))
          {
              if (! $active )
              {
                 $this->alert('INACTIVE_NOT_OK');
                 return;
              }

              if ($user_type != $ADMINISTRATIVE_USER)
              {
                 $this->alert('OPERATION_NOT_ALLOWED');
                 return;
              }

          }

          $hash = array(
                          'EMAIL'    => strtolower($email),
                          'TYPE'     => $user_type,
                          'ACTIVE'   => $active,
                          'USER_ID'  => $user_id
                       );

          if (strcmp($password1, $DUMMY_PASSWD))
          {

              $salt = user_mngr . phpchr(rand(64, 90)) . chr(rand(64, 90));
              $cryptPassword    = crypt($password1, $salt);
              $hash['PASSWORD'] = $cryptPassword;
          }


          
          $userObj = new User($this->dbi, $user_id);
          
          $userObj->getUserInfo();

          $hash['EMAIL'] = (strcmp($email, $userObj->getEMAIL())) ? strtolower($email) : null;          
          

          $status = $userObj->updateUser($hash);

          if ($status)
          {
             $this->show_status($this->getMessage('USER_MODIFY_SUCCESSFUL'),
                                $USERMNGR_MNGR);
          } else {
             $this->show_status($this->getMessage('USER_MODIFY_FAILED'),
                                $USERMNGR_MNGR);
          }

      }


      function deleteUser()
      {
          global $USERMNGR_MNGR,
                 $ROOT_USER;
                 
          $user_id = $this->getRequestField('user_id');       

          $this->emptyError($user_id, 'USER_ID_MISSING');


          $userObj = new User($this->dbi, $user_id);

          $userObj->getUserInfo();

          $email = $userObj->getEMAIL();

          if (! strcmp($email, $ROOT_USER))
          {
             $this->alert('USER_DELETE_NOT_ALLOWED');

          } else {

             $status = $userObj->deleteUser();
          }

          if ($status)
          {
             $this->show_status($this->getMessage('USER_DELETE_SUCCESSFUL'),
                                $USERMNGR_MNGR);
          } else {
             $this->show_status($this->getMessage('USER_DELETE_FAILED'),
                                $USERMNGR_MNGR);
          }

      }


      function menu(&$t)
      {
         $userObj = new User($this->dbi);
         $users = $userObj->getUserList();

         $t->set_block('mainBlock','userBlock', 'ublock');

         while(list($uid, $email) = each($users))
         {
             $t->set_var( array(
                                'USER_ID'   => $uid,
                                'USER_NAME' => $email,
                               )
                        );

             $t->parse('ublock', 'userBlock', true);
         }


         return TRUE;
      }


      function modify_screen(&$t)
      {

         global $DUMMY_PASSWD;
         
         $user_id = $this->getRequestField('user_id');

         $userObj = new User($this->dbi, $user_id);

         $status = $userObj->getUserInfo();

         if (! $status)
         {

            $this->alert('USER_INFO_MISSING');

         } else {

            $userType = $userObj->getTYPE();

         }

         $userTypes = $userObj->getUserTypeList();

         $t->set_block('mainBlock','typeBlock', 'tblock');

         $chosen = '';

         while(list($tid, $typeName) = each($userTypes))
         {

             $chosen = ($tid == $userType) ? 'selected' : '';

             $t->set_var(
                          array(
                                'TYPE_ID'   => $tid,
                                'USER_TYPE' => $typeName,
                                'CHOSEN'    => $chosen
                               )
                         );

             $t->parse('tblock', 'typeBlock', true);

         }

         $fields = $userObj->getUserFieldList();

         foreach ($fields as $f)
         {
            $t->set_var($f, null);
         }

         $activeON  = ( $userObj->getACTIVE()) ? 'checked' : null;
         $activeOFF = (!$userObj->getACTIVE()) ? 'checked' : null;

         $t->set_var(array(
                           'EMAIL'      => $userObj->getEMAIL(),
                           'USERNAME'      => $userObj->getUSERNAME(),
                           'PASSWORD'   => $DUMMY_PASSWD,
                           'ACTIVE_ON'  => $activeON,
                           'ACTIVE_OFF' => $activeOFF,
                           'ACTION'     => 'modify',
                           'USER_ID'    => $user_id
                           )
                    );

         return TRUE;
      }

      function add_screen(&$t)
      {
         $userObj = new User($this->dbi);
         $userTypes = $userObj->getUserTypeList();

         $t->set_block('mainBlock','typeBlock', 'tblock');

         $chosen = '';

         while(list($tid, $typeName) = each($userTypes))
         {
             $t->set_var( array(
                                'TYPE_ID'   => $tid,
                                'USER_TYPE' => $typeName,
                                'CHOSEN'    => $chosen
                               )
                        );

             $t->parse('tblock', 'typeBlock', true);
         }

         $fields = $userObj->getUserFieldList();

         foreach ($fields as $f)
         {
            $t->set_var($f, null);
         }

         $t->set_var('ACTIVE_ON', 'selected');
         $t->set_var('ACTIVE_OFF', null);
         $t->set_var('ACTION', 'add');

         return TRUE;

      }

      function checkPassword($pwd1, $pwd2)
      {

          global $MIN_PASSWORD_SIZE, $DUMMY_PASSWD;

          $this->emptyError($pwd1, 'PASSWORD1_MISSING');
          $this->emptyError($pwd2, 'PASSWORD2_MISSING');

          if (strcmp($pwd1, $pwd2))
          {
             $this->alert('PASSWORD_MISMATCH');

          } else if (strlen($pwd1) < $MIN_PASSWORD_SIZE) {

             $this->alert('INVALID_PASSWORD');

          }

      }

      function checkInput()
      {
          $username = $this->getRequestField('username'); 
          $password1 = $this->getRequestField('password1'); 
          $password2 = $this->getRequestField('password2');
          $user_type = $this->getRequestField('user_type');

          $this->emptyError($username, 'USERNAME_MISSING');
          $this->emptyError($user_type, 'USER_TYPE_MISSING');
          $this->checkPassword($password1, $password2);

      }

      function authorize()
      {
         global $ADMINISTRATIVE_USER;

         $userObj = new User($this->dbi, $this->getUID());

         $type =  $userObj->getTYPE();

         return ($type == $ADMINISTRATIVE_USER) ? TRUE : FALSE;

      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $APP_DB_URL;

   $thisApp = new userManagerApp(
                             array( 'app_name'    => $APPLICATION_NAME,
                                   'app_version'  => '1.0.0',
                                   'app_type'     => 'WEB',
                                   'app_db_url'   => $APP_DB_URL,
                                   'app_auto_authorize' => FALSE,
                                   'app_auto_connect' => TRUE,
                                   'app_auto_chk_session' => TRUE,
                                   'app_debugger' => $ON
                                   )
                            );

   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>