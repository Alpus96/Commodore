<?php

    require_once 'MysqlSocket.php';

    class StoreSocket extends MysqlSocket {

        static private $query;

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

        protected function saveToStore ($id, $token, $salt) {
            $t_is = is_string($token);
            $s_is = is_string($salt);
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t || !$t_is || !$s_is) { return false; }

            $conn = parent::connect();
            if (!$conn) { return false; }
            if ($query = $conn->prepare(self::$query->insert)) {
                $query->bind_param($id_t.'sss', $id, $token, $salt);
                $query->execute();
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                return $success;
            }
            $conn->close();
            return false;
        }

        protected function getFromStore ($token) {
            $t_is = is_string($token);
            if (!$t_is) { return false; }

            $conn = parent::connect();
            if (!$conn) { return false; }
            if ($query = $conn->prepare(self::$query->select_t)) {
                $query->bind_param('s', $token);
                $query->execute();
                $query->bind_result($id, $salt, $unix);
                $query->fetch();
                $data = (object)[
                    'id' => $id,
                    'token' => $token,
                    'salt' => $salt,
                    'unix' => $unix
                ];
                $query->close();
                $conn->close();
                return $data;
            }
            $conn->close();
            return false;
        }

        protected function isInStore ($id) {
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t) { return false; }

            $conn = parent::connect();
            if (!$conn) { return false; }
            if ($query = $conn->prepare(self::$query->select_id)) {
                $query->bind_param($id_t, $id);
                $query->execute();
                $query->bind_result($unix);
                $query->fetch();
                $data = $unix;
                $query->close();
                $conn->close();
                return $data;
            }
            $conn->close();
            return false;
        }

        protected function updateInStore ($id, $token, $salt) {
            $t_is = is_string($token);
            $s_is = is_string($salt);
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t || !$t_is || !$s_is) { return false; }

            $conn = parent::connect();
            if (!$conn) { return false; }
            if ($query = $conn->prepare(self::$query->update)) {
                $query->bind_param('ss'.$id_t, $token, $salt, $id);
                $query->execute();
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                return $success;
            }
            $conn->close();
            return false;
        }

        protected function deleteFromStore ($id) {
            $id_t = is_string($id) ? 's' : is_integer($id) ? 'i' : false;
            if (!$id_t) { return false; }

            $conn = parent::connect();
            if (!$conn) { return false; }
            if ($query = $conn->prepare(self::$query->delete)) {
                $query->bind_param($id_t, $id);
                $query->execute();
                $success = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                return $success;
            }
            $conn->close();
            return false;
        }

    }
?>