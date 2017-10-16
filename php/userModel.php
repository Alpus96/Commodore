<?php
    /**
    *   This class handels reading user information, also
    *   updating password hashes and display names.
    *
    *   @uses           mysqlSocket
    *   @uses           logger
    *
    *   @category       Datastoreage
    *   @package        Users
    *   @subpackage     User_info
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'mysqlSocket.php';
    require_once 'logger.php';

    class userModel extends mysqlSocket {

        static private $query;

        /**
        *   @method     Constructs the parent mysqlSocket class and sets class usable queries.
        * */
        protected function __construct () {
            parent::__construct();
            self::$query->read = 'SELECT * FROM USERS WHERE USERNAME = ?';
            self::$query->ud_hash = 'UPDATE USERS SET HASH = ? WHERE USERNAME = ?';
            self::$query->ud_dname = 'UPDATE USERS SET DISPLAY_NAME = ? WHERE USERNAME = ?';
        }

        /**
        *   @method     Reads the database row for the given username.
        *
        *   @param      string        : The username to query with.
        *
        *   @return     object|false  : The object representation of the database row or
        *                               false if unsuccessful or nothing found.
        * */
        protected function read ($username) {
            //  Confirm the username is a string.
            if (!is_string($username)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Query the database for the user information.
            if ($query = $conn->prepare(self::$query->read)) {
                $query->bind_param('s', $username);
                $query->execute();
                //  Get the result.
                $query->bind_result($username, $display_name, $hash, $type, $locked);
                $query->fetch();
                $data = (object)[
                    'username' => $username,
                    'display_name' => $display_name,
                    'hash' => $hash,
                    'type' => $type,
                    'locked' => $locked
                ];
                //  Close the query and connection after result.
                $query->close();
                $conn->close();
                //  Return the data object if it contains user information or false if not.
                return $data->hash ? $data : false;
            }
            //  If the query could not be perform close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Updates the user password hash.
        *
        *   @param      string        : The username to update the hash of.
        *   @param      string        : The new hash string.
        *
        *   @return     boolean       : Success status of the action.
        * */
        protected function updateHash ($username, $newHash) {
            //  Confirm the given username and hash are strings.
            if (!is_string($username) || !is_string($newHash)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  query the database to update hash.
            if ($query = $conn->prepare(self::$query->ud_hash)) {
                $query->bind_param('ss', $newHash, $username);
                $query->execute();
                //  Confirm the query had effect.
                $result = $query->affected_rows > 0 ? true : false;
                //  Close the query and connection.
                $query->close();
                $conn->close();
                //  Return the success status.
                return $result;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Updates the display name for the given username.
        *
        *   @param      string        : The username to update the display name of.
        *   @param      string        : The display name to update to.
        * */
        protected function updateDisplayName ($username, $new_dname) {
            //  Confirm the username and display name are strings.
            if (!is_string($username) || !is_string($new_dname)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Query the database to update the display name.
            if ($query = $conn->prepare(self::$query->ud_dname)) {
                $query->bind_param('ss', $new_dname, $username);
                $query->execute();
                //  Confirm the query had effect.
                $result = $query->affected_rows > 0 ? true : false;
                //  Close the query and connection.
                $query->close();
                $conn->close();
                //  Return the success status.
                return $result;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Enters a exception entry to the error log file.
        *
        *   @param      object|string : The faulty object or message to log as an exception.
        * */
        private function logError ($msg) {
            //  Open the log instance.
            $logger = new logger('tokenModel_errorsLog');
            //  Create an exception with the given prameter.
            $e = new Exception($msg);
            //  Log the exception.
            $logger->log('Unable to connect to database: '.$e);
        }

    }
?>
