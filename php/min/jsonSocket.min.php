<?php
    require_once 'logger.php';

    class jsonSocket {

        static private $lib_path;

        function __construct ($lib_path = false) {
            if ($lib_path && is_string($lib_path))
            { self::$lib_path = $lib_path; }
            else { self::$libraryPath = dirname(__FILE__).'/json/'; }
            if (!file_exists(self::$lib_path)) {
                try { mkdir($lib_path); }
                catch (Exception $e)
                { self::handleError($e); }
            }
        }

        function create ($file_name, $data = null) {
            $file_path = self::$lib_path.$file_name.'.json';
            if (file_exists($file_path))
            { return true; }
            try { touch($file_path); }
            catch(Exeption $e)
            { self::handleError($e); }
            if ($data != null)
            { return self::update($file_name, $data); }
            return true;
        }

        function read ($file_name) {
            $file_path = self::$lib_path.$file_name.'.json';
            if (file_exists($file_path))
            { return json_decode(file_get_contents($file_path)); }
            return false;
        }

        function update ($file_name, $data) {
            $file_path = self::$lib_path.$file_name.'.json';
            if (file_exists($file_path))
            { return file_put_contents($file_path,
                json_encode($data, JSON_PRETTY_PRINT));}
            return false;
        }

        function delete ($file_name) {
            $file_path = self::$lib_path.$file_name.'.json';
            if (file_exists($file_path))
            { return unlink($file_path); }
            return true;
        }

        private function handleError ($msg) {
            $e = new Exception($msg);
            $logger = new logger('jsonSocket_errorLog');
            $logger->log($e);
            throw $e;
        }

    }
?>
