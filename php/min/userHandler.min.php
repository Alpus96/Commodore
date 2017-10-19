<?php

    require_once 'UserModel.php';
    require_once 'JWT_Store/TokenStore.php';

    class UserHandler extends UserModel {

        static private $token_store;

        function __construct() {
            parent::__construct();
            self::$token_store = new TokenStore();
        }

        function login ($username, $password) {
            if (!is_string($username) || !is_string($password)) { return false; }
            $user = parent::read($username);
            if (!$user || $user->locked != 0 || !password_verify($password, $user->hash))
            { return false; }
            return self::$token_store->create($user->id, $user);
        }

        function verifyToken ($token) {
            return self::$token_store->verify($token) ? true : false;
        }

        function logout ($token) {
            if ($user = self::$token_store->verify($token)) {
                return self::$token_store->destroy($token, $user->id);
            } else { return false; }
        }

        function changePassword ($token, $password, $new_pass) {
            if (!is_string($token) || !is_string($password) || !is_string($new_pass))
            { return false; }
            $user = self::$token_store->verify($token);
            if (!$user || !password_verify($password, $user->hash)) { return false; }
            $n_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $updated = parent::updateHash($user->username, $n_hash);
            if ($updated) {
                $user->hash = $n_hash;
                return self::$token_store->update($token, $user);
            }
            return false;
        }

        function getDisplayName ($token) {
            $user = self::$token_store->verify($token);
            return $user->display_name;
        }

        function changeDisplayName ($token, $password, $new_name) {
            if (!is_string($token) || !is_string($password) || !is_string($new_name))
            { return false; }
            $user = self::$token_store->verify($token);
            if (!$user || !password_verify($password, $user->hash)) { return false; }
            $updated = parent::updateDisplayName($user->username, $new_name);
            if ($updated) {
                $user->display_name = $new_name;
                return self::$token_store->update($token, $user);
            }
            return false;
        }

    }
?>