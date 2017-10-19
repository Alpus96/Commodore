<?php
    /**
    *   This class handels reading the database credentials,
    *   through the jsonSocket, and connecting to the database.
    *   Connection errors are logged using the logger class.
    *
    *   @uses           jsonSocket
    *   @uses           logger
    *
    *   @category       Datastoreage
    *   @package        dataSockets
    *   @subpackage     database
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'JsonSocket.php';  //  Require the jsonSocket class to read the credentials json.
    require_once 'Logger.php';      //  Require the logger class file for error logging.

    class MysqlSocket {

        static private $config;     //  MySQL database configuration.

        /**
        *   @method     Sets up an instance of the logger class, jsonSocket class and
        *               confirms that the mysqlSocket configuration file was read with
        *               the correct properties.
        * */
        protected function __construct() {
            //  Temporairaly instance the jsonSocket and read the configuration file.
            $json_socket = new jsonSocket();
            $config = $json_socket->read('MysqlConfig');
            //  If the configuration read returned false handle the error.
            if (!$config) { self::logError(0, 'Could not read mysqlSocket configuration file.'); }
            //  Set boolean values for required top level properties exist.
            $credentials = property_exists($config, 'credentials');
            $chatset = property_exists($config, 'credentials');
            //  Confirm property credentials exist.
            if (!$credentials) {
                //  If the credentials property did not exist, handle the error.
                self::logError(0, 'mysqlSocket configuration file missing credential(s).');
            }
            //  Confirm property charset exist.
            if (!$charset) {
                //  If the charset property did not exist, default to UTF-8.
                $config->charset = 'utf8';
            }
            //  Set boolean values for required credential properties.
            $host = property_exists($config->credentials, 'host');
            $user = property_exists($config->credentials, 'user');
            $password = property_exists($config->credentials, 'password');
            $database = property_exists($config->credentials, 'database');
            //  Confirm required properties exist.
            if (!$host || !$user || !$password || !$database) {
                //  If any where missing, handle the error.
                self::logError(0, 'mysqlSocket configuration file missing credential(s).');
            } else {
                //  If all required credential properties exist set the class
                //  configuration variable to current configuration.
                self::$config = $config;
            }
        }

        /**
        *   @method     This function tries to connect to the MySQL database using the
        *               set cridentials.
        *
        *   @return      mysqli_connect
        * */
        protected function connect() {
            //
            set_error_handler(function($err_no, $err_str)
            { self::logError($err_no, $err_str); });
            //  Connect to the database using the loaded cridentials.
            $conn = new mysqli (
                self::$config->credentials->host,
                self::$config->credentials->user,
                self::$config->credentials->password,
                self::$config->credentials->database
            );
            restore_error_handler();
            //  Confirm there was no error connecting to the database.
            if ($conn->connect_error) { return false; }
            //  Change the connection charset and save result as boolean.
            $charset = $conn->set_charset(self::$config->charset);
            //  If all is ok, return the connection.
            return $conn;
        }

        /**
        *   @method     This function logs a message before throwing it as an Exception.
        *
        *   @param      string    : The message too log and throw as an Exception.
        * */
        private function logError ($no, $str) {
            //  Create the logger instance and set the name of the logfile.
            $logger = new logger('mysqlSocket_errorLog');
            // Set the message as an Exception
            try { throw new Exception($no." :\t".$str); }
            catch (Exception $e)
            //  Log the error message.
            { $logger->log($e); }
        }

    }
 ?>
