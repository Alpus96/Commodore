<?php

    /**
    *   TODO:   Comment and review.
    * */

    require_once 'lib/StoreSocket.php';
    require_once 'JsonSocket.php';
    require_once 'Logger.php';
    require_once 'lib/jwt/JWT.php';

    class TokenStore extends StoreSocket {

        static private $config;
        static private $jwt;

        function __construct () {
            parent::__construct();
            $json_socket = new JsonSocket();
            $conf = $json_socket->read('TokenStoreConfig');
            $kl_e = $conf ? property_exists($conf, 'key_length') : false;
            $vf_e = $conf ? property_exists($conf, 'valid_for') : false;
            if (!$conf || !$kl_e || !$vf_e) {
                self::logException("Invalid token store configuration. Recreating from default values.\n");
                self::$config = (object)[
                    "key_length" => 64,
                    "valid_for" => 1800
                ];
                $json_socket->create('TokenStoreConfig', self::$config);
            }
            self::$config = self::$config ? self::$config : $conf;
            self::$jwt = new JWT();
        }

        function create ($id, $payload) {
            if (!is_numeric($id) || !is_array($payload) && !is_object($payload)) { return false; }
            $salt = self::generateSalt(self::$config->key_length);
            $token = self::$jwt->encode($payload, $salt);
            if ($token) {
                $saved = false;
                if (parent::isInStore($id))
                { $saved = parent::updateInStore($id, $token, $salt); }
                else { $saved = parent::saveToStore($id, $token, $salt); }
                return $saved ? $token : false;
            }
            return false;
        }

        function verify ($token) {
            if (!is_string($token)) { return false; }

            $info = parent::getFromStore($token);
            if (!info) { return false; }
            $expired = strtotime($info->unix) + self::$config->valid_for < time();
            if ($expired) { return false; }
            unset($info->unix);
            $valid = true;
            $t_obj = null;
            try { $t_obj = self::$jwt->decode($token, $info->salt); }
            catch (Exception $e) {
                $valid = false;
                self::logException($e->message);
            }
            return $t_obj != null && $valid != false ? $t_obj : false;
        }

        function update ($token, $new_t_obj = null) {
            if (!is_string($token)) { return false; }
            //  Decode old token
            $t_obj;
            if (!$t_obj = self::verify($token)) { return false; }
            $id = parent::getFromStore($token)->id;
            //  new salt to encode again.
            $n_salt = self::generateSalt(self::$config->key_length);
            //  if new token object was passed encode that instead of old token object
            $n_token;
            if ($new_t_obj != null) {
                if (!is_object($new_t_obj) && !is_array($new_t_obj)) { return false; }
                $n_token = self::$jwt->encode($new_t_obj, $n_salt);
            } else { $n_token = self::$jwt->encode($t_obj, $n_salt); }
            //  Then save the new token and salt.
            $updated = parent::updateInStore($id, $n_token, $n_salt);
            return $updated ? $n_token : false;
        }

        function destroy ($token, $id) {
            if (!is_string($id) && !is_integer($id)) { return false; }
            if (!self::verify($token)) { return false; }
            return parent::deleteFromStore($id);
        }

        private function generateSalt ($len) {
            $nums = '01234567890123456789';
            for ($i = 0; $i < 6; $i++) { $nums.= rand(0, 9); }
            $sm_chars = 'abcdefghijklmnopqrstuvwxyz';
            $lg_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $chars = str_shuffle($nums.$sm_chars.$lg_chars);
            $chars_len = strlen($chars);
            $new_key = '';
            for ($i = 0; $i < $len; $i++) {
                $new_key.= $chars[rand(0, $chars_len-1)];
            }
            return $new_key;
        }

        private function logException ($e) {
            $logger = new Logger('TokenStore__ExceptionLog');
            try { throw new Exception($e); }
            catch (Exception $e)
            { $logger->log($e); }
        }

    }
?>