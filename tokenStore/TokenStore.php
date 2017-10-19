<?php

    require_once 'lib/StoreModel.php';
    require_once 'lib/JsonSocket.php';
    require_once 'lib/Logger.php';
    require_once 'lib/jwt/JWT.php';

    class TokenStore extends StoreModel{

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
                    "key_length" => 256,
                    "valid_for" => 1200
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
            $unix = time();
            if ($token) {
                $saved = false;
                if (parent::isInStore($id))
                { $saved = parent::updateInStore($id, $token, $salt, $unix); }
                else { $saved = parent::saveToStore($id, $token, $salt, $unix); }
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

    $token_store = new TokenStore();

    $token = $token_store->create(324, ['user', 'bhalf', 324]);

    //$t_obj = $token_store->verify($token);
?>