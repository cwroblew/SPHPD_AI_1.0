#!/usr/bin/php -q
<?php

   require_once('reminder.conf');

   define(USER_FILE_MISSING, 1);

   $userList = getUsers(PASSWD_FILE);

   foreach($userList as $userName => $homeDir)
   {
      doRemind(USER_REMINDER_DIR, 
               USER_REMINDER_FILE,
              $userName, 
              $homeDir);
   }

   exit;

   function doRemind($userDir = null,
                     $userFile = null,
                     $userName = null, 
                     $homeDir = null)
   {
       $userReminderDir = sprintf("%s/%s", $homeDir, $userDir);
       $userReminderFile = sprintf("%s/%s", $userReminderDir, $userFile);
       $userReminderLogFile = sprintf("%s/%s.log", $userReminderDir, $userName);

       $logEntries = array();

       if (!file_exists($userReminderFile)) 
       {
           return USER_FILE_MISSING;
       }

       if (DEBUG) echo "Processing reminders for $userName ...\n";
       if (DEBUG) echo "Reminder File $userReminderFile \n";
       $mailings =  getRemindersForToday(file($userReminderFile), 
                                         $userReminderLogFile
                                         );

       foreach ($mailings as $mail)
       {
          $mail = sprintf("%s/%s", $userReminderDir, $mail);

          if (file_exists($mail))
          {
             doMail($mail, $userName);

          } else 
          {
             array_push($logEntries, "cannot find or open $mail");
          }
       }

       writeLog($userReminderLogFile, $logEntries);

   }

   function doMail($file = null, $user = null)
   {
       $lines = file($file);

       $today = date('M-d-Y h:i:s A');

       $contentTypeSet = FALSE;

       $message = array();

       $headers = array("X-Mailer: " . $GLOBALS['XMAILER'] . "\r\n");

       $to = $user;

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
     
       $subject = preg_replace('/<%TODAY%>/i', $today, $subject);

       $body = implode('', $message);
       $body = preg_replace('/<%TODAY%>/i', $today, $body);

       $headerStr = implode('', $headers);

       if (DEBUG) echo "Sending mail to: $to (subject: $subject)\n";
       mail($to, $subject, $body, $headerStr);
   }

   function getRemindersForToday($list = null, $logFile = null)
   {

      $reminders = array();
      $logEntries = array();

      // Get today's date
      $thisMonth = date('M');
      $thisMM = date('m');

      $thisDay = strtolower(date('D'));
      $thisDD = date('d');

      $MMDD = sprintf("%02d-%02d", $thisMM, $thisDD);

      $lineNumber = 0;

      // Parse each line in the user's reminder file
      foreach ($list as $line)
      {
         
         // Count line number (needed for error reporting)
         $lineNumber++;

         // Ignore lines starting with # as comments
         if (preg_match('/^#/', $line)) continue;

         // Ignore lines that are blank
         $line = ltrim($line);
         if (preg_match('/^$/', $line)) continue;

         $line = substr($line,0, strlen($line)-1);
         list ($type,$when,$what) = explode(':', $line);

         if (preg_match('/daily/i', $type))
         {
            // daily reminders have only 2 parts daily:file
            // so $when will have what we want in $what

            // Daily reminder
            array_push($reminders, $when);
         } 
         else if ( preg_match('/weekly/i', $type) && 
                   !strcmp($thisDay, strtolower($when)) &&
                   !empty($what)
                 )
         {
            // Weekly reminder
            array_push($reminders, $what);
         }
         else if ( preg_match('/monthly/i', $type) &&  
                   ($thisDD == $when) &&
                   !empty($what)
                 )
         {
            // Monthly reminder
            array_push($reminders, $what);
         } 
         else if ( preg_match('/yearly/i', $type) &&  
                   (!strcmp($MMDD, $when)) &&
                   !empty($what)
                 )
         {
            // Yearly reminder
            array_push($reminders, $what);

         }
         else if (empty($what))
         {
            array_push($logEntries, "error in line $lineNumber ($line)");
         }
      }
      
       // Write log entries
       writeLog($logFile, $logEntries);

       // Remove duplicates from reminder list
       return array_values(array_unique($reminders));
   }

   function writeLog($logFile = null, $entries = null)
   {

       if (count($entries) <1) return FALSE;

       $logFD = fopen($logFile, 'a+');

       $today = date('M-d-Y h:i:s A');

       if (! $logFD) return FALSE;

       foreach ($entries as $logRecord)
       {
          fputs($logFD, "$today: $logRecord\n");
       }

       fclose($logFD);

       return TRUE;
   }

   function getUsers($userFile = null)
   {
       $users = array();
       
       if (!file_exists($userFile))
       {
          return $users;
       }

       // For each line in the file
       // create a entry in users array
       // as:  $users[username] = home_dir
       foreach ( file($userFile) as $line)
       {
          $userInfo = explode(':',$line);
           
          $users[$userInfo[0]] = $userInfo[5];
       }

       return $users;
   }

?>
