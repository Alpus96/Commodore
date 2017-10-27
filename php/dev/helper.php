<?php

    require_once ROOT_PATH.'library/debug/Logger.php';

    class ErrorHelper {

        static private $logger;

        function __construct ($helper_id)
        { self::$logger = new Logger($helper_id); }

        function logException ($msg) {
            try { throw new Exception($msg); }
            catch (Exception $e)
            { self::$logger->log($e); }
        }

    }

    class VarHelper {

        function __construct () {}

        function generateString ($length = 16) {
             if (!is_integer($length)) { return false; }
             $nums = '01234567890123456789';
             for ($i = 0; $i < 6; $i++) { $nums.= rand(0, 9); }
             $sm_chars = 'abcdefghijklmnopqrstuvwxyz';
             $lg_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
             $chars = str_shuffle($nums.$sm_chars.$lg_chars);
             $chars_len = strlen($chars);
             $new_string = '';
             for ($i = 0; $i < $length; $i++)
             { $new_string.= $chars[rand(0, $chars_len-1)]; }
             return $new_string;
        }

    }

    class QueryHelper extends MysqlSocket {

        protected function __construct () {
            parent::__construct();
        }

        /**
         * NOTE:    Log all queries to be able to reverse?
         */

        protected function dataFrom ($query_str, $params) {
            if (!is_string($query_str) || !is_object($params) && !is_array($params)) { return false; }

        }

        protected function resultOf ($query_str) {
            if (!is_string($query_str)) { return false; }
        }

    }

    

?>