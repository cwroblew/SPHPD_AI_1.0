<?php


/*
* CVS ID: $Id$
*/
   define('DEBUGGER_LOADED', TRUE);        
   class Debugger {

      var $myTextColor = 'red';

      function __construct($params = null)
      {

	 // Debugger constructor method
         $this->color  = $params['color'];
         $this->prefix = $params['prefix'];
         $this->line = 0;
         $this->buffer_str = null;
         $this->buffer = $params['buffer'];
         $this->banner_printed = FALSE;



      }
      
      
      function print_banner()
      {

        if ($this->banner_printed == TRUE)
        {
            return 0;
        }

        $out = "<br><br><font color='$this->myTextColor'>" .
               "<strong>Debugger started for $this->prefix</strong>" .
               "</font><br><hr>";

	if ($this->buffer == TRUE ){
           $this->buffer_str .= $out;
        } else {
           echo $out;
           $this->banner_printed = TRUE;
        }

        return 1;
      }

      function write($msg)
      {
         $out = sprintf("<font color='%s'>%03d &nbsp;</font>" . 
                        "<font color=%s>%s</font><br>\n",
         		$this->myTextColor,
         		$this->line++,
         		$this->color,
         		$msg);


         if ($this->buffer == TRUE)
         {
             $this->buffer_str .= $out;
         } else {
             echo $out;
         }
      }

     function debug_array($hash = null)
     {
        while(list($k, $v) = each($hash))
        {
          $this->write("$k = $v");
        }
     }

     function set_buffer()
     {
        $this->buffer = TRUE;
     }

     function reset_buffer()
     {
        $this->buffer = FALSE;
        $this->buffer_str = null;
     }


     function flush_buffer()
     {
        $this->buffer = FALSE;
        $this->print_banner();
        echo $this->buffer_str;
     }

   }

?>
