#!/usr/bin/php -q
<?php

   require_once('hdmonitor.conf');
   require_once('class.Linux.inc.php');
   require_once('common_functions.php');

   $alertInfo = array();

   $system = new sysinfo;
   $alertInfo['/<%HOST%>/']    = $system->chostname();
   $alertInfo['/<%IP_ADDR%>/'] = $system->ip_addr();
   $alertInfo['/<%KERNEL%>/']  = $system->kernel();
   $alertInfo['/<%TODAY%>/']   = date('M-d-Y h:i:s A');

   $diskInfo = getDiskInfo($system->filesystems());

   $alert = 0;

   foreach ($diskInfo as $mount => $currentPercent)
   {
     if (!empty($MAXSIZE[$mount]) &&
         $MAXSIZE[$mount] <= $currentPercent
        )
     {
        $alert++;
        $alertInfo['/<%DISK_STATUS%>/'] .= "Filesystem: $mount exceeds limit. " .
                             "Currently used: $currentPercent%\n";
       if (DEBUG) echo "Filesystem: $mount exceeds limits.\n";
     }
   }

   if ($alert) sendAlert($alertInfo);

   exit;

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


   function getDiskInfo($fs = null)
   {
      $info = array();

      foreach($fs as $disk)
      {
         $mountPoint = $disk['mount'];
         $percent = $disk['percent'];

         // remove % sign
         $info[$mountPoint] = substr($percent, 0, strlen($percent) -1);

      }

      return $info;
   }
?>
