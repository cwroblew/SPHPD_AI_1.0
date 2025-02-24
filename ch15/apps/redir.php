<?php

   require_once 'ecampaign.conf';

   require_once $ECAMPAIGN_TRACK_CLASS;
   require_once $ECAMPAIGN_URL_CLASS;

   class redir extends PHPApplication {

      function run()
      {
         
         $chk = $this->getRequestField('chk');
         $mode = $this->getRequestField('mode');
         global $ECAMPAIGN_DB_URL, $HOME_URL;

         if ($this->connect($ECAMPAIGN_DB_URL) == FALSE)
         {
            $this->alert('APP_FAILED');
            exit();
         }

         if( !strcmp($mode, 'test'))
         {
            $this->redirectTest();
         }

         if ( $chk != $this->computeCheckSum())
         {
            $this->alert('INVALID_URL_REQUEST');
            header("Location: $HOME_URL");

         } else{

            $this->keepTrackAndRedirect();
         }
      }

      function computeCheckSum()
      {
         global $SECRET;
         
         $u = $this->getRequestField('u');
         $uid = $this->getRequestField('uid');
         $c = $this->getRequestField('c');

         return ($u << 8) + ($uid << 8) +  ($c << 4) + $SECRET;
      }


      function keepTrackAndRedirect()
      {
         global $HOME_URL;
         
         $u = $this->getRequestField('u');
         $uid = $this->getRequestField('uid');
         $c = $this->getRequestField('c');

         $trackObj = new EcampaignTrack($this->dbi);

         $status = $trackObj->storeTrack($uid, $c, $u);

         $urlObj = new EcampaignURL($this->dbi);

         $url = $urlObj->getURL($u);

         header("Location: $url");

         $this->debug("Location: $url");

      }


      function redirectTest()
      {
         $u = $this->getRequestField('u');

         $urlObj = new EcampaignURL($this->dbi);

         $url = $urlObj->getURL($u);

         header("Location: $url");
      }


   }//class

   $thisApp = new redir(
                                   array('app_name'              => $APPLICATION_NAME,
                                         'app_version'           => '1.0.0',
                                         'app_type'              => 'WEB',
                                         'app_db_url'            => $ECAMPAIGN_DB_URL,
                                         'app_auto_connect'      => FALSE,
                                         'app_auto_authorize'    => FALSE,
                                         'app_auto_chk_session'  => FALSE,
                                         'app_debugger'          => $OFF
                                        )
                                   );
   $thisApp->buffer_debugging();
   $thisApp->run();
   $thisApp->dump_debuginfo();

?>
