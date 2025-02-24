#!/usr/bin/php -q
<?php

  /*
 
  SPAMFREE Version 1.0
  This script can be run as a cron job to connect to
  multiple POP3 mail boxes to automatically remove
  SPAMs using configurable spam filter rules which
  are specified in spamfree.conf file.
  
  Currently, this script supports:
  + allow/deny email list per mail box account
  + common deny email list 
  + deny subject lines per mail box account
  + common deny subject lines
  
  common rules can be turned on or off per
  mail box
  
  email addresses can be regular expressions
  subject lines can be regular expressions 
  
  */
        
  require_once("pop3.php");
  require_once("spamfree.conf");

  $apop  = 0;
  $logMsgs = array();
  
  // For each POP account do SPAM processing
  foreach($POP_ACCOUNTS as $account => $popInfo)
  {     
     if (DEBUG) echo "Working on $account account\n";     
     if (LOGGING) push_log("Processing $account POP3 account.", 2);    
     
     // Extract POP account information
     list($popUser, $popPasswd, $popServer) = explode(':',$popInfo);
     
     // Now process the current POP account
     $status = checkAccount($account, $popUser, $popPasswd, $popServer);
     
     if (LOGGING) 
     {  
     	$success = writeLog($account, &$logMsgs[]);
     	if (! $success)
     	{
     	   if (DEBUG) echo "Warning! log file could not be written.\n";
     	   echo "Warning! log file could not be written.\n";
        }
     }
  }
  
  exit;

  function checkAccount($account = null, 
                        $popUser = null, 
                        $popPassword= null, 
                        $popServer = null)
  {
     
     // Create a POP3 class object
     $mbox=new pop3_class;
     
     // Set POP3 mail server
     $mbox->hostname = $popServer;
     
     // Initialize SPAM counters
     $junkBytes = 0;
     $junkMsgCnt = 0;
        
     // If connection to POP3 server cannot be opened, return false   
     if(($error=$mbox->Open())!= "")
     {
        if (DEBUG) echo "Connection error: $error\n";        
        if (LOGGING) push_log($error, 1);    
        return FALSE;
     }

     if (DEBUG) echo "Connected to the POP3 server $mbox->hostname.\n";     
     if (LOGGING) push_log("connected to POP3 server $mbox->hostname.", 2);    
     
     // Authenticate as given POP3 user
     if(($error=$mbox->Login($popUser,$popPassword,$apop))!= "")
     {
        if (DEBUG) echo "Login error: $error\n";
        
        if (LOGGING) push_log($error,1);    
        return FALSE;
     }

     if (DEBUG) echo "User: $popUser logged in.\n";     
     if (LOGGING) push_log("$popUser login OK",2);     
     
     // Get mailbox stats
     if(($error=$mbox->Statistics(&$messages,&$size))=="")
     {
       if (DEBUG) 
           echo "Total $messages mail ($size bytes) in mail box.\n";       
           
       if (LOGGING) 
           push_log("Total $messages mail of $size bytes in mail box.",2);


       // Get a list of messages in the mail box
       $msgList=$mbox->ListMessages("",0);

       // If a mail list is returned then process each message
       // in the list by applying SPAM filter rules
       if(GetType($msgList)=="array")
       {
           reset($msgList);
           // For each msg get the msg ID and size
           foreach($msgList as $msgID => $msgSize)
           {
               if (DEBUG) 
                  echo "Message: $msgID - $msgSize bytes.\n";
               
               if (LOGGING) 
                  push_log("Message $msgID is $msgSize bytes.",2);
               
               // Get the current msg headers, body
               if(($error=$mbox->RetrieveMessage($msgID ,
                                                 &$headers, 
                                                 &$body, 
                                                 -1)) =="")
               {
                  
                  // Apply spam filters to see if we need to remove this msg
                  $deleteOK = applySpamFilter($account, 
                                              &$headers, 
                                              &$body);
               
                  // If we need to remove it, delete the msg
                  // and increment junk counters as appropriate
                  if($deleteOK && ($error=$mbox->DeleteMessage($msgID))=="")
                  {
                    if (DEBUG) echo "Marked message $msgID for deletion.\n";
                    
                    if (LOGGING) 
                        push_log("Message $msgID is marked for deletion.",2);
                        
                    $junkBytes += $msgSize;
                    $junkMsgCnt++;
                  }
               }
          }
       }
      
       // Now close the connection if no previous errors
       // were encountered.
       if($error=="" && ($error=$mbox->Close())=="") 
       {
         if (DEBUG) 
         { 
            echo "Removed $junkMsgs messages, $junkBytes bytes.\n";
            echo "Disconnected from the POP3 server $mbox->hostname.\n";
         }
                     
         if (LOGGING) 
             push_log("Total $junkMsgs deleted ($junkBytes bytes).",1);
         
       }
        else 
       {
         echo "Error: $error\n";
         if (LOGGING) push_log($error,1);
       }
       
     }
            
     return TRUE;
  }

  function push_log($msg = null, $level)
  {
    global $logMsgs;
    
    if (LOG_LEVEL >= $level)
    {
       array_push($logMsgs, $msg);
       if (DEBUG) echo "LOG => $msg \n";
    }
  }
  
  function writeLog($account = null, $msgs = null)
  {
     
     global $logMsgs;
     
     print_r($logMsgs);
     
     // Create FQPN of the log file
     $logFile = sprintf("%s/%s.log", LOG_DIR, $account);
     if (DEBUG) echo "Log file $logFile\n";
     
     // Open the log file in append mode
     $fp = fopen($logFile, 'a+');
     
     
     // If a file handler is created for the log file
     // write current entries to it.
     if ($fp)
     {
     
        // Get Date
        $today = date('M-d-Y h:i:s A', time());	
     	foreach ($logMsgs as $line)
     	{
     	   fputs($fp, "$today [$account] $line\n");
     	}
     	
     	// Close the file handle to the log file.
     	fclose($fp);
     	
     	return TRUE;
     }
     
     return FALSE;
  }
  
  function applySpamFilter($account = null, $headers = null, $body = null)
  {
      //print_r($headers);
      //print_r($body);
      
      $fromHeaders = getFromHeaders(&$headers);
      $subject = getSubject(&$headers);
      //print_r($fromHeaders);
      
      $denyList = $GLOBALS['DENY_FROM'][$account];
      $allowList= $GLOBALS['ALLOW_FROM'][$account];     
      
      if(count($denyList) > 0 && ($denyFrom = inList($denyList, $fromHeaders)) != null)
      {
      	// Process account specific DENY list
      	if (DEBUG) echo "[SPAM DETECTED] From: $denyFrom per DENY list\n";
      	return TRUE;
      }

      if(count($allowList) > 0 && ($allowFrom = inList($allowList, $fromHeaders)) != null)
      {
      	// Process account specific ALLOW list
      	return FALSE;
      }
      
      $denySubjects= $GLOBALS['DENY_SUBJECT'][$account];
      
      if($subject != null && ($bannedSubject = inSubject($denySubjects, $subject)) != null)
      {
      	// Process account specific DENY_SUBJECT list
      	if (DEBUG) echo "[SPAM DETECTED] Subject: $bannedSubject DENY_SUBJECT list\n";
      	return TRUE;
      }
      
      // Apply common Spam Filters
      if ($GLOBALS['APPLY_COMMON'][$account])
      {
      	  $denySubjects= $GLOBALS['COMMON_DENY_SUBJECT'];
      	  
          if($subject != null && ($bannedSubject = inSubject($denySubjects, $subject)) != null)
          {
      	      // Process account specific DENY_SUBJECT list
      	      if (DEBUG) echo "[SPAM DETECTED] Subject: $bannedSubject COMMON_DENY_SUBJECT list\n";
      	      return TRUE;
          }      	  
      
         $denyList = $GLOBALS['COMMON_DENY_FROM'];
         if(count($denyList) > 0 && ($denyFrom = inList($denyList, $fromHeaders)) != null)
         {
      	   // Process account specific DENY list
      	   if (DEBUG) echo "[SPAM DETECTED] From: $denyFrom per COMMON_DENY_FROM list\n";
      	   return TRUE;
         }
      
      }
      
      

            
      return FALSE;
  }
  
  function getSubject($headers = null)
  {
     foreach($headers as $thisHeader)
     {
     	if(preg_match('/Subject: (.+)/', $thisHeader, $matches))
     	{     	   
     	   return $matches[1];
        }
     }
     
     return null;
  }
  
  function getFromHeaders($headers = null)
  {
     $headerCnt = count($headers);
     $from = array();
     
     for($i=0;$i<$headerCnt;$i++)
     {
     	foreach($GLOBALS['FROM_HEADERS'] as $fromPattern)
     	{
     	   if (preg_match($fromPattern, $headers[$i]))
     	   {
     	     //if (DEBUG) echo "Found a FROM header => $headers[$i]\n";
     	     $from[] = $headers[$i];     	     
           }
        }
     }

     return $from;
  }
  
  function inSubject($bannedSubjectList= null, $currentSubject = null)
  {
        
     // Search for the given email address in the given list
     foreach ($bannedSubjectList as $thisSubject)
     {
        if (DEBUG) echo "Checking Subject: $thisSubject...\n";
        
        {
           if (preg_match($thisSubject, $currentSubject))
           {
              if (DEBUG) echo "Found $thisSubject in $currentSubject\n";
              return $currentSubject;
           }
        }
     }
     
     return null;
  }
  
  function inList($list= null, $headers = null)
  {
        
     // Search for the given email address in the given list
     foreach ($list as $thisEmail)
     {
        if (DEBUG) echo "Checking $thisEmail ...\n";
        foreach($headers as $str)
        {
           if (preg_match('/' . $thisEmail . '/', $str))
           {
              if (DEBUG) echo "Found $thisEmail in $str \n";
              return $thisEmail;
           }
        }
     }
     
     return null;
  }
?>
