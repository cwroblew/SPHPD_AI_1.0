<?


/*
* CVS ID: $Id$
*/

require_once('constants.php');
require_once('class.DBI.php');
require_once 'DB.php';

$DEBUG = 0;

$DB_URL = "mysql://root:foobar@localhost:/sessions";

$dbi =  new DBI($DB_URL);

$SESS_LIFE = get_cfg_var("session.gc_maxlifetime");

        function sess_open($save_path, $session_name) {
           return true;
        }

        function sess_close() {
           return true;
        }

        function sess_read($key) {
                global $dbi, $DEBUG, $SESS_LIFE;

                $statement = "SELECT value FROM sessions WHERE " .
                       "sesskey = '$key' AND expiry > " . time();

                $result = $dbi->query($statement);

                if ($DEBUG) echo "sess_read: $statement <br>result: $result<br>";
                $row = $result->fetchRow();
                if ($row) {
                   return $row->value;
                }

                return false;
        }

        function sess_write($key, $val) {
                global $dbi, $SESS_LIFE;

                $expiry = time() + $SESS_LIFE;
                $value = addslashes($val);

                $statement = "INSERT INTO sessions VALUES ('$key', $expiry, '$value')";
                $result = $dbi->query($statement);

                if ($DEBUG) echo "sess_write: $statement <br>result: $result<br>";

                if (! $result) {
                        $statement = "UPDATE sessions SET expiry = $expiry, value = '$value' " .
                               "WHERE sesskey = '$key' AND expiry > " . time();
                        $result = $dbi->query($statement);
                }

                return $result;
        }

        function sess_destroy($key) {
                global $dbi;

                $statement = "DELETE FROM sessions WHERE sesskey = '$key'";
                $result = $dbi->query($statement);
                if ($DEBUG) echo "sess_destroy: $statement <br>result: $result<br>";

                return $result;
        }

        function sess_gc($maxlifetime) {
                global $dbi;

                $statement = "DELETE FROM sessions WHERE expiry < " . time();
                $qid = $dbi->query($statement);
                if ($DEBUG) echo "sess_gc: $statement <br>result: $result<br>";

                return 1;
        }

        session_set_save_handler(
                "sess_open",
                "sess_close",
                "sess_read",
                "sess_write",
                "sess_destroy",
                "sess_gc");
?>
