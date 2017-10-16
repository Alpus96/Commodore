<?php

    require_once 'mysqlSocket.php';
    require_once 'logger.php';

    class userModel extends mysqlSocket {

        static private $query;

        protected function __construct () {
            parent::__construct();
            self::$query->read = 'SELECT * FROM USERS WHERE USERNAME = ?';
            self::$query->ud_hash = 'UPDATE USERS SET HASH = ? WHERE USERNAME = ?';
            self::$query->ud_dname = 'UPDATE USERS SET DISPLAY_NAME = ? WHERE USERNAME = ?';
        }

        protected function read ($username) {
            if (!is_string($username)) { return false; }
            $conn = parent::connect();
            if ($query = $conn->prepare(self::$query->read)) {
                $query->bind_param('s', $username);
                $query->execute();
                $query->bind_result($username, $display_name, $hash, $type, $locked);
                $query->fetch();
                $data = (object)[
                    'username' => $username,
                    'display_name' => $display_name,
                    'hash' => $hash,
                    'type' => $type,
                    'locked' => $locked
                ];
                $query->close();
                $conn->close();
                return $data->hash ? $data : false;
            }
            $conn->close();
            return false;
        }

        protected function updateHash ($username, $newHash) {
            if (!is_string($username) || !is_string($newHash)) { return false; }
            $conn = parent::connect();
            if ($query = $conn->prepare(self::$query->ud_hash)) {
                $query->bind_param('ss', $newHash, $username);
                $query->execute();
                $result = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                return $result;
            }
            $conn->close();
            return false;
        }

        protected function updateDisplayName ($username, $new_dname) {
            if (!is_string($username) || !is_string($new_dname)) { return false; }
            $conn = parent::connect();
            if ($query = $conn->prepare(self::$query->ud_dname)) {
                $query->bind_param('ss', $new_dname, $username);
                $query->execute();
                $result = $query->affected_rows > 0 ? true : false;
                $query->close();
                $conn->close();
                return $result;
            }
            $conn->close();
            return false;
        }

    }
?>
