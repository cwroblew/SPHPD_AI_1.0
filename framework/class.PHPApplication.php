<?php
/*
*
* PHPApplication class
*
* @author <php@evoknow.com>
* @access public
*
* Version 1.0.1
*/


  if (defined("DEBUGGER_LOADED") && ! empty($DEBUGGER_CLASS))
  {
      include_once $DEBUGGER_CLASS;
  }

  //require_once 'lib.session_handler.php';


  class PHPApplication {

     function PHPApplication($param = null)
     {

        global $ON, $OFF, $TEMPLATE_DIR;

        global $MESSAGES, $DEFAULT_LANGUAGE,
               $REL_APP_PATH,
               $REL_TEMPLATE_DIR;

        // initialize application
        $this->app_name 	= $this->setDefault($param['app_name'], null);
        $this->app_version 	= $this->setDefault($param['app_version'], null);
        $this->app_type 	= $this->setDefault($param['app_type'], null);
        $this->app_db_url 	= $this->setDefault($param['app_db_url'], null);
        $this->debug_mode	= $this->setDefault($param['app_debugger'], null);

        $this->auto_connect 	= $this->setDefault($param['app_auto_connect'], TRUE);
        $this->auto_chk_session	= $this->setDefault($param['app_auto_chk_session'], TRUE);
        $this->auto_authorize 	= $this->setDefault($param['app_auto_authorize'], TRUE);
       
        $this->session_ok       = $this->setDefault($param['app_auto_authorize'], FALSE);

        $this->error		= array();
        $this->authorized	= FALSE;
        $this->language         = $DEFAULT_LANGUAGE;
        $this->base_url         = sprintf("%s%s", $this->get_server(), $REL_TEMPLATE_DIR);
        $this->app_path         = $REL_APP_PATH;
        $this->template_dir	= $TEMPLATE_DIR;
        $this->messages	        = $MESSAGES;
//print_r ($param);
        // If debuggger is ON then create a debugger object

        if (defined("DEBUGGER_LOADED") && $this->debug_mode == $ON)
        {
            if (empty($param['debug_color']))
            {
                $param['debug_color'] = 'red';
            }
            $this->debugger = new Debugger(array('color' 	=> $param['debug_color'],
                                                 'prefix' 	=> $this->app_name,
                                                 'buffer'	=> $OFF));
        } 

        // load error handler
        $this->has_error = null;

        $this->set_error_handler();

        // start session

        if (strstr($this->get_type(), 'WEB'))
        {

            session_start();

            $this->user_id          = (! empty($_SESSION["SESSION_USER_ID"]))  ? $_SESSION["SESSION_USER_ID"] : null;
            $this->user_name        = (! empty($_SESSION["SESSION_USERNAME"])) ? $_SESSION["SESSION_USERNAME"]: null;;
            $this->user_email       = (! empty($_SESSION["SESSION_USERNAME"])) ? $_SESSION["SESSION_USERNAME"]: null;;
            $this->set_url();
			if ($this->app_name != "LOGIN")
{
//echo "Session ID: ". session_id() ."ID $this->user_id App $this->app_name - User $this->user_name";
}
            if ($this->auto_chk_session) $this->check_session();

            if (! empty($this->app_db_url) && $this->auto_connect && ! $this->connect())
            {
                $this->alert('APP_FAILED');

            }

            if ($this->auto_authorize && ! $this->authorize())
            {
               $this->alert('UNAUTHORIZED_ACCESS');
            }

         }
     }

     function getEMAIL()
     {
        return $this->user_email;
     }

     function getNAME()
     {
         list($name, $host) = explode('@', $this->getEMAIL());
         return ucwords($name);
     }

     function check_session()
     {
        if ($this->session_ok == TRUE)
        {
            return TRUE;
        }

        if (!empty($this->user_name))
        {

            $this->session_ok = TRUE;

        } else {

            $this->session_ok = FALSE;

            $this->reauthenticate();
        }

        return $this->session_ok;
     }

     function reauthenticate()
     {
         global $AUTHENTICATION_URL;                  
         header("Location: $AUTHENTICATION_URL?url=$this->self_url");         
         
     }

     function getBaseURL()
     {
        return $this->base_url;
     }

     function get_server()
     {
        $this->set_url();
        return $this->server;
     }

     function getAppPath()
     {
        return $this->app_path;
     }

     function getFQAP()
     {
        // get fully qualified application path

        return sprintf("%s%s",$this->server, $this->app_path);
     }

     function getFQAN($thisApp = null)
     {
        return sprintf("%s/%s", $this->getFQAP(), $thisApp);
     }

     function getTemplateDir()
     {
        return $this->template_dir;
     }

     function set_url()
     {

         $row_protocol = $this->getEnvironment('SERVER_PROTOCOL');

         $port  = $this->getEnvironment('SERVER_PORT');

         if ($port == 80)
         {
             $port = null;
         } else {
             $port = ':' . $port;
         }

         $protocol = strtolower(substr($row_protocol,0, strpos($row_protocol,'/')));

         $this->server = sprintf("%s://%s%s", 
                                $protocol, 
                                $this->getEnvironment('HTTP_HOST'), 
                                $port);

         $this->self_url = sprintf("%s://%s%s%s", $protocol,
                                   $this->getEnvironment('HTTP_HOST'),
                                   $port,
                                   $this->getEnvironment('REQUEST_URI'));


     }

     function getServer()
     {
     	return $this->server;
     }

     function terminate()
     {
        if (isset($this->dbi))
        { 
	   if ($this->dbi->connected) {
             $this->dbi->disconnect();
           }
        }   
     	//Asif Changed
     	session_destroy();
        exit;
     }

     function authorize($username = null)
     {

         // override this method
         return FALSE;

     }

     function set_error_handler()
     {
        // create error handler
        if (defined("ERROR_HANDLER_LOADED"))
	    $this->errHandler = new ErrorHandler(
                                array ('name' => $this->app_name));
     }

     function getErrorMessage($code)
     {
        return $this->errHandler->error_message[$code];
     }

     function show_popup($code)
     {
	return $this->errHandler->alert($code, 0);
     }

     function getMessage($code = null, $hash = null)
     {
        $msg = $this->messages[$this->language][$code];

        if (! empty($hash))
        {
            foreach ($hash as $key => $value)
            {
               $key = '/{' . $key . '}/';
               $msg = preg_replace($key, $value, $msg);
            }
        }

        return $msg;
     }

     function alert($code = null, $flag = null)
     {
	return (defined("ERROR_HANDLER_LOADED")) ? 
                $this->errHandler->alert($code, $flag) : false;
     }


     function buffer_debugging()
     {
        global $ON;

        if (defined("DEBUGGER_LOADED") && $this->debug_mode == $ON)
        {
            $this->debugger->set_buffer();
        }
     }

     function dump_debuginfo()
     {
        global $ON;

        if (defined("DEBUGGER_LOADED") && $this->debug_mode == $ON)
        {
            $this->debugger->flush_buffer();
        }
     }

     function debug($msg)
     {
        global $ON;
        if ($this->debug_mode == $ON) {
            $this->debugger->write($msg);
        }
     }

     function run()
     {
        // run the application
        $this->writeln("You need to override this method.");
     }

     function connect($db_url = null)
     {
	
         if (empty($db_url))
         {
            $db_url = $this->app_db_url;
         }

         if (defined('DBI_LOADED') && ! empty($this->app_db_url))
         {
           $this->dbi = new DBI($db_url);
           return $this->dbi->connected;
         }

         return FALSE;

     }

     function disconnect()
     {
         $this->dbi->disconnect();
         $this->dbi->connected = FALSE;

         return $this->dbi->connected;
     }

     function get_error_message($code = null)
     {
        return $this->errHandler->get_error_message($code);

     }

     function show_debugger_banner()
     {
        global $ON;

        if ($this->debug_mode == $ON)
        {
            $this->debugger->print_banner();
        }

     }

     function get_version()
     {
        // return version
        return $this->app_version;
     }

     function get_name()
     {
        // return name
        return $this->app_name;
     }

     function get_type()
     {
        // return type
        return $this->app_type;
     }

     function set_error($err = null)
     {
        // set error condition
        if (isset($err))
        {
           array_push($this->error, $err);
           $this->has_error = TRUE;
           return 1;
        } else {
           return 0;
        }
     }

     function has_error()
     {
        return $this->has_error;
     }

     function reset_error()
     {
        $this->has_error = FALSE;
     }

     function get_error()
     {
        // return error condition
        return array_pop($this->error);
     }

     function get_error_array()
     {
        return $this->error;
     }

     function dump_array($a)
     {
        if (strstr($this->get_type(), 'WEB'))
        {
           echo '<pre>';
           print_r($a);
           echo '</pre>';
        } else {
           print_r($a);
        }


     }

     function dump()
     {
        if (strstr($this->get_type(), 'WEB'))
        {
           echo '<pre>';
           print_r($this);
           echo '</pre>';
        } else {
           print_r($this);
        }


     }

     function checkRequiredFields($fieldType = null, $fieldData = null, $errorCode = null)
     {
         $err = array();

         while(list($field, $func) = each ($fieldType))
         {
            $ok = $this->$func($fieldData[$field]);

            if (! $ok )
            {
               $this->alert($errorCode{$field});
            }

         }
         return $err;
     }

     function number($num = null)
     {
        if (is_array($num))
        {
           foreach ($num as $i)
           {
              if (! is_numeric($i))
              {
              	return 0;

              }

           }

           return 1;

        } else if (is_numeric($num))
        {
            return 1;
        } else {
            return 0;
        }
     }

     function name($name = null)
     {

        if (!strlen($name) || is_numeric($name))
        {
          return 0;
        } else {
          return 1;
        }
     }

     function email($email = null)
     {
        if (strlen($email) < 5 || ! strpos($email,'@'))
        {
            return 0;
        } else {
            return 1;
        }

     }

     function currency($amount = null)
     {
        return 1;
     }

     function month($mm = null)
     {
        if ($mm >=1 && $mm <=12)
        {
           return 1;
        } else {
           return 0;
        }
     }

     // ASIF what is thie method doing in this class???
     function comboOption($optVal = null)
     {

       if ($optVal != 0)
       {
       	return 1;
       }else {
       	return 0;
       }

     }

     function day($day = null)
     {
        if ($day >=1 && $day <=31)
        {
           return 1;
        } else {
           return 0;
        }
     }

     function year($year = null)
     {
        return ($this->number($year));
     }

     function one_zero_flag($flag = null)
     {
        if ($flag == 1 || $flag == 0)
        {
            return 1;

        } else {

            return 1;
        }
     }

     function plain_text($text = null)
     {
        return 1;
     }

     function debug_array($hash = null)
     {
        $this->debugger->debug_array($hash);
     }

     function writeln($msg)
     {
        // print
        global $WWW_NEWLINE;
        global $NEWLINE;
        echo $msg ,(strstr($this->app_type, 'WEB')) ? $WWW_NEWLINE :  $NEWLINE;
     }


     function show_status($msg = null,$returnURL = null)
     {
        global $STATUS_TEMPLATE;
        $template = new Template($this->template_dir);
        $template->set_file('fh', $STATUS_TEMPLATE);
        $template->set_block('fh', 'mainBlock', 'mblock');
        $template->set_var('STATUS_MESSAGE', $msg);

        if (!preg_match('/^http:/', $returnURL) && (!preg_match('/^\//', $returnURL)))
        {
            $appPath = sprintf("%s/%s", $this->app_path, $returnURL);

        } else {

            $appPath = $returnURL;
        }

        $template->set_var('RETURN_URL', $appPath);

        $template->set_var('BASE_URL', $this->base_url);
        $template->parse('mblock', 'mainBlock');
        $template->pparse('output', 'fh');
     }

    function set_escapedVar($hash)
    {
    	while(list($key, $value) = each ($hash))
    	{
    	   $this->escapedVarHash{$key} = preg_replace("/\s/","+",$value);
    	}
    }

    function get_escapedVar($key)
    {
    	return $this->escapedVarHash{$key};
    }

    function setUID($uid = null)
    {
       $this->user_id = $uid;
    }

    function getUID()
    {
       return $this->user_id;
    }
    
    //To Kabir: I added this -- Asif
    function getUserName()
    {
       return $this->user_name;	
    }

    function emptyError($field, $errCode)
    {
      if (empty($field))
      {
         $this->alert($errCode);
      }
    }

    function getRequestField($field, $default = null)
    {
    	return (! empty($_REQUEST[$field] )) ? $_REQUEST[$field] : $default;
    }
    
    function getSessionField($field, $default = null)
    {
    	return (! empty($_SESSION[$field] )) ? $_SESSION[$field] : $default;
    }
    
    
    function setDefault($value, $default)
    {
       return (isset($value)) ? $value : $default;
    }

    function fileextension($filename)
    {
       return substr(basename($filename), strrpos(basename($filename), ".") + 1);
    }

    function outputTemplate(&$t)
    {
       $t->parse('main', 'mainBlock', false);
       return $t->parse('output', 'fh');

    }

    function showScreen($templateFile = null, $func = null, $app_name)
    {

        $menuTemplate = new Template($this->getTemplateDir());

        $this->doCommonTemplateWork($menuTemplate, $templateFile, $app_name);

        if ($func != null)
        {
           $status = $this->$func($menuTemplate);
        }

        if ($status)
        {
            return $this->outputTemplate($menuTemplate);

        } else {

            return null;
        }

    }

    function doCommonTemplateWork(&$t, $templateFile, $app_name)
    {

       $t->set_file('fh', $templateFile);

       $t->set_block('fh','mainBlock', 'main');

       $t->set_var(array(
                         'APP_PATH'     => $this->getAppPath(),
                         'APP_NAME'     => $app_name,
                         'BASE_URL'     => $this->getBaseURL(),
						 'UserName'     => $this->user_name,
						 'Website'      => $this->get_server()
                        )
                   );
    }

  function getEnvironment($key)
  {
      return $_SERVER[$key];
  }

  function showPage($contents = null)
  {
                
      global $THEME_TEMPLATE;
      global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
      global $REL_TEMPLATE_DIR;
      global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
  
      $themeObj = new Theme($this->dbi, null,'home');

      $this->themeObj = $themeObj;
      $this->theme = $themeObj->getUserTheme($this->getUID());
       
      $themeTemplate = new Template($THEME_TEMPLATE_DIR);
  
      $themeTemplate->set_file('fh', $THEME_TEMPLATE[$this->theme]);
      $themeTemplate->set_block('fh', 'mmainBlock', 'mmblock');
      $themeTemplate->set_block('mmainBlock', 'contentBlock', 'cnblock');
      $themeTemplate->set_block('mmainBlock', 'printBlock', 'prnblock');
      $themeTemplate->set_var('printBlock', '&nbsp;');
      $themeTemplate->parse('prnblock', 'printBlock',false);
      $themeTemplate->set_block('mmainBlock', 'pageBlock', 'pblock');
      $themeTemplate->set_var('pblock', null);
      $photoFile = sprintf("%s/photo%003d.jpg",$PHOTO_DIR, $this->getUID());
      $defaultPhoto = sprintf("%s/%s",$REL_PHOTO_DIR,$DEFAULT_PHOTO);
      $userPhoto = sprintf("%s/photo%003d.jpg",$REL_PHOTO_DIR,$this->getUID());
      $photo = file_exists($photoFile) ? $userPhoto : $defaultPhoto;
      
      $themeTemplate->set_var('PHOTO', $photo);
      $themeTemplate->set_var('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
      $themeDir = $THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme]);
      $leftNavigation = $this->themeObj->getLeftNavigation($themeDir);
      $themeTemplate->set_var('LEFT_NAVIGATION', $leftNavigation);
               
      $themeTemplate->set_var('SERVER_NAME', $this->get_server());
      $themeTemplate->set_var('BASE_HREF', $REL_TEMPLATE_DIR);
      $themeTemplate->set_var('CONTENT_BLOCK', $contents);
      $themeTemplate->parse('cnblock', 'contentBlock');
      $themeTemplate->parse('mmblock', 'mmainBlock');
      $themeTemplate->pparse('output', 'fh');
  
  }

}

?>
