<?php

    class FileHelper {

        static private $root_path;

        protected function __construct ($root_path) {
            if (!is_string($root_path)) { return false; }
            if (!file_exists($root_path) || is_file($root_path)) { return false; }
            //  Put / at end of string if it is not there
            if (substr($root_path, count($root_path)) !== '/') { $root_path.= '/'; }
            self::$root_path = $root_path;
        }

        protected function create ($dir, $file, $data = null) {
            if (!is_string($dir)) { return false; }
            $path = self::$root_path.$dir;
            //  check dir(s) to file, create if not there
            $dirs = explode('/', $dir);
            $sub_path = '';
            foreach ($dirs as $sub_dir) {
                $mk_dir_path = self::$root_path.$sub_path.$sub_dir;
                if (!file_exists($mk_dir_path))
                { mkdir($mk_dir_path); }
                $sub_path.= $sub_dir.'/';
            }
            //  create the file
            $file_handle = fopen($path.$file, 'w');
            if (!is_string($data) && $data != null)
            { $data = json_encode($data); }
            fwrite($file_handle, $data);
            fclose($file_handle);
        }

        protected function read ($rel_path) {
            $file_path = self::$root_path.$rel_path;
            if (!is_file($file_path)) { return false; }
            $file_handle = fopen($file_path, 'r');
            $data = fread($file_handle, filesize($file_path));
            if (preg_match('/^{.+}$/', $data)) {
                $decoded = null;
                try { $decoded = json_decode($data); }
                catch (Exception $e) { $decoded = false; }
                if ($decoded !== false) { $data = $decoded; }
            }
            return $data;
        }

        protected function update ($rel_path, $data) {
            if (!is_string($rel_path) || !file_exists($rel_path)) { return false; }
            if (!is_string($data)) { $data = json_encode($data); }
            return file_put_contents($rel_path, $data);
        }

        protected function append ($rel_path, $data) {
            if (!is_string($rel_path) || !file_exists($rel_path)) { return false; }
            $prev_contents = self::read($rel_path);
            $file_handle = fopen($rel_path, 'w+');
            if (is_string($data)) {
                $file_contents = fread($file_handle, filesize($rel_path));
                $success = fwrite();
            }
        }

        protected function move ($org_path, $new_path, $opt) {

        }

        protected function delete () {
            
        }

    }

    class test extends FileHelper {
        function __construct () {
            parent::__construct(__DIR__);
            parent::create('/fileTests/', 'testFile.txt',  ["str" => "this should be handled as json contents."]);
            $read = parent::read('fileTests/testFile.txt');
            

        }
    }

    //$test = new test();

    $data;
    $data = json_decode("[{\"first\":\"first text\"},{\"second\":\"second text\"}]");
    echo $data ? json_encode($data) : 'no data';

?>