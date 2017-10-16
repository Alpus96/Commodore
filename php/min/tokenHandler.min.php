<?php

    require_once 'tokenModel.php';
    require_once 'jsonSocket.php';
    require_once 'jwt/JWT.php';
    require_once 'logger.php';

    class tokenHandler extends tokenModel {

        static private $jwt;
        static private $key;

        function __construct() {
            parent::__construct();
            $json_socket = new jsonSocket();
            $conf = $json_socket->read('jwtConfig');
            if ($conf === false)
            { $logger = new logger('tokenHandler_errorLog');
              $e = new Exception('Unable to read token configuration file.');
              $logger->log($e);
              throw $e; }
            if (!property_exists($conf, 'key'))
            { self::$key = self::newKeyGen();
              $key_obj = (object)['key' => self::$key];
              $json_socket->create('jwtConfig', $key_obj); }
            self::$key = self::$key ? self::$key : $conf->key;
            self::$jwt = new JWT();
        }

        function create ($object) {
            if (!is_object($object) || !property_exists($object, 'username')) { return false; }
            $t_string = self::$jwt->encode($object, self::$key);
            $existing = parent::read($object->username);
            if ($existing) { parent::delete($object->username); }
            if (parent::create($object->username, $t_string))
            { $t_obj = parent::read($object->username)[0];
              unset($t_obj->unix);
              return json_encode($t_obj); }
        }

        function verify ($token) {
            if (!is_string($token)) { return false; }
            $t_obj;
            try { $t_obj = json_decode($token); }
            catch (Exception $e) { return false; }
            if (!is_object($t_obj)) { return false; }
            $un_e = property_exists($t_obj, 'username');
            $t_e = property_exists($t_obj, 'token');
            if (!$un_e || !$t_e) { return false; }
            $existing = parent::read($t_obj->username)[0];
            if (!$existing) { return false; }
            if (strtotime($existing->unix) + (10*60) < time()) { return false; }
            unset($existing->unix);
            if ($existing !== $t_obj) { return false; }
            $t_decoded = self::$jwt->decode($t_obj->token, self::$key);
            $updated = $t_decoded ? parent::updateUnix($t_obj->username) : false;
            return $updated ? $t_decoded : false;
        }

        function update ($token, $newObject) {
            if (!self::verify($token)) { return false; }
            if (!is_object($newObject) || !property_exists($newObject, 'username'))
            { return false; }
            $t_obj = json_decode($token);
            if ($t_obj->username !== $newObject->username) { return false; }
            $t_obj->token = self::$jwt->encode($newObject, self::$key);
            $updated = parent::updateToken($t_obj->username, $t_obj->token);
            return $updated ? json_encode($t_obj) : false;
        }

        function destroy ($token) {
            $obj = self::verify($token);
            if (!$obj) { return true; }
            return parent::delete($obj->username);
        }

    }
?>
