<?php
    /**
    *   This class handels creating, verifying,
    *   updating and deleting json encoded tokens.
    *
    *   @uses           tokenModel
    *   @uses           jsonSocket
    *   @uses           JWT
    *   @uses           logger
    *
    *   @category       User verification
    *   @package        Users
    *   @subpackage     User tokens
    *   @version        1.0
    *   @since          1.0
    *   @deprecated     ---
    * */

    require_once 'tokenModel.php';
    require_once 'jsonSocket.php';
    require_once 'jwt/JWT.php';
    require_once 'logger.php';

    class tokenHandler extends tokenModel {

        static private $jwt;
        static private $key;

        /**
        *   @method     On construct of this class the token key is set and the
        *               json webb token class is instansed.
        *
        *   @throws     Exception     : Unable to read token configuration file.
        * */
        function __construct() {
            parent::__construct();
            //  Read the token configuration file.
            $json_socket = new jsonSocket();
            $conf = $json_socket->read('jwtConfig');
            //  Confirm the configuration file was read.
            if ($conf === false) {
                $logger = new logger('tokenHandler_errorLog');
                $e = new Exception('Unable to read token configuration file.');
                $logger->log($e);
                throw $e;
            }
            //  If the configuration file was read confirm the token key was set.
            if (!property_exists($conf, 'key')) {
                //  If the key was not set, generate one and save it.
                self::$key = self::newKeyGen();
                $key_obj = (object)['key' => self::$key];
                $json_socket->create('jwtConfig', $key_obj);
            }
            //  If a key was generated keep it as it now has been set in the configuraion,
            //  otherwise use the configuration key that existed.
            self::$key = self::$key ? self::$key : $conf->key;
            //  Now that the key has been set instance the Json Web Token class.
            self::$jwt = new JWT();
        }

        /**
        *   @method     This function encodes an object to a token string.
        *
        *   @param      object        : The object to encode. {username, ...}
        *
        *   @return     string        : Json encoded object. {username, token_string}
        * */
        function create ($object) {
            //  Confirm the passed parameter is an object that contains the property 'username'.
            if (!is_object($object) || !property_exists($object, 'username')) { return false; }
            //  Encode the object.
            $t_string = self::$jwt->encode($object, self::$key);
            //  Check for existing entries.
            $existing = parent::read($object->username);
            //  Delete them if any.
            if ($existing) { parent::delete($object->username); }
            //  Then create the new token an return it as a json encoded object.
            if (parent::create($object->username, $t_string)) {
                $t_obj = parent::read($object->username)[0];
                unset($t_obj->unix);
                return json_encode($t_obj);
            }
        }

        /**
        *   @method     Verifies that the given token is a valid token.
        *
        *   @param      string        : The json ecoded token object.
        *
        *   @return     object|false  : The JWT decoded token object or false if invalid.
        * */
        function verify ($token) {
            //  If not a string it is not a token, return false.
            if (!is_string($token)) { return false; }
            //  If a string try decoding as a token.
            $t_obj;
            try { $t_obj = json_decode($token); }
            catch (Exception $e) { return false; }
            //  If the decoding succeded check it contains the correct properties.
            if (!is_object($t_obj)) { return false; }
            $un_e = property_exists($t_obj, 'username');
            $t_e = property_exists($t_obj, 'token');
            if (!$un_e || !$t_e) { return false; }
            //  If properties are correct read the token from the database to compare.
            $existing = parent::read($t_obj->username)[0];
            //  If it does not exist in the database it is not valid.
            if (!$existing) { return false; }
            //  Confirm the token has not existed for to long.
            if (strtotime($existing->unix) + (10*60) < time()) { return false; }
            //  Then remove the timestamp to compare.
            unset($existing->unix);
            //  Confirm the existing and the given token objects are identical.
            if ($existing !== $t_obj) { return false; }
            //  If identical decode the token string.
            $t_decoded = self::$jwt->decode($t_obj->token, self::$key);
            //  If the token string was decoded successfully update the timestamp.
            $updated = $t_decoded ? parent::updateUnix($t_obj->username) : false;
            //  Return the decoded object if the timestamp updated.
            return $updated ? $t_decoded : false;
        }

        /**
        *   @method     Updates the token string to a new encoded object.
        *
        *   @param      string        : The current token.
        *   @param      object        : The new object to encode to a token string.
        *
        *   @return     string|false  : The json encoded token or false if unsuccessful.
        * */
        function update ($token, $newObject) {
            //  Verify the given token is a valid json encoded token object.
            if (!self::verify($token)) { return false; }
            //  Confirm the new object has the nessecary properties.
            if (!is_object($newObject) || !property_exists($newObject, 'username'))
            { return false; }
            //  Decode the ocld token as a json.
            $t_obj = json_decode($token);
            //  Confirm the old username and the new match.
            if ($t_obj->username !== $newObject->username) { return false; }
            //  Encode the new token string.
            $t_obj->token = self::$jwt->encode($newObject, self::$key);
            //  Update the token in the database.
            $updated = parent::updateToken($t_obj->username, $t_obj->token);
            //  Return the new json encoded token object if the token was updated.
            return $updated ? json_encode($t_obj) : false;
        }

        /**
        *   @method     This function removed the token from the database.
        *
        *   @param      string        : The json encoded token string.
        *
        *   @return     boolean       : True if successful, false if not.
        * */
        function destroy ($token) {
            //  Verify the token is a valid json encoded token object.
            $obj = self::verify($token);
            //  Return true if not valid, as it does not exist.
            if (!$obj) { return true; }
            //  If it did exist delete it and return the success status.
            return parent::delete($obj->username);
        }

    }
?>
