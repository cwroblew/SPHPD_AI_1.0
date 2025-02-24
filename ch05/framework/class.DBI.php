<?php

   /*

    Database abstraction class

    Purpose: this class provides database abstraction using the PEAR
    DB package.



   */
   define('DBI_LOADED', TRUE);
   
   class DBI {

      var $VERSION = "1.0.0";

      function DBI($DB_URL)
      {

         

         $this->db_url = $DB_URL;

         $this->connect();

         if ($this->connected == TRUE)
         {
             // set default mode for all resultset

             $this->dbh->setFetchMode(DB_FETCHMODE_OBJECT);
         } 



      }

      function connect()
      {

         // connect to the database


         $status = $this->dbh = DB::connect($this->db_url, 2);

         if (DB::isError($status))
         {
             $this->connected = FALSE;

             $this->error = $status->getMessage();
			
         } else {

             $this->connected = TRUE;
         }


         return $this->connected;

      }
   
      function isConnected()
      {
         return $this->connected;
      }

      function disconnect()
      {

         if (isset($this->dbh)) {
             $this->dbh->disconnect();
             return 1;
         } else {
             return 0;
         }
      }


      function query($statement)
      {
echo $statement;
         $result =  $this->dbh->query($statement);

         if (DB::isError($result))
         {

            $this->setError($result->getMessage());

            return null;

         } else {

            return $result;
         }


      }
      function setError($msg = null)
      {

          global $TABLE_DOES_NOT_EXIST, $TABLE_UNKNOWN_ERROR;

          $this->error = $msg;

          if (strpos($msg, 'no such table'))
          {
             $this->error_type = $TABLE_DOES_NOT_EXIST;

          } else {

             $this->error_type = $TABLE_UNKNOWN_ERROR;
          }
      }

      function isError()
      {
         return (!empty($this->error)) ? 1 : 0;
      }

      function isErrorType($type = null)
      {
          return ($this->error_type == $type) ? 1 : 0;
      }

      function getError()
      {
          return $this->error;
      }

      function quote($str)
      {
          return "'" . $str . "'";
      }

      function apiVersion()
      {
         return $VERSION;
      }
   }

?>
