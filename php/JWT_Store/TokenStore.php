<?php

    /**
    *   This class handels creating, verifying, updating
    *   and destroying tokens in the token store.
    *
    *   @uses           TokenStorage
    *   @uses           JWT
    *   @uses           JsonSocket
    *   @uses           Logger
    *
    *   @category       User verification
    *   @package        JWT_Store
    *   @subpackage     TokenHandler
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'lib/TokenStorage.php';
    require_once 'lib/jwt/JWT.php';
    require_once 'JsonSocket.php';
    require_once 'Logger.php';

    class TokenStore extends TokenStorage {

        static private $config;
        static private $jwt;

        /**
        *   @method     Reads the configuration file, or creates one with default values if none.
        *               Aslo instances JWT class to use for making tokens.
        * */
        function __construct () {
            parent::__construct();
            //  Intance the json socket class and read the config file.
            $json_socket = new JsonSocket();
            $conf = $json_socket->read('TokenStoreConfig');
            $kl_e = $conf ? property_exists($conf, 'key_length') : false;
            $vf_e = $conf ? property_exists($conf, 'valid_for') : false;
            //  Confirm the config was setup and containes required information.
            if (!$conf || !$kl_e || !$vf_e) {
                //  If not create it with default values.
                self::logException("Invalid token store configuration. Recreating from default values.\n");
                self::$config = (object)[
                    "key_length" => 64,
                    "valid_for" => 1800
                ];
                $json_socket->create('TokenStoreConfig', self::$config);
            }
            //  If the config was accepted save it to use later.
            self::$config = self::$config ? self::$config : $conf;
            //  Instance the JWT class.
            self::$jwt = new JWT();
        }

        /**
        *   @method     Created a token from payload and saves it with the given id.
        *
        *   @param      integer|string: The id to save token with.
        *   @param      object|array  : The payload to enctypt as a token.
        *
        *   @return     string        : The encrypted token string.
        * */
        function create ($id, $payload) {
            //  Confirm the passed parmas are of correct type.
            if (!is_numeric($id) || !is_array($payload) && !is_object($payload)) { return false; }
            //  Generate a key to lock the token encryption.
            $salt = self::generateSalt(self::$config->key_length);
            //  Encode the token with the generated key.
            $token = self::$jwt->encode($payload, $salt);
            //  If successful save the token.
            if ($token) {
                $saved = false;
                //  Update in token store if the id is already exists.
                if (parent::isInStore($id))
                { $saved = parent::updateInStore($id, $token, $salt); }
                //  If new id save it as a new entry.
                else { $saved = parent::saveToStore($id, $token, $salt); }
                //  If the token was successfully saved in token store return the token string.
                return $saved ? $token : false;
            }
            //  If the token could not be encoded return false.
            return false;
        }

        /**
        *   @method     verifies that the given token sring is a valid token string and
        *               that it has not expired.
        *
        *   @param      string        : The token string.
        *
        *   @return     object|false  : The decoded token or false if not valid.
        * */
        function verify ($token) {
            //  Confirm the token string is a string.
            if (!is_string($token)) { return false; }
            //  Get the information from the store.
            $info = parent::getFromStore($token);
            //  Confirm it existed in the store.
            if (!info) { return false; }
            //  Confirm it has not expired.
            $expired = strtotime($info->unix) + self::$config->valid_for < time();
            if ($expired) { return false; }
            unset($info->unix);
            //  Try decoding the token.
            $valid = true;
            $t_obj = null;
            try { $t_obj = self::$jwt->decode($token, $info->salt); }
            catch (Exception $e) {
                $valid = false;
                self::logException($e);
            }
            //  If successful return the decoded token, return false if not.
            return $t_obj != null && $valid != false ? $t_obj : false;
        }

        /**
        *   @method     Re-encodes the token, with new payload if given, and saves the new
        *               token and encryption key in token store.
        *
        *   @param      string        : The current token string.
        *   @param      object|array  : The new payload to encrypt as the token.
        *
        *   @return     string|false  : The new token string or false if unsuccessful.
        * */
        function update ($token, $new_t_obj = null) {
            //  Confirm the token string is a string.
            if (!is_string($token)) { return false; }
            //  Decode the old token string.
            $t_obj;
            if (!$t_obj = self::verify($token)) { return false; }
            $id = parent::getFromStore($token)->id;
            //  Generate a new salt to encode the token again.
            $n_salt = self::generateSalt(self::$config->key_length);
            //  If a new token object was passed encode that instead of the old token object.
            $n_token;
            if ($new_t_obj != null) {
                if (!is_object($new_t_obj) && !is_array($new_t_obj)) { return false; }
                $n_token = self::$jwt->encode($new_t_obj, $n_salt);
            } else { $n_token = self::$jwt->encode($t_obj, $n_salt); }
            //  Then save the new token string and generated salt.
            $updated = parent::updateInStore($id, $n_token, $n_salt);
            //  If updated successfully return the new token string, else return false.
            return $updated ? $n_token : false;
        }

        /**
        *   @method     Removes token information from the token store for the given token with id.
        *
        *   @param      string        : The token to delete from the store.
        *   @param      integer|string: The id of the token.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function destroy ($token, $id) {
            //  Confirm the given id is of correct type.
            if (!is_string($id) && !is_integer($id)) { return false; }
            //  Verify the given token.
            if (!self::verify($token)) { return false; }
            //  Verify the id is the id of the given token.
            $info = parent::getFromStore($token);
            if ($info->id != $id) { return false; }
            //  Delete the row with id in token store and return status.
            return parent::deleteFromStore($id);
        }

        /**
        *   @method     Generates a random string to use as encoding key for a token.
        *
        *   @param      integer       : The length of the string.
        *
        *   @return     string        : A random string with length of given number.
        * */
        private function generateSalt ($len) {
            //  Confirm the given length is a number.
            if (!is_integer($len)) { return false; }
            //  Set witch numbers to select from. (With 1/3 probability)
            $nums = '01234567890123456789';
            for ($i = 0; $i < 6; $i++) { $nums.= rand(0, 9); }
            //  Set witch small letters to select from. (With 1/3 probability)
            $sm_chars = 'abcdefghijklmnopqrstuvwxyz';
            //  Set witch big letters to select from. (With 1/3 probability)
            $lg_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            //  Shuffle the string to select from.
            $chars = str_shuffle($nums.$sm_chars.$lg_chars);
            $chars_len = strlen($chars);
            $new_key = '';
            //  Randomly pick characters from the string of available chars until given length.
            for ($i = 0; $i < $len; $i++) {
                $new_key.= $chars[rand(0, $chars_len-1)];
            }
            //  Return the random string with given length.
            return $new_key;
        }

        /**
        *   @method     Enters an exception stack trace int a log file.
        *
        *   @param      string|Exception: The message string or exception to log.
        * */
        private function logException ($e) {
            //  Instance the logger class.
            $logger = new Logger('TokenStore__ExceptionLog');
            //  Throw the given message or exception.
            try { throw new Exception($e); }
            catch (Exception $e)
            //  Catch it to enter in log file.
            { $logger->log($e); }
        }

    }
?>