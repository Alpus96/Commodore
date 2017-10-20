<?php

    /**
    *   This class handels tokens with id, salt
    *   and timestamp in the database.
    *
    *   @uses           MysqlSocket
    *
    *   @category       Datastoreage
    *   @package        JWT_Store
    *   @subpackage     TokenStorage
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'MysqlSocket.php';

    class TokenStorage extends MysqlSocket {

        static private $query;

        /**
        *   @method     Constructs the parent and sets the string values of prepared
        *               queries used by this class.
        * */
        protected function __construct () {
            parent::__construct();
            self::$query = (object)[
                'insert' => 'INSERT INTO TOKEN_STORE SET ID = ?, TOKEN = ?, SALT = ?',
                'select_t' => 'SELECT ID, SALT, UNIX FROM TOKEN_STORE WHERE TOKEN = ?',
                'select_id' => 'SELECT UNIX FROM TOKEN_STORE WHERE ID = ?',
                'update' => 'UPDATE TOKEN_STORE SET TOKEN = ?, SALT = ? WHERE ID = ?',
                'delete' => 'DELETE FROM TOKEN_STORE WHERE ID = ?'
            ];
        }

        /**
        *   @method     Saved the given token and salt with a specified id.
        *
        *   @param      integer|string: The id of the token entry, preferably the same id
        *                               as the user it belongs to.
        *   @param      string        : The token string.
        *   @param      string        : The key the token was encrypted with.
        *
        *   @return     boolean       : The success status of the query.
        * */
        protected function saveToStore ($id, $token, $salt) {
            //  Verify the given params are of valid type.
            $t_is = is_string($token);
            $s_is = is_string($salt);
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t || !$t_is || !$s_is) { return false; }
            //  Connect to the database and confirm connection.
            $conn = parent::connect();
            if (!$conn) { return false; }
            //  Run the query.
            if ($query = $conn->prepare(self::$query->insert)) {
                $query->bind_param($id_t.'sss', $id, $token, $salt);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                //  Close query and connection.
                $query->close();
                $conn->close();
                //  Return success status.
                return $success;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Gets all information about a token entry where the token
        *               matched the passed string.
        *
        *   @param      string        : The token string.
        *
        *   @return     object|boolean: The row where the token matched or false if no match.
        * */
        protected function getFromStore ($token) {
            //  Confrim the passed param is of correct type.
            $t_is = is_string($token);
            if (!$t_is) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) { return false; }
            //  Run the query.
            if ($query = $conn->prepare(self::$query->select_t)) {
                $query->bind_param('s', $token);
                $query->execute();
                //  Bind and fetch the result.
                $query->bind_result($id, $salt, $unix);
                $query->fetch();
                $data = (object)[
                    'id' => $id,
                    'token' => $token,
                    'salt' => $salt,
                    'unix' => $unix
                ];
                //  Close the query and connection.
                $query->close();
                $conn->close();
                //  Return the data if any.
                return $data->id ? $data : false;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Gets the timestamp for the token with the given id.
        *
        *   @param      integer|string: The id of the token entry.
        *
        *   @return     object|false  : The row where the id matched or false if no result.
        * */
        protected function isInStore ($id) {
            //  Confirm the given param is of correct type.
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) { return false; }
            //  Run the query.
            if ($query = $conn->prepare(self::$query->select_id)) {
                $query->bind_param($id_t, $id);
                $query->execute();
                //  Bind the result and fetch it.
                $query->bind_result($unix);
                $query->fetch();
                $data = $unix;
                //  Close the query and the connection.
                $query->close();
                $conn->close();
                //  Return the data if any.
                return $data ? $data : false;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Updates the token in store for the given id with the given tokan and salt.
        *
        *   @param      integer|string: The id of the token entry.
        *   @param      string        : The new token string.
        *   @param      string        : The new salt.
        *
        *   @return     boolean       : Success sataus of the query.
        * */
        protected function updateInStore ($id, $token, $salt) {
            //  Confirm the given params are of correct type.
            $t_is = is_string($token);
            $s_is = is_string($salt);
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t || !$t_is || !$s_is) { return false; }
            //  Connect to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) { return false; }
            //  Run the query.
            if ($query = $conn->prepare(self::$query->update)) {
                $query->bind_param('ss'.$id_t, $token, $salt, $id);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                //  Close query and connection.
                $query->close();
                $conn->close();
                //  Return the success status of the query.
                return $success;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

        /**
        *   @method     Removes the token entry row with id matching given id.
        *
        *   @param      integer|string: The id of the token entry.
        *
        *   @return     boolean       : The success status of the query.
        * */
        protected function deleteFromStore ($id) {
            //  Confirm the given param is of correct type.
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t) { return false; }
            //  Connetc to the database and confirm the connection.
            $conn = parent::connect();
            if (!$conn) { return false; }
            //  Run the query.
            if ($query = $conn->prepare(self::$query->delete)) {
                $query->bind_param($id_t, $id);
                $query->execute();
                //  Confirm the query had effect.
                $success = $query->affected_rows > 0 ? true : false;
                //  Close the query and connection.
                $query->close();
                $conn->close();
                //  Return the success status.
                return $success;
            }
            //  If the query could not be made close the connection and return false.
            $conn->close();
            return false;
        }

    }
?>