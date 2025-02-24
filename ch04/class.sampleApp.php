<?php

   class sampleApp  extends PHPApplication {

      function run()
      {
          // At this point user is authorized
          // Start business logic driver
          $this->debug("Real application code starts here.");
          $this->debug("Call application specific function here.");
          $this->doSomething();
     }

     function authorize($email = null)
     {
         return TRUE;
     }

     function doSomething()
     {
         global $MESSAGES, $DEFAULT_LANGUAGE;
         $this->debug("Started doSomething()");
         echo $MESSAGES[$DEFAULT_LANGUAGE]['DEFAULT_MSG'];
         $this->debug("Finished doSomething()");
     }
   } // Class  
   
?>
