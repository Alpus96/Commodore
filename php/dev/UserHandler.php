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

    require_once 'UserModel.php';
    require_once 'JWT_Store/TokenStore.php';

    class UserHandler extends UserModel {

        static private $token_store;

        /**
        *   @method     Constructs the parent user model and an instanse of the token handler.
        * */
        function __construct() {
            parent::__construct();
            self::$token_store = new TokenStore();
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
            return self::$token_store->create($user->id, $user);
        }

        /**
        *
        * */
        function verifyToken ($token) {
            return self::$token_store->verify($token) ? true : false;
        }

        /**
        *   @method     Destroys the user authentication token.
        *
        *   @param      string        : The users token object string.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function logout ($token) {
            //  Destroy the registered token if it is valid.
            if ($user = self::$token_store->verify($token)) {
                return self::$token_store->destroy($token, $user->id);
            } else { return false; }
        }

        /**
        *   @method     Updates the users password hash.
        *
        *   @param      string        : The users token object string.
        *   @param      string        : The new password to hash and save.
        *
        *   @return     boolean       : Success status of the action.
        * */
        function changePassword ($token, $password, $new_pass) {
            //  Confirm the given token, verification password and new password are strings.
            if (!is_string($token) || !is_string($password) || !is_string($new_pass))
            { return false; }
            //  Verify the token object string and the given verification password.
            $user = self::$token_store->verify($token);
            if (!$user || !password_verify($password, $user->hash)) { return false; }
            //  Hash the new password and save it.
            $n_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $updated = parent::updateHash($user->username, $n_hash);
            //  If the update was saved update the token to and return it.
            if ($updated) {
                $user->hash = $n_hash;
                return self::$token_store->update($token, $user);
            }
            //  If the new hash was not saved return false.
            return false;
        }

        /**
        *   @method     This function returns the currently set displayname for the user.
        *
        *   @param      string        : The user verification token.
        *
        *   @return     string        : The display name of the token owner.
        * */
        function getDisplayName ($token) {
            $user = self::$token_store->verify($token);
            return $user->display_name;
        }

        /**
        *   @method     Updates the display name of a user.
        *
        *   @param      string        : The users token object string.
        *   @param      string        : The new display name.
        *
        *   @return     string|boolean: On success a new token string of false if not.
        * */
        function changeDisplayName ($token, $password, $new_name) {
            //  Confirm the given token, verification password and new displayname are strings.
            if (!is_string($token) || !is_string($password) || !is_string($new_name))
            { return false; }
            //  Verify the token object string and the given verification password.
            $user = self::$token_store->verify($token);
            if (!$user || !password_verify($password, $user->hash)) { return false; }
            //  Update the display name.
            $updated = parent::updateDisplayName($user->username, $new_name);
            //  If the update was saved update the token to and return it.
            if ($updated) {
                $user->display_name = $new_name;
                return self::$token_store->update($token, $user);
            }
            //  If the new hash was not saved return false.
            return false;
        }

    }
?>