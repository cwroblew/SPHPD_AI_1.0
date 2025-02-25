<?php

global $APPLICATION_NAME, $ON;
require_once "login.conf.php";
   require_once "login.errors.php";

   /*
      Session variables must be defined before session_start()
      method is called
   */

   $count = 0;

   class loginApp extends PHPApplication {

      function run()
      {
         global $MIN_USERNAME_SIZE, $MIN_PASSWORD_SIZE, $MAX_ATTEMPTS;
         global $WARNING_URL, $APP_MENU;
         
         
         $email = $this->getRequestField('email');
		 $username = $this->getRequestField('username');
         $password = $this->getRequestField('password')	;
         $url = $this->getRequestField('url');
		 //echo "Email: $email User: $username Pass: $password URL: $url"; 
			$this->debug("Email: $email User: $username Pass: $password");

         $emailLen = strlen($email);
		 $usernameLen = strlen ($username);
         $passwdLen = strlen($password);

         $this->debug("Login attempts : " . $this->getSessionField('SESSION_ATTEMPTS'));
         
         
         if ($this->is_authenticated())
         {
             // return to caller HTTP_REFERRER
             $this->debug("User already authenticated.");
             $this->debug("Redirecting to $url.");
             $url = (isset($url)) ? $url : $this->getServer();             
             header("Location: $url");

         } else if ((strlen($email) < $MIN_USERNAME_SIZE && $usernameLen < $MIN_USERNAME_SIZE) ||
                  strlen($password) < $MIN_PASSWORD_SIZE) {
             // display the login interface
             $this->debug("Invalid Email or password.");
             $this->display_login();
             $_SESSION["SESSION_ATTEMPTS"] = $this->getSessionField("SESSION_ATTEMPTS") + 1;

         } else {

			if ($emailLen > 0)
			{
             // Prepare the email with domain name
             if (!strpos($email, '@'))
             {
                 $hostname = explode('.', $_SERVER['SERVER_NAME']); 

                 if (sizeof($hostname) > 1)
                 {
                     $email .= '@' . $hostname[1] . '.' . $hostname[2];
                 }
             }
			}
             // authenticate user

             $this->debug("Authenticate user: $username, email: $email with password $password");

             if ($this->authenticate($username, $email, $password))
             {
                 $this->debug("User is successfully authenticated.");
                 $_SESSION["SESSION_USERNAME"] = ($email=null?$email:$username);
                 $_SESSION["SESSION_PASSWORD"] = $password;
                 $_SESSION["SESSION_USER_ID"]  = $this->getUID();

                 if (empty($url))
                 {
                     $url = $APP_MENU;
                 }

                 // Log user activity
             
                 $thisUser = new User($this->dbi, $this->getUID());
                 $thisUser->logActivity(LOGIN);

                 $this->debug("Location $url");                 
// echo "Location $url"; exit;
                header("Location: $url");
                 $this->debug("Redirect user to caller application at url = $url.");

             } else {
             	 $this->debug("User failed authentication.");
                 $this->display_login();
                 $_SESSION["SESSION_ATTEMPTS"] = $this->getSessionField("SESSION_ATTEMPTS") + 1;
             }
         }
      }

      function warn()
      {
          global $WARNING_URL;
          $this->debug("Came to warn the user $WARNING_URL");
          header("Location: $WARNING_URL");
      }

      function display_login()
      {

          global $TEMPLATE_DIR;
          global $LOGIN_TEMPLATE;
          global $MAX_ATTEMPTS;
          global $REL_TEMPLATE_DIR;
          global $email, $url;
          global $PHP_SELF,
                 $FORGOTTEN_PASSWORD_APP;


          $url = $this->getRequestField('url');
           
          if ($this->getSessionField("SESSION_ATTEMPTS") > $MAX_ATTEMPTS)
          {
             $this->warn();
          }

          $this->debug("Display login dialog box");
          $template = new Template($TEMPLATE_DIR);
          $template->set_file('fh', $LOGIN_TEMPLATE);
          $template->set_block('fh', "mainBlock");
          $template->set_var('SELF_PATH', $PHP_SELF);
          $template->set_var('ATTEMPT', $this->getSessionField("SESSION_ATTEMPTS"));
          $template->set_var('TODAY', date("M-d-Y h:i:s a"));
          $template->set_var('TODAY_TS', time());
          $template->set_var('USERNAME', $email);
          $template->set_var('REDIRECT_URL', $url);
          $template->set_var('FORGOTTEN_PASSWORD_APP', $FORGOTTEN_PASSWORD_APP);
          $template->parse("fh", "mainBlock");
          $template->set_var('BASE_URL', sprintf("%s",$this->base_url));
          $template->pparse("output", "fh");
          return 1;
      }


      function is_authenticated()
      {
          return (!empty($_SESSION["SESSION_USERNAME"])) ? TRUE : FALSE;
      }

      function authenticate($user = null, $email = null, $passwd = null)
      {
          $authObj = new Authentication($user, $email, $passwd, $this->app_db_url);

          if ($authObj->authenticate())
          {
              $uid = $authObj->getUID();
              $this->debug("Setting user id to $uid");
              $this->setUID($uid);
              return TRUE;
          }

          return FALSE;
      }

   }

   global $AUTH_DB_URL;

   $thisApp = new loginApp(
                            array(
                                  'app_name'             => $APPLICATION_NAME,
                                  'app_version'          => '1.0.0',
                                  'app_type'             => 'WEB',
                                  'app_db_url'           => $AUTH_DB_URL,
                                  'app_auto_authorize'   => FALSE,
                                  'app_auto_chk_session' => FALSE,
                                  'app_auto_connect'     => TRUE,
                                  'app_debugger'         => $ON
                                 )
                          );

   $thisApp->buffer_debugging();
   $thisApp->debug("This is $thisApp->app_name application");
//$thisApp->debug("User: " . (!empty ($_REQUEST["username"])? $_REQUEST["username"]: "") . " Pass: " . (!empty ($_REQUEST["password"])? $_REQUEST["password"]: ""));
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
