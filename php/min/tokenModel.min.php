<?php

    require_once 'mysqlSocket.php';
    require_once 'logger.php';

    class tokenModel extends mysqlSocket {

        static private $query;

        protected function __construct () {
            parent::__construct();
            self::$query = (object)[
                'create' => 'INSERT INTO ACTIVE_USERS SET USERNAME = ?, TOKEN = ?',
                'read' => 'SELECT TOKEN, UNIX FROM ACTIVE_USERS WHERE USERNAME = ?',
                'ud_unix' => 'UPDATE ACTIVE_USERS SET UNIX = now() WHERE USERNAME = ?',
                'ud_token' => 'UPDATE ACTIVE_USERS SET UNIX = now(), TOKEN = ? WHERE USERNAME = ?',
                'delete' => 'DELETE FROM ACTIVE_USERS WHERE USERNAME = ?' ];
        }

        protected function create ($username, $t_string) {
            if (!is_string($username) || !is_string($t_string)) { return false; }
            $conn = parent::connect();
            if (!$conn)
            { self::logError($conn);
              return false; }
            if ($query = $conn->prepare(self::$query->create))
            { $query->bind_param('ss', $username, $t_string);
              $query->execute();
              $success = $query->affected_rows > 0 ? true : false;
              $query->close();
              $conn->close();
              return $success; }
            $conn->close();
            return false;
        }

        protected function read ($username) {
            if (!is_string($username)) { return false; }
            $conn = parent::connect();
            if (!$conn)
            { self::logError($conn);
              return false; }
            if ($query = $conn->prepare(self::$query->read))
            { $query->bind_param('s', $username);
              $query->execute();
              $query->bind_result($token, $unix);
              $data = [];
              while ($query->fetch())
              { $tokenEntry = (object)[
                    'username' => $username,
                    'token' => $token,
                    'unix' => $unix ];
                array_push($data, $tokenEntry); }
              $query->close();
              $conn->close();
              if (count($data) > 1)
              { $msg = 'WARNING: Several token entries for same username.';
                self::logError($msg.' ("'.$username.'")'); }
              return count($data) !== 0 ? $data : false; }
            $conn->close();
            return false;
        }

        protected function updateUnix ($username) {
            if (!is_string($username)) { return false; }
            $conn = parent::connect();
            if (!$conn)
            { self::logError($conn);
              return false; }
            if ($query = $conn->prepare(self::$query->ud_unix))
            { $query->bind_param('s', $username);
              $query->execute();
              $success = $query->affected_rows > 0 ? true : false;
              $query->close();
              $conn->close();
              return $success; }
            $conn->close();
            return false;
        }

        protected function updateToken ($username, $t_string) {
            if (!is_string($username) || !is_string($t_string)) { return false; }
            $conn = parent::connect();
            if (!$conn)
            { self::logError($conn);
              return false; }
            if ($query = $conn->prepare(self::$query->ud_token))
            { $query->bind_param('ss', $t_string, $username);
              $query->execute();
              $success = $query->affected_rows > 0 ? true : false;
              $query->close();
              $conn->close();
              return $success; }
            $conn->close();
            return false;
        }

        protected function delete ($username) {
            if (!is_string($username)) { return false; }
            $conn = parent::connect();
            if (!$conn)
            { self::logError($conn);
              return false; }
            if ($query = $conn->prepare(self::$query->delete))
            { $query->bind_param('s', $username);
              $query->execute();
              $success = $query->affected_rows > 0 ? true : false;
              $query->close();
              $conn->close();
              return $success; }
            $conn->close();
            return false;
        }

        private function logError ($msg) {
            $logger = new logger('tokenModel_errorsLog');
            $e = new Exception($msg);
            $logger->log('Unable to connect to database: '.$e);
        }

    }

?>
