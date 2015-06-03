<?php
require_once ('soapclient/SforceEnterpriseClient.php');

class module_sf_ep_auth extends EfrontModule
{

    private $_salt = "";
    private $_SFuser = "";
    private $_SFpass = "";
    private $_SFtoken = "";

    private $_groupID = null;

    private $_connection;


    public function __construct() {
        require( dirname(__FILE__) . '/config.php');
        $this->_salt = $salt;
        $this->_SFuser = $SFuser;
        $this->_SFpass = $SFpass;
        $this->_SFtoken = $SFtoken;

        if(isset($groupID)) $this->_groupID = $groupID;

        $this->_connection = new SforceEnterpriseClient();
        $this->_connection->createConnection(dirname(__FILE__) . "/soapclient/enterprise_aiesec_gery.wsdl.xml");
        $this->_connection->login($this->_SFuser, $this->_SFpass . $this->_SFtoken);
    }

    public function getName() {
        return "SF-EP Authentification";
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
        $r = $this->_connection->query("SELECT Name, PersonEmail, Password__c FROM Account WHERE RecordType.Id='01220000000MJnD' AND PersonEmail='" . $user . "'");
        if(count($r->records) == 0) {
            return false;
        } else {
            $n = explode(' ', $r->records[0]->Name);
            $ln = $n[(count($n) - 1)];
            unset($n[(count($n) - 1)]);
            $fn = implode(' ', $n);
            return array(
                'login' => $r->records[0]->PersonEmail,
                'name' => $fn,
                'surname' => $ln,
                'email' => $r->records[0]->PersonEmail,
                'active' => 1,
                'pw_mode' => 'sf-ep',
                'encrypted_password' => $r->records[0]->Password__c
            );
        }
    }

    /**
     * Code that checks if the password encryption mode is provided by this module and if yes encrypts the given password
     *
     * @return boolean False if Module can not provide the encryption mode, or else String with encrypted $password
     * @param string $password
     * @param string $mode
     */
    public function createPassword($password, $mode) {
        if($mode == 'sf-ep') {
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
        if($mode == 'sf-ep') {
            $r = $this->_connection->query("SELECT Password__c FROM Account WHERE RecordType.Id='01220000000MJnD' AND PersonEmail='" . $user . "' LIMIT 1");
            if (count($r->records) == 0) {
                return false;
            } else {
                return $r->records[0]->Password__c;
            }
        }
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

    public function onNewUser($user) {
        if($this->_groupID !== null) {
            $user = EfrontUserFactory :: factory ($user);
            if($user->user['pw_mode'] == 'sf-ep') {
                $user->addGroups($this->_groupID);
            }
        }
    }
}
?>