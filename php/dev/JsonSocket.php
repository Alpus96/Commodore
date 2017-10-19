<?php
    /**
    *   This class handels CRUD actions to .json files in the json library.
    *
    *   @uses           logger
    *
    *   @category       Datastoreage
    *   @package        dataSockets
    *   @subpackage     json
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'Logger.php';

    class JsonSocket {

        static private $lib_path;

        /**
        *   @method     Ensures that the json library folder exists.
        *
        *   @param      string  The path to the desired library folder, if any.
        * */
        function __construct ($lib_path = false) {
            //  Check if a library path was passed.
            if ($lib_path && is_string($lib_path)) {
                //  Set the library path to the passed path.
                self::$lib_path = $lib_path;
            }
            //  Use default if no library path was passed.
            self::$lib_path = self::$lib_path ? self::$lib_path : __DIR__.'/json_lib/';
            //  Try creating the library folder if it does not already exist.
            if (!file_exists(self::$lib_path)) {
                try { mkdir(self::$lib_path); }
                catch (Exception $e)
                { self::logException($e); }
            }
        }

        /**
        *   @method     Creates a new json file if it does not
        *               exist and writes passed data to it.
        *
        *   @param      string  The name of the file to create.
        *   @param      object  The data to write to the json file.
        * */
        function create ($file_name, $data = null) {
            //  Concatinate the absolute path to the new json file.
            $file_path = self::$lib_path.$file_name.'.json';
            //  Create the file.
            try { touch($file_path); }
            catch(Exeption $e)
            { self::logException($e); }
            //  Continue if successful.
            //  If data was passed return the update success.
            if ($data != null) {
                return self::update($file_name, $data);
            }
            //  If no data was passed return true.
            return true;
        }

        /**
        *   @method     Reads the contents of the json file.
        *
        *   @param      string  The name of the file to read.
        *
        *   @return     object  The parsed data from the json file.
        * */
        function read ($file_name) {
            //  Concatinate the absolute path to the new json file.
            $file_path = self::$lib_path.$file_name.'.json';
            //  Confirm that the json file exists.
            if (file_exists($file_path)) {
                //  If the json file exists return its parsed data.
                return json_decode(file_get_contents($file_path));
            }
            //  If the json file did not exist return false.
            return false;
        }

        /**
        *   @method     Overwrites a json file with passed data.
        *
        *   @param      string  The name of the json file to write to.
        *   @param      object  The data to write to the json file.
        *
        *   @return     boolean Signaling wheather the file was updated.
        * */
        function update ($file_name, $data) {
            //  Concatinate the absolute path to the new json file.
            $file_path = self::$lib_path.$file_name.'.json';
            //  If the file exists write the new data to it.
            if (file_exists($file_path)) {
                return file_put_contents(
                    $file_path,
                    json_encode(
                        $data,
                        JSON_PRETTY_PRINT
                    )
                );
            }
            //  If the file did not exist return false.
            return false;
        }

        /**
        *   @method     Removes a json file from the library.
        *
        *   @param      string  The name of the file to delete.
        *
        *   @return     boolean Signaling wheather the file was removed.
        * */
        function delete ($file_name) {
            //  Concatinate the absolute path to the new json file.
            $file_path = self::$lib_path.$file_name.'.json';
            //  If the file exists remove it.
            if (file_exists($file_path)) {
                return unlink($file_path);
            }
            //  If the file did not exist return true,
            //  as it was the same result as removing it.
            return true;
        }

        /**
        *   @method     This function logs a message before throwing it as an Exception.
        *
        *   @param      string    : The message too log and throw as an Exception.
        * */
        private function logException ($msg) {
            //  Create the logger instance and set the name of the logfile.
            $logger = new logger('jsonSocket_errorLog');
            // Throw the message as an Exception
            try { throw new Exception($msg); }
            catch (Exception $e)
            //  Log the Exception.
            { $logger->log($e); }
        }

    }
?>
