<?php

    class Form
    {
        
        //Constructor takes the DBI object and an array as parameter. 
        function Form($dbi = null, $fid = null)
        {
           $this->dbi = $dbi;
           
           $this->frm_tbl = TAF_FRM_TBL;
           
           $this->submtn_tbl = TAF_FRM_SUBMTN_TBL;
           
           $this->subscr_tbl = TAF_SUBSCRIPTION_TBL;
            
           $this->field_arr = array
                                   (
                                    'FRM_ID'                        =>    'number',
                                    'FRM_NAME'                      =>    'text',
                                    'ACTIVATION_TS'                 =>    'number',
                                    'TERMINATION_TS'                =>    'number',
                                    'FRIENDS_MSG_ID'                =>    'number',
                                    'ORIGIN_MSG_ID'                 =>    'number',
                                    'SUBSCRIBER_MSG_ID'             =>    'number',
                                    'MAX_FRIEND_PER_ORIGIN'         =>    'number',
                                    'SCORE_PER_FRIEND_SUBMISSION'   =>    'number',
                                    'SCORE_PER_FRIEND_SUBSCRIPTION' =>    'number'
                                   );
           $this->fields = implode(',', array_keys($this->field_arr));
           $this->setFormID($fid);
        }
        
        function setFormID($fid)
        {
           if (!empty($fid))
           {
              $this->fid = $fid;	
           }	
           return empty($this->fid) ? NULL : $this->fid;
        }
        
        function getFormInfo($fid = null)
        {        	
           $this->setFormID($fid);
           $stmt = "SELECT $this->fields FROM $this->frm_tbl WHERE FRM_ID = $this->fid";
           
           $result = $this->dbi->query($stmt);

           if (empty($result) || $result->numRows() <= 0) 
           {
              return null;
           }
           $row = $result->fetchRow();
           $retArr = array();
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {              
              $retArr[$fieldName] = stripslashes($row->$fieldName);	                
           }
           return $retArr;
        }
        
        function getAllForms()
        {
           $stmt = "SELECT FRM_ID, FRM_NAME FROM $this->frm_tbl";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           while ($row = $result->fetchRow())
           {
              $retArr[$row->FRM_ID] = $row->FRM_NAME;
           }
           return $retArr;           
        }
        
        function addForm($params)
        {
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }
           
           $paramValueStr = implode(', ', $params);
           $stmt = "INSERT INTO $this->frm_tbl($this->fields) VALUES($paramValueStr)";
           
           $result = $this->dbi->query($stmt);
           if ($result != DB_OK)
           {
              return false;	
           }
           
           $stmt = "SELECT FRM_ID FROM $this->frm_tbl WHERE FRM_NAME = $params[FRM_NAME]";
           $result= $this->dbi->query($stmt);

           if (empty($result) || $result->numRows() <= 0)
           {
              return false;	
           }
           $row = $result->fetchRow();
           return $row->FRM_ID;
        }
        
        function modifyForm($params)
        {
           while(list($fieldName, $fieldType) = each($this->field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }
           $keyValue = null;
           while(list($k, $v) = each($params))
           {
            $keyValue .= empty($keyValue) ? null : ', ';
            $keyValue .= $k.' = '.$v;  	
           }
         
           $stmt = "UPDATE $this->frm_tbl SET $keyValue WHERE FRM_ID = $params[FRM_ID]";
           
           
           $result = $this->dbi->query($stmt);
           return ($result == DB_OK) ? true : false;	
        }
        
        function deleteForm($fid = null)
        {
           $this->setFormID($fid);
           $stmt = "DELETE FROM $this->frm_tbl WHERE FRM_ID = $this->fid";
           $result = $this->dbi->query($stmt);
           return ($result == DB_OK) ? true : false; 	
        }
        
        function isMaximumSubmitted($orig, $fid = null)
        {
           $this->setFormID($fid);
           $orig = $this->dbi->quote(addslashes($orig));
           $stmt = "SELECT COUNT(FRND_ID) AS FRNDS FROM $this->submtn_tbl "
                  ."WHERE ORIGIN_EMAIL = $orig AND FRM_ID = $this->fid";
           
           $result = $this->dbi->query($stmt);
           if (empty($result))
           {
              return FALSE;
           }


           $row = $result->fetchRow();
           $frnds = $row->FRNDS;
           $frmInfo = $this->getFormInfo();
           $maxSubmtn = isset($frmInfo['MAX_FRIEND_PER_ORIGIN']) ? $frmInfo['MAX_FRIEND_PER_ORIGIN'] : 0;
           return ($maxSubmtn >= $frnds) ? TRUE : FALSE;
           
        }
        
        function addSubmissionData($params)
        {
           $field_arr = array
                             (
                              'FRND_ID'       => 'number',
                              'FRND_EMAIL'    => 'text',
                              'FRND_NAME'     => 'text',
                              'FRM_ID'        => 'number',
                              'ORIGIN_EMAIL'  => 'text',
                              'ORIGIN_IP'    => 'text',
                              'SUBMIT_TS'    => 'number',                                    
                             );           
           $fields = implode(',', array_keys($field_arr));
           
           while(list($fieldName, $fieldType) = each($field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }           
           $paramValueStr = implode(', ', $params);           
           $stmt = "INSERT INTO $this->submtn_tbl($fields) VALUES($paramValueStr)";
           $result = $this->dbi->query($stmt);           
           if ($result != DB_OK)
           {
              return false;
           }           
           $stmt = "SELECT FRND_ID FROM $this->submtn_tbl WHERE FRM_ID = $params[FRM_ID] AND FRND_EMAIL = $params[FRND_EMAIL]";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return false;	
           }
           $row = $result->fetchRow();
           return $row->FRND_ID;
        }
        
        function getFriendList($fid = null)
        {
           $this->setFormID($fid);
           $stmt = "SELECT FRND_ID, FRND_EMAIL FROM $this->submtn_tbl WHERE FRM_ID = $this->fid";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           while ($row = $result->fetchRow())
           {
             $retArr[$row->FRND_ID] = $row->FRND_EMAIL; 	
           }
           return $retArr;
        }
        
        function addSubscriptionData($params)
        {
           $field_arr = array
                             (
                              'FRM_ID'        =>  'number',
                              'FRND_EMAIL'    =>  'text',
                              'SUBSCRIPTION'  =>  'text',
                              'ORIGIN_EMAIL'  =>  'text',
                              'TS'            =>  'number'
                             );
           $fields = implode(',', array_keys($field_arr));
           
           while(list($fieldName, $fieldType) = each($field_arr))
           {
              if (!strcmp($fieldType, 'text'))
              {
                 $params[$fieldName] = $this->dbi->quote(addslashes($params[$fieldName]));	 
              }
           }           
           $paramValueStr = implode(', ', $params);           
           $stmt = "INSERT INTO $this->subscr_tbl($fields) VALUES($paramValueStr)";

           $result = $this->dbi->query($stmt);

           if ($result != DB_OK)
           {
                // the friend email already exists so lets update the subscription
                $stmt = "UPDATE $this->subscr_tbl SET".
                        " SUBSCRIPTION = " .$params['SUBSCRIPTION'] . " ," .
                        " TS = " . $params['TS'] .
                        " WHERE FRND_EMAIL = " . $params['FRND_EMAIL'] ." AND ".
                        " ORIGIN_EMAIL = " . $params['ORIGIN_EMAIL'];
                
                $result = $this->dbi->query($stmt);
 
           }

           return ($result == DB_OK) ? TRUE : FALSE;
        }
        
        function hasUnsubscribed($email)
        {
           $email = $this->dbi->quote(addslashes($email));
           $stmt = "SELECT FRND_EMAIL FROM $this->subscr_tbl WHERE FRND_EMAIL = $email AND SUBSCRIPTION = 'unsub'";
           $result = $this->dbi->query($stmt);
           return (empty($result) || $result->numRows() <= 0) ? 0 : $result->numRows();

        }
        
        function getNumberOfSubscriber($fid = null)
        {
           $this->setFormID($fid);
           $stmt = "SELECT FRND_EMAIL FROM $this->subscr_tbl WHERE FRM_ID = $this->fid AND SUBSCRIPTION = 'sub'";
           $result = $this->dbi->query($stmt);
           return (empty($result) || $result->numRows() <= 0) ? 0 : $result->numRows();
        }
        
        function getNumberOfUnsubscriber($fid = null)
        {
           $this->setFormID($fid);
           $stmt = "SELECT FRND_EMAIL FROM $this->subscr_tbl WHERE FRM_ID = $this->fid AND SUBSCRIPTION = 'unsub'";
           $result = $this->dbi->query($stmt);
           return (empty($result) || $result->numRows() <= 0) ? 0 : $result->numRows();
        }
        
        function getOriginSubmissions($fid = null)
        {
           $this->setFormID($fid);
           $stmt = "SELECT ORIGIN_EMAIL, COUNT(FRND_ID) AS NUM_SUBMTN FROM $this->submtn_tbl WHERE FRM_ID = $this->fid GROUP BY ORIGIN_EMAIL";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           while ($row  = $result->fetchRow())
           {
              $retArr[$row->ORIGIN_EMAIL] = $row->NUM_SUBMTN;	
           }
           return $retArr;
        }
        
        function getNumSubscriptionPerOrigin($orig, $fid = null)
        {
           $orig = $this->dbi->quote(addslashes($orig));
           $fid = $this->setFormID($fid);
           $stmt = "SELECT FRND_EMAIL "
                  ."FROM $this->subscr_tbl "
                  ."WHERE FRM_ID = $this->fid AND ORIGIN_EMAIL = $orig AND SUBSCRIPTION = 'sub'";
           $result = $this->dbi->query($stmt);

           return (empty($result) || $result->numRows() <= 0) ? 0 : $result->numRows();
        }
        
        function getFriendsByOrigin($orig, $fid = null)
        {
           $orig = $this->dbi->quote(addslashes($orig));
           $fid = $this->setFormID($fid);
           $stmt = "SELECT FRND_EMAIL, FRND_NAME FROM $this->submtn_tbl WHERE ORIGIN_EMAIL = $orig AND FRM_ID = $this->fid";
           $result = $this->dbi->query($stmt);

           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           while ($row = $result->fetchRow())
           {
              $retArr[$row->FRND_EMAIL] = $row->FRND_NAME;	
           }
           return $retArr;
        }
        
        function getSubscriptionStatus($frnd, $fid = null)
        {
           $frnd = $this->dbi->quote(addslashes($frnd));
           $fid = $this->setFormID($fid);
           $stmt = "SELECT SUBSCRIPTION FROM $this->subscr_tbl WHERE FRND_EMAIL = $frnd AND FRM_ID = $this->fid";
           $result = $this->dbi->query($stmt);
           if (empty($result) || $result->numRows() <= 0)
           {
              return null;	
           }
           $row = $result->fetchRow();
           return (!strcmp($row->SUBSCRIPTION, 'sub')) ? 1 : -1;
        }
    }

?>
