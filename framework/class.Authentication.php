<?php

/*
*
* Application class
*
* @author EVOKNOW, Inc. <php@evoknow.com>
* @access public
* CVS ID: $Id$
*/

global $DEBUGGER_CLASS;
include_once $DEBUGGER_CLASS;

  class Authentication {

     function __construct($username = null, $email = null, $password = null, $db_url = null)
     {

        global $AUTH_DB_TBL;

     	$this->status = FALSE;
     	$this->email = $email;
		$this->username = $username;
     	$this->password = $password;
        $this->auth_tbl = $AUTH_DB_TBL;

        $this->db_url = ($db_url == null) ? null : $db_url;

        if ($db_url == null)
        {
           global $AUTH_DB_TYPE, $AUTH_DB_NAME;
           global $AUTH_DB_USERNAME, $AUTH_DB_PASSWD;
           global $AUTH_DB_HOST;

           $this->db_url = sprintf("%s://%s:%s@%s/%s",$AUTH_DB_TYPE,
                                                      $AUTH_DB_USERNAME,
                                                      $AUTH_DB_PASSWD,
                                                      $AUTH_DB_HOST,
                                                      $AUTH_DB_NAME);
        }

        $this->status = FALSE;
     }

     function authenticate()
     {

        $dbi = new DBI($this->db_url);

		if ($this->username == null)
		{
			$username_email = "EMAIL = '" . $this->email . "'";
		}
		else
		{
			$username_email = "USERNAME = '" . $this->username . "'";
		}
        $query  = "SELECT USER_ID, PASSWORD from " . $this->auth_tbl;
        $query .= " WHERE " . $username_email . " AND ACTIVE = '1'";

        $result = $dbi->query($query);

        if ($result != null)
        {
           $row = $result->fetchRow();

           $salt = substr($row->PASSWORD,0,2);
//echo crypt($this->PASSWORD, $salt);
           if (crypt($this->password, $salt) == $row->PASSWORD)
           {

              $this->status  = TRUE;
              $this->user_id = $row->USER_ID;

           } else {
              $this->status = FALSE;
           }
        }
        $dbi->disconnect();

     	return $this->status;
     }

     function getUID()
     {
         return $this->user_id;
     }


  }

?>
