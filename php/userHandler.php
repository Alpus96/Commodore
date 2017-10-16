<?php

    require_once 'userModel.php';
    require_once 'tokenHandler.php';

    class userHandler extends userModel {

        static private $token_handler;

        function __construct() {
            parent::__construct();
            self::$token_handler = new tokenHandler();
        }

        function login ($username, $password) {
            if (!is_string($username) || !is_string($password)) { return false; }
            $user = parent::read($username);
            if (!$user || $user->locked != 0 || !password_verify($password, $user->hash))
            { return false; }
            return self::$token_handler->create($user);
        }

        function logout ($token) {
            return self::$token_handler->destroy($token);
        }

        function changePassword ($token, $new_pass) {
            if (!is_string($token) || !is_string($new_pass)) { return false; }
            $user = self::$token_handler->verify($token);
            if (!$user) { return false; }
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            return parent::updateHash($user->username, $hash);
        }

        function changeDisplayName ($token, $new_name) {
            if (!is_string($token) || !is_string($new_name)) { return false; }
            $user = self::$token_handler->verify($token);
            if (!$user) { return false; }
            return parent::updateDisplayName($user->username, $new_name);
        }

    }
?>