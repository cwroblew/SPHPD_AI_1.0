<?php

/*
* CVS ID: $Id$
*/

/*
* Centalizes all error messages.
* Supports internationalization of error messages.
*
* @author EVOKNOW, Inc. <php@evoknow.com>
* @access public
*/
   define('ERROR_HANDLER_LOADED', TRUE);
   
   class ErrorHandler
   {

      function __construct($params = null)
      {

         global $DEFAULT_LANGUAGE;

         $this->language = $DEFAULT_LANGUAGE;

         $this->caller_class = (!empty($params['caller'])) ? $params['caller'] : null;

         $this->error_message = array();

         //error_reporting(E_ERROR | E_WARNING | E_NOTICE);

         $this->load_error_code();

         

      }

      function alert($code = null, $flag = null)
      {
         
         $msg = $this->get_error_message($code);
         if (!strlen($msg))
         {
             $msg = $code;
         }

         if ($flag == null)
         {
            echo "<script>alert('$msg');history.go(-1);</script>";

         } else if (!strcmp($flag,'close')){

            echo "<script>alert('$msg');window.close();</script>";

         } else {

            echo "<script>alert('$msg');</script>";
         }
      }

      function get_error_message($code = null)
      {

        if (isset($code))
        {

            if (is_array($code))
            {
               $out = array();
               foreach ($code as $entry)
               {
                  array_push($out, $this->error_message[$entry]);
               }

               return $out;

            } else {
            	
               return (! empty($this->error_message[$code])) ? $this->error_message[$code] : null;
               
            }

        } else {
        	
            return (! empty($this->error_message['MISSING'])) ? $this->error_message['MISSING'] : null;
        }

      }

      function load_error_code()
      {
         global $ERRORS;

         if (empty($ERRORS[$this->language]))
         {
            return FALSE;
         }

         while (list($key, $value) = each ($ERRORS[$this->language])) {
            $this->error_message[$key] = $value;
         }

         return TRUE;
      }
   }

?>
