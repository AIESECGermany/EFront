<?php
require_once( dirname(__FILE__) . '/gis-wrapper/AuthProviderUser.php');
require_once( dirname(__FILE__) . '/gis-wrapper/GIS.php');

class module_gis_auth extends EfrontModule
{

    private $_salt;

    private $_offices;

    private $_membersOnly;

    public function __construct() {
        require( dirname(__FILE__) . '/config.php');
        $this->_salt = $salt;
        $this->_offices = $offices;
        $this->_membersOnly = $membersOnly;
    }

    public function getName() {
        return "GIS Authentification";
    }

    public function getPermittedRoles() {
        return array("administrator");
    }

    /**
     * Code that gets executed during the user object factory, when the user is not found.
     *
     * @return boolean False if Module can not provide the user, or Array with user data to create a new user (mandatory keys: login, name, surname, email)
     * @param string $user
     * @param string $password
     * @param mixed $forceType
     */
    public function onUserNotFound($user, $password, $forceType) {
        if( $password === false && isset($_POST['submit_login']) && trim($_POST['password']) != "" && $user === $_POST['login'] && eF_checkParameter($user, 'login') ) {
            $password = $_POST['password'];
        }
        try {
            $User = new \GIS\AuthProviderUser($user, $password);
            $gis = new \GIS\GIS($User);

            foreach ($gis->current_person as $p) {
                if(isset($p->current_office->id)) {
                    if (in_array($p->current_office->id, $this->_offices)) {
                        if($this->_membersOnly === false || (isset($p->current_position->id) && $p->current_position->id != "")) {
                            return array(
                                'login' => $user,
                                'name' => $p->person->first_name,
                                'surname' => $p->person->last_name,
                                'email' => $p->person->email,
                                'active' => 1,
                                'pw_mode' => 'gis',
                                'encrypted_password' => sha1($this->_salt . $password)
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Code that checks if the password encryption mode is provided by this module and if yes encrypts the given password
     *
     * @return boolean False if Module can not provide the encryption mode, or else String with encrypted $password
     * @param string $password
     * @param string $mode
     */
    public function createPassword($password, $mode) {
        if($mode == 'gis') {
            return sha1($this->_salt . $password);
        } else {
            return false;
        }
    }

    /**
     * Code that checks if the user is provided by this module and if yes, returns the encrypted password
     *
     * @return boolean False if Module can not provide the user, or else String with encrypted password
     * @param string $user
     * @param string $mode
     */
    public function getPassword($user, $mode) {
        // if the login form was send and the $user matches the username, then we use this data to authenticate against the GIS and if not we can not provide the password here (that's why we store the hash in the database)
        if( isset($_POST['submit_login']) && trim($_POST['password']) != "" && $user === $_POST['login'] && eF_checkParameter($user, 'login')) {
            try {
                $User = new \GIS\AuthProviderUser($user, $_POST['password']);
                if($User->getToken() != "") {
                    $password = sha1($this->_salt . $_POST['password']);
                    eF_updateTableData("users", array("password" => $password), "login='" . $user . "' AND password!='" . $password . "'");
                }
            } catch(\GIS\InvalidCredentialsException $e) {
                // set the password to '' when somebody logs in with that password but it's wrong, through we use hashes it's safe because a hash is never ''
                eF_updateTableData("users", array("password" => ''), "login='" . $user . "' AND password='" . sha1($this->_salt . $password) . "'");
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Code that checks if the user is provided by this module and if yes set a new password if possible.
     *
     * @return boolean False if Module wants to deny this change, or else true.
     * @param string $user
     * @param string $password
     */
    public function setPassword($user, $password) {
        return false;
    }
}
?>