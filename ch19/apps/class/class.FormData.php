<?php

   class FormData {

     function FormData($dbi = null , $fid = null)
     {
        $this->_DBI = $dbi;        
        $this->_DL_TBL = DOWNLOAD_TRACK_TBL;
        $this->setFormID($fid);            	
     }
     
     function setFormID($fid = null)
     {
        if (!empty($fid))
        {
           $this->_fid = $fid;
           $KNOWN_FORMS = $GLOBALS['KNOWN_FORMS'];
           require_once FORM_CONF_FILE_DIR.'/'.
                             substr($KNOWN_FORMS[$this->_fid], 0, 
                                   strpos($KNOWN_FORMS[$this->_fid],'.')).'/'.$KNOWN_FORMS[$this->_fid];	
           $this->_fieldArr = $FORM_FIELDS_ARRAY;
           $this->_fields = implode(",", array_keys($this->_fieldArr));                        
        }
        return $this->_fid;
     }
     
     function getFormData($start = null, $end = null, $sort = null, $toggle = null, $fid = null)
     {
     	$this->setFormID($fid);     	
     	$sort = empty($sort) ? 'id' : $sort;     	
     	$start = empty($start) ? 0 : $start;     	
     	$end = empty($end) ? mktime() : $end;
     	
     	$stmt = "SELECT id, $this->_fields FROM ".FORM_TABLE
     	       ." WHERE SUBMIT_TS BETWEEN $start AND $end"
     	       ." ORDER BY $sort $toggle";
     	$result = $this->_DBI->query($stmt);
     	if (empty($result) || $result->numRows() <= 0)
     	{
     	   return null;     	   	
     	}
     	while ($row = $result->fetchRow())
     	{
     	   $retArr[] = $row;
     	}
     	return $retArr;
     }
     
     function getDataAfterRecordID($id, $fid = null)
     {
        $this->setFormID($fid);
        
        $stmt = "SELECT id, $this->_fields FROM ".FORM_TABLE." WHERE id > $id";
        $result = $this->_DBI->query($stmt);
     	if (empty($result) || $result->numRows() <= 0)
     	{
     	   return null;     	   	
     	}
     	while ($row = $result->fetchRow())
     	{
     	   $retArr[] = $row;
     	}
     	return $retArr;        	
     }
     
     function getLastDLRecordID($fid = null)
     {
        $this->setFormID($fid);        
        $fid = $this->_DBI->quote(addslashes($this->_fid));
        
        $stmt = "SELECT MAX(RECORD_ID) AS ID FROM $this->_DL_TBL WHERE FORM_ID = $fid";        
        $result = $this->_DBI->query($stmt);
     	if (empty($result) || $result->numRows() <= 0)
     	{
     	   return 0;     	   	
     	}
     	$row = $result->fetchRow();
     	return $row->ID;
     }
     
     function updateDownloadTrack($recid, $fid = null)
     {
     	$this->setFormID($fid);        
        $fid = $this->_DBI->quote(addslashes($this->_fid));        
        $curTime = mktime();                
        $stmt = "INSERT INTO $this->_DL_TBL(FORM_ID, DOWNLOAD_TS, RECORD_ID) VALUES($fid, $curTime, $recid)";        
        $result = $this->_DBI->query($stmt);        
        return ($result == DB_OK) ? TRUE : FALSE;
     }
     
   }

?>