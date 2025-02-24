#!/usr/bin/php -q
<?php

   require_once('loadmonitor.conf');
   require_once('class.Linux.inc.php');
   require_once('common_functions.php');

   $alertInfo = array();

   $system = new sysinfo;
   $alertInfo['/<%HOST%>/']    = $system->chostname();
   $alertInfo['/<%IP_ADDR%>/'] = $system->ip_addr();
   $alertInfo['/<%KERNEL%>/']  = $system->kernel();
   $alertInfo['/<%TODAY%>/']   = date('M-d-Y h:i:s A');

   $loadInfo = $system->loadavg();

   $load0  = $loadInfo[0];
   $load5  = $loadInfo[1];
   $load15 = $loadInfo[2];
   $alertInfo['/<%LOAD%>/'] = "Now: $load0  Last 5 Min: $load5 Last 15 Min: $load15";

   $highestAlertRange = 0;
   $highestAlert = null;

   foreach ($ALERT_CONDITIONS as $alertType)
   {
      $alertRange = $ALERT[$alertType];

      if (DEBUG) echo "Alert: $alertRange => Current $load0 $load5 $load15\n";

      if ( 
           ($alertRange <= $load0) &&
           ($alertRange <= $load5) &&
           ($alertRange <= $load15)
         )
      {
          if (DEBUG) echo "Alert: $alertType ($alertRange) as load ".
                          "for last 15 min till now is $load15 $load0 \n";

          if ($alertRange > $highestAlertRange)
          {
              $highestAlertRange =  $alertRange;
              $highestAlert      =  $alertType;
          }
      }
   }

   if ($highestAlert != null)
   {
      if (DEBUG) echo "Highest alert $highestAlert \n";

      $alertInfo['/<%ALERT%>/'] = $highestAlert;
      $ps =execute_program($PS_BIN, $PS_OPT);
      $alertInfo['/<%PSAUX%>/'] = $ps;

      // Find out if last mail sent was within mail frequency range
      // or not, if not send mail
      if (isOKtoSendMail($MAIL_CONTROL_FILE, $MAIL_FREQUENCY))
      {
         sendAlert($alertInfo);
      }
   }

   exit;

   function isOKtoSendMail($ctrlFile = null, $interval = null)
   {
      $now = time();
      if (DEBUG) echo "Now: " . date('M-d-Y h:i:s A', $now) . "\n";

      if (file_exists($ctrlFile)) 
      {
         // Read file
         $lastTime = file($ctrlFile);
         if (DEBUG) echo "Last time mail sent: " . date('M-d-Y h:i:s A', $lastTime[0]) . "\n";

      } else {
         // If control file does not exist
         // Create one and yes we can send mail
         if (DEBUG) echo "Create new control file.\n";
         writeControlFile($ctrlFile);
         return TRUE;
      }

      // If current time - last time is greater than
      // or equal to the mail interval, we can send mail
      if ($now - $lastTime[0] >= $interval)
      {
         // Update flie
         if (DEBUG) echo "$now - $lastTime[0] => $interval\n";
         writeControlFile($ctrlFile);
         return TRUE;
      }

      // No cannot send mail as we already did not too late ago
      return FALSE;
   }

   function writeControlFile($file = null)
   {
      $now = time();
       $fp = fopen($file, 'w');
       if ($fp)
       { 
          if (DEBUG) echo "Writing control $file: $now\n";
          fputs($fp, $now);
          fclose($fp);
          return TRUE;
       } else  {

          echo "Error: could not create control file $file \n";
       }
        

       return FALSE;
   }

   function sendAlert($info = null)
   {
       $lines = file($GLOBALS['MAIL_TEMPLATE']);

       $contentTypeSet = FALSE;

       $message = array();

       $headers = array();

       foreach ($lines as $str)
       {
          $index++;
          if (preg_match('/To:\s*(.+)/i', $str, $match))
          {
              $to = $match[1];
          } 
          else if (preg_match('/From:\s*(.+)/i', $str, $match))
          {
              array_push($headers, "From: $match[1] \r\n");
          } 
          else if (preg_match('/Subject:\s*(.+)/i', $str, $match))
          {
              $subject = $match[1];
          } 
          else if (preg_match('/^CC:\s*(.+)/i', $str, $match))
          {
              array_push($headers, "Cc: $match[1] \r\n");
          } 
          else if (preg_match('/Bcc:\s*(.+)/i', $str, $match))
          {
              array_push($headers, "Bcc: $match[1] \r\n");
          } 
          else if (preg_match('/Content-Type:\s*(.+)/i', $str, $match))
          {
              if (preg_match('/html/', $match[1]))
              {
                  array_push($headers, "Content-Type: text/html\r\n");
              } else {
                  array_push($headers, "Content-Type: text/plain\n");
              }

              $contentTypeSet = TRUE;
          } 
          else if (preg_match('/MIME-Version:\s*(.+)/i', $str, $match))
          {
              array_push($headers, "MIME-Version: $match[1] \r\n");

          } else {
              array_push($message, $str);
          }
       }

       if (! $contentTypeSet) array_push($headers, "Content-Type: text/plain\r\n");
     
       $body = implode('', $message);

       $search  = array_keys($info);
       $replace = array_values($info);
       $body = preg_replace($search, $replace, $body);
       $subject = preg_replace($search, $replace, $subject);

       $headerStr = implode('', $headers);

       if (DEBUG) echo "Sending mail to: $to (subject: $subject)\n";

       mail($to, $subject, $body, $headerStr);
   }


?>
