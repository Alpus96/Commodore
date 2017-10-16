<?php
    require_once 'logger.php';
    require_once 'mysqlSocket.php';
    require_once 'tokenHandler.php';

    class userHandler extends mysqlSocket {

        static private $queries;
        static private $token_handler;
        static private $user_obj;

        function __construct ($identifier) {
            parent::__construct();

            $logger = new logger('_userHandler_errorLog');

            self::$queries = (object)[];
            self::setQueries();

            if (is_string($identifier)) {
                self::$token_handler = new tokenHandler($identifier);
                if (self::$token_handler->toJSON()) {
                    $username = json_decode($identifier)->username;
                    self::$user_obj = self::getUserInfo($username);
                }
            } else if (is_object($identifier)) {
                self::verifyCredentials($identifier);
            }
        }

        private function verifyCredentials () {
            //  TODO: verify credentials and create a token.
        }

        private function getUserInfo ($username) {

        }

        public function getToken () {
            return self::$token->toJSON();
        }

        public function getDisplayName () {
            return self::$token->decodedToken()->authName;
        }

        public function logout() {
            return self::$token->deleteToken();
        }

        public function setDisplayName ($PW, $newDisplayName) {
            if (!is_string($newDisplayName) || $newDisplayName === '') { return false; }
            if (password_verify($PW, self::$user_obj->hash)) {
                $conn = parent::connect();
                if ($query = $conn->prepare(self::$queries->setDisplayName)) {
                    $query->bind_param('si', $newDsiplayName, self::$user->id);
                    $query->execute();
                    $success = $query->affected_rows > 0 ? true : false;
                    $query->close();
                    $conn->close();
                    if ($success) {
                        self::$user->displayName = $newDsiplayName;
                        self::$token_handler = new tokenHandler(self::$user);
                    }
                    return $success;
                }
                $conn->close();
            }
            return false;
        }

        public function changePW($PW, $newPW) {
            if (!is_string($newPass) || strlen($newPass) < 6) { return false; }
            if (self::$token->toJSON() && password_verify($password, self::$user->hash)) {
                $password = password_hash($newPass, PASSWORD_DEFAULT);
                $connObj = parent::connect();
                if (!$connObj->error) {
                    $connection = $connObj->connection;
                    if ($query = $connection->prepare(self::$setPWQuery)) {
                        $query->bind_param('si', $password, self::$user->id);
                        $query->execute();
                        $success = $query->affected_rows > 0 ? true : false;
                        $query->close();
                        $connection->close();
                        if ($success) {
                            self::$user->hash = $password;
                            self::$token = new Token(self::$user);
                        }
                        return $success ? self::$token->toJSON() : false;
                    } else { $connection->close(); }
                } else { self::databaseError($connObj->connection); }
            }
            return false;
        }

        private function getValidUser ($identifier) {
            if (!property_exists($identifier, 'username') || !property_exists($identifier, 'password')) { return false; }
            $connObj = parent::connect();
            if (!$connObj->error) {
                $connection = $connObj->connection;
                if ($query = $connection->prepare(self::$getQuery)) {
                    $query->bind_param('s', $identifier->username);
                    $query->execute();
                    $query->bind_result(
                        $id,
                        $username,
                        $authName,
                        $hash,
                        $type,
                        $locked
                    );
                    $query->fetch();
                    if (!$locked) {
                        self::$user = (object)[
                            'id' => $id,
                            'username' => $username,
                            'authName' => $authName,
                            'hash' => $hash,
                            'type' => $type,
                            'locked' => $locked
                        ];
                    }
                    $query->close();
                    $connection->close();
                } else { $connection->close(); }
            } else { self::databaseError($connObj->connection); }

            if (isset(self::$user->id) && isset(self::$user->username) && isset(self::$user->hash) && isset(self::$user->type) && isset(self::$user->locked)) {
                if (password_verify($identifier->password, self::$user->hash)) {
                    self::$token = new Token(self::$user);
                    return;
                }
            }
            self::$token = new Token(null);
        }

        private function setQueries () {
            self::$queries->get = 'SELECT * FROM USERS WHERE USERNAME = ?';
            self::$queries->setPW = 'UPDATE USERS SET HASH = ? WHERE ID = ?';
            self::$queries->setDisplayName = 'UPDATE USERS SET AUTHOR_NAME = ? WHERE ID = ?';
        }

        private static function databaseError($error) {
            $msg = 'Unable to connect to the database : '.$error;
            self::$logger->log(self::$logName, $msg);
            throw new Exception($msg);
        }

        /**
        *   @method     This function logs a message before throwing it as an Exception.
        *
        *   @param      string    : The message too log and throw as an Exception.
        *
        *   @throws     Exception :
        * */
        private function handleError ($msg) {
            // Set the message as an Exception
            $e = new Exception($msg);
            //  Create the logger instance and set the name of the logfile.
            $logger = new logger('tokenHandler_errorLog');
            //  Log the error message.
            self::$logger->log($e);
            //  Throw the Exception.
            throw $e;
        }

    }
?>