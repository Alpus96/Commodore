<?php

    require_once 'userModel.php';
    require_once 'tokenHandler.php';

    class userHandler extends userModel {

        static private $token_handler;

        function __construct() {
            parent::__construct();
            self::$token_handler = new tokenHandler();
        }

    }
?>
