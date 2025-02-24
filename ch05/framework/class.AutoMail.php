<?php

/*
* CVS ID: $Id$
*/

class AutoMail
{
   function AutoMail($dbi = null, $mail_id = null)
   {
       $this->mail_id    = $mail_id;
       $this->dbi        = $dbi;
       $this->header_tbl = 'AUTO_MAIL_HDRS';
       $this->mail_tbl   = 'AUTO_MAIL';

   }

   function setMailID($mail_id = null)
   {
       if (!empty($mail_id))
       {
           $this->mail_id  = $mail_id;
       }
       return $this->mail_id;
   }

   function getBody($mail_id = null)
   {
      $this->setMailID($mail_id);
      $stmt = "SELECT BODY FROM $this->mail_tbl WHERE MAIL_ID = $this->mail_id";
      $result = $this->dbi->query($stmt);
      
            
      if ($result!=null) 
      {
         $row = $result->fetchRow();
         //echo "Print BODY " . $row->BODY;
      
         return $row->BODY;	
      }
      return null;
   }
   
   function getHeaders($mail_id = null)
   {
      $this->setMailID($mail_id);
      $stmt = "SELECT HEADER_ID, VALUE from $this->header_tbl WHERE MAIL_ID = $this->mail_id";
      $result = $this->dbi->query($stmt);
      if ($result != null)
      {
      	 while($row = $result->fetchRow())
      	 {
      	    $headerArray[$row->HEADER_ID] = $row->VALUE;
      	    //echo $row->VALUE;
         }
      	 return $headerArray;
      }
      return null;

   }

   function writeMail($data = null)
   {
   }

   function writerHeaders($mail_id = null, $headers = null)
   {
   }
   
   function sendAutoMail($toField = null, $message = null)
   {
   	   /*
   	      Header Key = 1  => To: header
   	                   2  => From: header
   	                   3  => Subject: header
   	                   4  => Conetnt-Type: header
   	   */
   	   
   	   
   	   if (empty($toField)) return false;
   	   else
   	   {
   	   	   global $DEFAULT_FROM;
   	   	   $CRNL = "\r\n";
   	   	   
   	   	   $headers = null;
   	   	   $headerArr = $this->getHeaders();
           
           $headers .= (empty($headerArr[1])) ? 'To: ' . $toField  . $CRNL : 'To: ' . $headerArr[1] . $CRNL;
           $headers .= (!empty($headerArr[2])) ? 'From: ' . $headerArr[2]  . $CRNL : 'From: ' . $DEFAULT_FROM . $CRNL;
           $headers .= 'Content-Type: ' . $headerArr[4] . $CRNL;
           //$headers .= 'Subject: ' . $headerArr[3] . $CRNL;
           
           
   	   	   if (empty($message)) $message = $this->getBody();
		         mail($toField, $headerArr[3], $this->unhtmlentities($message), $headers);
   	   }
   }
   
   function unhtmlentities ($string)
   {
	 $trans_tbl = get_html_translation_table (HTML_ENTITIES);
	 $trans_tbl = array_flip ($trans_tbl);
	 return strtr ($string, $trans_tbl);
   }


}

?>
