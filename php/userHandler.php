<?php
    /**
    *   This class handels authenticating
    *   users and basic account management.
    *
    *   @uses           userModel
    *   @uses           tokenHandler
    *
    *   @category       Handling users
    *   @package        Users
    *   @subpackage     User_handling
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'userModel.php';
    require_once 'tokenHandler.php';

    class userHandler extends userModel {

        static private $token_handler;

        /**
        *   @method     Constructs the parent user model and an instanse of the token handler.
        * */
        function __construct() {
            parent::__construct();
            self::$token_handler = new tokenHandler();
        }

        /**
        *   @method     Authenticates and creates a token for a user.
        *
        *   @param      string        : The username of the account.
        *   @param      string        : The matching account password.
        *
        *   @return     string|false  : The token string if authentication succeded, false if not.
        * */
        function login ($username, $password) {
            //  Confirm the given username and password are strings.
            if (!is_string($username) || !is_string($password)) { return false; }
            //  Read the user information for the username.
            $user = parent::read($username);
            //  Confirm an account was found, that it is not locked and
            //  that the given password matches.
            if (!$user || $user->locked != 0 || !password_verify($password, $user->hash))
            { return false; }
            //  If the credentials where valid return a new token object string.
            return self::$token_handler->create($user);
        }

        /**
        *   @method     Destroys the user authentication token.
        *
        *   @param      string        : The users token object string.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function logout ($token) {
            //  Destroy the registered token.
            return self::$token_handler->destroy($token);
        }

        /**
        *   @method     Updates the users password hash.
        *
        *   @param      string        : The users token object string.
        *   @param      string        : The new password to hash and save.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function changePassword ($token, $new_pass) {
            //  Confirm the given token and new password are strings.
            if (!is_string($token) || !is_string($new_pass)) { return false; }
            //  Verify the token object string.
            $user = self::$token_handler->verify($token);
            if (!$user) { return false; }
            //  Hash the new password.
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            //  Update the hash through the user model and return result.
            return parent::updateHash($user->username, $hash);
        }

        /**
        *   @method     Updates the display name of a user.
        *
        *   @param      string        : The users token object string.
        *   @param      string        : The new display name.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function changeDisplayName ($token, $new_name) {
            //  Confirm the given token object string and display name are strings.
            if (!is_string($token) || !is_string($new_name)) { return false; }
            //  Verify the token object string.
            $user = self::$token_handler->verify($token);
            if (!$user) { return false; }
            //  Update the display name and return the result.
            return parent::updateDisplayName($user->username, $new_name);
        }

    }
?>