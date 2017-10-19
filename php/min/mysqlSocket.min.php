<?php

    require_once 'JsonSocket.php';
    require_once 'Logger.php';

    class MysqlSocket {

        static private $config;

        protected function __construct() {
            $json_socket = new jsonSocket();
            $config = $json_socket->read('MysqlConfig');
            if (!$config) { self::logError(0, 'Could not read mysqlSocket configuration file.'); }
            $credentials = property_exists($config, 'credentials');
            $chatset = property_exists($config, 'credentials');
            if (!$credentials)
            { self::logError(0, 'mysqlSocket configuration file missing credential(s).'); }
            if (!$charset) { $config->charset = 'utf8'; }
            $host = property_exists($config->credentials, 'host');
            $user = property_exists($config->credentials, 'user');
            $password = property_exists($config->credentials, 'password');
            $database = property_exists($config->credentials, 'database');
            if (!$host || !$user || !$password || !$database)
            { self::logError(0, 'mysqlSocket configuration file missing credential(s).'); }
            else { self::$config = $config; }
        }

        protected function connect() {
            set_error_handler(function($err_no, $err_str) { self::logError($err_no, $err_str); });
            $conn = new mysqli (
                self::$config->credentials->host,
                self::$config->credentials->user,
                self::$config->credentials->password,
                self::$config->credentials->database
            );
            restore_error_handler();
            if ($conn->connect_error) { return false; }
            $charset = $conn->set_charset(self::$config->charset);
            return $conn;
        }

        private function logError ($no, $str) {
            $logger = new logger('mysqlSocket_errorLog');
            try { throw new Exception($no." :\t".$str); }
            catch (Exception $e)
            { $logger->log($e); }
        }

    }
 ?>
