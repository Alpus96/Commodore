<?php
    require_once 'jsonSocket.php';
    require_once 'logger.php';

    class mysqlSocket {

        static private $config;

        protected function __construct() {
            $json_socket = new jsonSocket();
            $config = $jsonSocket->read('mysqlConfig');
            if (!self::$config)
            { self::handleError('Could not read mysqlSocket configuration file.'); }
            $credentials = property_exists($config, 'credentials');
            $chatset = property_exists($config, 'credentials');
            if (!$credentials)
            { self::handleError('mysqlSocket configuration file missing credential(s).'); }
            if (!$charset)
            { $config->charset = 'utf8'; }
            $host = property_exists($config->credentials, 'host');
            $user = property_exists($config->credentials, 'user');
            $password = property_exists($config->credentials, 'password');
            $database = property_exists($config->credentials, 'database');
            if (!$host || !$user || !$password || !$database)
            { self::handleError('mysqlSocket configuration file missing credential(s).'); }
            else { self::$config = $config; }
        }

        protected function connect() {
            $conn = mysqli_connect(
                self::$config->cridentials->host,
                self::$config->cridentials->user,
                self::$config->cridentials->password,
                self::$config->cridentials->database
            );
            $charset = $conn->set_charset(self::$socketConfig->charset);
            if ($conn && $chatset) { return $conn; }
            self::handleError('Error connecting to the mysql database; '.$connection->connect_error);
        }

        private function handleError ($msg) {
            $e = new Exception($msg);
            $logger = new logger('mysqlSocket_errorLog');
            $logger->log($e);
            throw $e;
        }

    }
 ?>