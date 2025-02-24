<?php

   class FormSubmission {

     function FormSubmission($dbi = null )
     { 
         $this->_DBI = $dbi;
         $this->_ID = $_REQUEST['form_id'];
         $this->_KNOWN_FORMS = $GLOBALS['KNOWN_FORMS'];         
         $this->_ERRORS = array();
         
     } 

     function hasError()
     {
        // Return TRUE if there is errors in submitted data
        return (count($this->_ERRORS) <= 0) ? FALSE : TRUE;
     }

     function getErrors()
     {
        // Return error messages
        return $this->_ERRORS;
     }

     function getErrorMessage($lang = null, $err = null)
     {
         // Return error messages for given language
         if (! $err) 
         {
             $err = $this->_ERRORS;
         }


         if (is_array($err))
         {
            foreach ($err as $k)
            {
               $k = 'ERROR_' . strtoupper($k);
               $errMsg[] = $this->_FORM_ERRORS[$lang][$k];
            }

             return '\n' . implode('\n', $errMsg);

         } else {

             $err = strtoupper($err);

             return $this->_FORM_ERRORS[$lang][$err];
         }
     }

     function setupForm()
     {
     	// setup form's configuraiton
     	
     	foreach ($this->_FORM_FIELDS as $field => $config)
     	{
           // Breakdown the configuration for each form field
           list($requiredFlag,
                $fieldType,
                $sizeInfo,
                $validator,
                $cleanupMethods)     = explode(':', $config);

            $this->_REQUIRED[$field] = ($requiredFlag) ? TRUE : FALSE;
            
            $this->_TYPE[$field]     = strtolower($fieldType);
            
            $this->_VALIDATOR[$field]= strtolower($validator);
            $this->_CLEANUP[$field]  = strtolower($cleanupMethods);
            $this->_SIZE[$field]     = strtolower($sizeInfo);
     	}
     }


     function isKnownForm()
     {
        // check the given form id is known
        
        return (in_array($this->_ID, array_keys($this->_KNOWN_FORMS))) ? TRUE : FALSE;
     }


     function loadConfigFile()
     { 
     	require_once FORM_CONF_FILE_DIR.'/'.
                     substr($this->_KNOWN_FORMS[$this->_ID], 0, 
                            strpos($this->_KNOWN_FORMS[$this->_ID],'.')).'/'.$this->_KNOWN_FORMS[$this->_ID];
     	
     	$this->_FORM_FIELDS = $FORM_FIELDS_ARRAY;
     	$this->_FORM_ERRORS = $ERRORS;
     	
     	if(UPLOAD_FILE)
        {  
           $this->_FILE_LOAD_FIELDS = $UPLOAD_FILE_FIELDS_ARRAY;
        }
     	
     }


     function processForm()                     
     {
        // Check if field requirements are met
        if (! $this->haveRequiredData()){
            return MISSING_REQUIRED_VALUES;
        }

        // Validate data
        if (! $this->validateData()){            
            return BAD_DATA;
        }

        // Cleanup
        $this->cleanupData();

        // Submit data to database
        if(! $this->submitData())
        {
            return DATABASE_FAILURE;
        }
        
        //upload attachment file to directory
        $status = $this->uploadFile();
        
        if($status != 1)
        {
           return $status;
        }
        
        // Send outbound and inbound emails
        if (SEND_OUTBOUND_MAIL)
        {
           $this->sendMail($_REQUEST['email'], OUTBOUND_MAIL_TEMPLATE, OUTBOUND_MAIL_SUBJECT);
        }

        if (SEND_INBOUND_MAIL)
        {
           $this->sendMail(INBOUND_MAIL_TO, INBOUND_MAIL_TEMPLATE, INBOUND_MAIL_SUBJECT);
        }

        return TRUE;
     }


     function haveRequiredData()
     {
        // check to see if submission has required fields.
        foreach ($this->_REQUIRED as $field => $required)
        {
            
            if ($required && empty($_REQUEST[$field]))
            {
            	array_push($this->_ERRORS, $field);
            }
        }

        return (count($this->_ERRORS) <= 0) ? TRUE : FALSE;
     }


     function validateData()
     {
        // check to see if submission data is valid

        // Create a Data Validator object
        $dataValidatorObj = new DataValidator();

        foreach ($this->_VALIDATOR as $field => $validator)
        {
            // Call the validate method with (field value, type, size)
            if(!empty($_REQUEST[$field]))
            {


               if (!$dataValidatorObj->validate($_REQUEST[$field], 
                                                $this->_TYPE[$field], 
                                                $this->_SIZE[$field], 
                                                $this->_VALIDATOR[$field]))
               {
               	array_push($this->_ERRORS, $field);
               }
            }
        }

        return (count($this->_ERRORS) <= 0) ? TRUE : FALSE;
     }


     function cleanupData()
     {
        // cleanup data

        // Create a Data Validator object
        $cleanupObj = new DataCleanup();

        foreach ($this->_CLEANUP as $field => $cleanupMethods)
        {

            // Make a list of cleanup methods to apply for the current field
            $methodList = explode('|', $cleanupMethods);

            // For each cleanup method apply it
            foreach ($methodList as $func)
            {
            	// Cleanup method names are cleanup_NAME
            	$method = 'cleanup_' . $func;
            	// Call the cleanup method with data from $_REQUEST
            	// store the data back in the same place
            	$_REQUEST[$field] = $cleanupObj->$method($_REQUEST[$field]);
            }
            
        }

        return TRUE;
     }

     function submitData()
     {
     	 // Submit data to the FORM_TABLE
         $fields = array();
         $values = array();
     	 foreach ($this->_TYPE as $field => $type)
     	 {
     	    array_push($fields, $field);
     	    // If data type is text then quote(addslashes())
     	    if (!strcmp($type, 'text'))
     	    {
     	    	array_push($values, $this->_DBI->quote(addslashes($_REQUEST[$field])));
     	    } else {
     	    	array_push($values, $_REQUEST[$field]);
     	    }
     	 }


         array_push($fields, 'SUBMIT_TS');
         array_push($values, mktime());

     	 // Now build the SQL INSERT statement.
     	 $fieldList = implode(',', $fields);
     	 $valueList = implode(',', $values);
     	 $stmt = sprintf("INSERT INTO %s (%s) VALUES (%s)", FORM_TABLE, $fieldList, $valueList); 

       	 // Execute the statement
     	 $result = $this->_DBI->query($stmt);

     	 return ($result == DB_OK) ? TRUE  : FALSE;
     	 
     }
     
     
     function uploadFile()
     {  
     	// upload attachment file to the specified directory in the form.conf 

     	$dataValidatorObj = new DataValidator();
     	
     	if( !empty($this->_FILE_LOAD_FIELDS))
     	{

           foreach($this->_FILE_LOAD_FIELDS as $fieldName => $value)
           {

//MJK
              list($requiredFlag, $sizeInfo)  =  explode(':', $value);
              
              if($requiredFlag)
              { 
                 if(empty($_FILES[$fieldName]['name']))
                 {
                    return MISSING_REQUIRED_VALUES;
                 }
              }
              
              if(!empty($_FILES[$fieldName]['name']))
              {
                 if( !$dataValidatorObj->validate_file_size($sizeInfo, $_FILES[$fieldName]['size']) )
                 {
                    return INVALID_FILE_SIZE;
                 }
                           
                 move_uploaded_file($_FILES[$fieldName]['tmp_name'], $GLOBAL[REL_APP_PATH].UPLOAD_FILE_DIR.$_FILES[$fieldName]['name']);   
              }
           }
        }
                
        return TRUE;
     }


     function sendMail($toList = null, $msgTemplate = null, $subject = null)
     {
        $recipients = explode(',', $toList);

     	if (count($recipients) < 0) {
     	
     	   return FALSE;
     	}

        // Load the message template
        // Replace all tags using $_REQUEST[] and $_SERVER[]

        //$body = new Template($this->getTemplateDir());
        
        $body = new Template($GLOBALS['TEMPLATE_DIR']);  
        $body->set_file('fh', $msgTemplate);
        $body->set_block('fh', 'mainBlock', 'mblock');

        foreach($_REQUEST as $key => $value)
        {
           // Upper case key field to match {DATAFIELD} tags
           // in the message body template
           $body->set_var(strtoupper($key), stripslashes($value));
        }

        $contents = $body->parse('mblock', 'mainBlock');
               
        $headers = "Content-type: text/html; charset=iso-8859-1\r\n";
         
        foreach ($recipients as $to)
        {
           mail($to, $subject, $contents, $headers);
           // send mail to $to $contents $subject;
        }
     }


   }//class


?>
