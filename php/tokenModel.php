<?php
    /**
    *   This class handels inserting, reading, updating and
    *   deleting token information in the database.
    *
    *   @uses           mysqlSocket
    *   @uses           logger
    *
    *   @category       Datastoreage
    *   @package        dataModels
    *   @subpackage     User tokens
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'mysqlSocket.php';
    require_once 'logger.php';

    class tokenModel extends mysqlSocket {

        static private $query;

        /**
        *   @method     Runs construct of mysqlSocket and sets available queries.
        * */
        protected function __construct () {
            parent::__construct();
            self::$query = (object)[
                'create' => 'INSERT INTO ACTIVE_USERS SET USERNAME = ?, TOKEN = ?',
                'read' => 'SELECT TOKEN, UNIX FROM ACTIVE_USERS WHERE USERNAME = ?',
                'ud_unix' => 'UPDATE ACTIVE_USERS SET UNIX = now() WHERE USERNAME = ?',
                'ud_token' => 'UPDATE ACTIVE_USERS SET UNIX = now(), TOKEN = ? WHERE USERNAME = ?',
                'delete' => 'DELETE FROM ACTIVE_USERS WHERE USERNAME = ?'
            ];
        }

        /**
        *   @method     Inserts a encoded token string into the
        *               database with a username as identifier.
        *
        *   @param      string        : The username to use as identifier for the token.
        *   @param      string        : The token string to insert.
        *
        *   @return     boolean       : True if inserted successfully, false if not.
        * */
        protected function create ($username, $t_string) {
            //  Confirm the given parameters are strings.
            if (!is_string($username) || !is_string($t_string)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Run the query to insert the token.
            if ($query = $conn->prepare(self::$query->create)) {
                $query->bind_param('ss', $username, $t_string);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                //  Return the effect status.
                return $success;
            }
            //  If the query could not be prepared close
            //  the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Reads the rows with identifier matching the given username.
        *
        *   @param      string        : The username to use as identifier.
        *
        *   @return     array|false   : The rows of tokens realated to the username,
        *                               false if no results or unsuccessful.
        * */
        protected function read ($username) {
            //  Confirm the given username is a string.
            if (!is_string($username)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Send the query to the database.
            if ($query = $conn->prepare(self::$query->read)) {
                $query->bind_param('s', $username);
                $query->execute();
                $query->bind_result($token, $unix);
                $data = [];
                //  Fetch the results.
                while ($query->fetch()) {
                    //  Make an object for the row.
                    $tokenEntry = (object)[
                        'username' => $username,
                        'token' => $token,
                        'unix' => $unix
                    ];
                    //  Push the row object into the array of rows.
                    array_push($data, $tokenEntry);
                }
                //  Close the query and the connection.
                $query->close();
                $conn->close();
                //  If there was more than one row log a warning.
                if (count($data) > 1) {
                    $msg = 'WARNING: Several token entries for same username.';
                    self::logError($msg.' ("'.$username.'")');
                }
                //  Return the result.
                return count($data) !== 0 ? $data : false;
            }
            //  If the query was unsuccessful close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Updates the tokens timestamp.
        *
        *   @param      string        : The username to update the token timestamp for.
        *
        *   @return     boolean       : The success status or the action.
        * */
        protected function updateUnix ($username) {
            //  Confirm the username is a string.
            if (!is_string($username)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Send the query to the database.
            if ($query = $conn->prepare(self::$query->ud_unix)) {
                $query->bind_param('s', $username);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                //  Return the success status
                return $success;
            }
            //  If the query could not be prepared close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Updates the token string for the given username.
        *
        *   @param      string        : The username to update the token for.
        *   @param      string        : The new token string.
        *
        *   @return     boolean       : The success status of the action.
        * */
        protected function updateToken ($username, $t_string) {
            //  Confirm the username and token are strings.
            if (!is_string($username) || !is_string($t_string)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Send the query to the database.
            if ($query = $conn->prepare(self::$query->ud_token)) {
                $query->bind_param('ss', $t_string, $username);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                //  Return the success status.
                return $success;
            }
            //  If the query could not be prepared close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Deletes the tokens with the given username as identifier.
        *
        *   @param      string        : The username to delete tokens from.
        *
        *   @return     boolean       : Success status of the action.
        * */
        protected function delete ($username) {
            //  Confirm the username is a string.
            if (!is_string($username)) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) {
                self::logError($conn);
                return false;
            }
            //  Send the query to the database.
            if ($query = $conn->prepare(self::$query->delete)) {
                $query->bind_param('s', $username);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                //  Return the success status.
                return $success;
            }
            //  If the query could not be prepared close the connection and return false.
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
