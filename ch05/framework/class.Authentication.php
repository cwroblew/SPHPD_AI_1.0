<?php

/*
*
* Application class
*
* @author EVOKNOW, Inc. <php@evoknow.com>
* @access public
* CVS ID: $Id$
*/

  include_once $DEBUGGER_CLASS;

  class Authentication {

     function Authentication($username = null, $email = null, $password = null, $db_url = null)
     {

        global $AUTH_DB_TBL;

     	$this->status = FALSE;
		$this->username = $username;
     	$this->email = $email;
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
			$username_email = "email = '" . $this->email . "'";
		}
		else
		{
			$username_email = "username = '" . $this->username . "'";
		}
        $query  = "SELECT Id, password from " . $this->auth_tbl;
        $query .= " WHERE " . $username_email . " AND Active = '1'";

        $result = $dbi->query($query);

        if ($result != null)
        {

           $row = $result->fetchRow();

           $salt = substr($row->password,0,2);



           if (crypt($this->password, $salt) == $row->password)
           {

              $this->status  = TRUE;
              $this->user_id = $row->Id;

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
