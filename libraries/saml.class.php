<?php 
class EfrontSaml
{
	const SSO_TYPE_NONE = '0';
	const SSO_TYPE_RMUNIFY = '1';
	const SSO_TYPE_SAML = '2';
	const SSO_TYPE_LDAP = '3';

	protected $_domain = 'efront-sp';
	protected $_sso_settings = array();

	public function __construct() {
		require_once(G_ROOTPATH.'libraries/external/simplesamlphp/lib/_autoload.php');

		$this->_sso_settings = self::getSamlAttributes();
		if (!$this->_sso_settings['saml_enabled']) {
			throw new \Exception("Saml is not enabled");
		}
		$session = \SimpleSAML_Session::getInstance();
		$this->_sso_settings['domain'] = $this->_domain;
		$session->setData("Array", "sso", $this->_sso_settings);
	}

	public function authenticate() {
		try{
			$as = new \SimpleSAML_Auth_Simple($this->_domain);
			$globalConfig = \SimpleSAML_Configuration::getInstance();
			//$globalConfig::setConfigDir(G_CONFIGDIR.'saml/');
			$as->requireAuth();

			if($as->isAuthenticated()){
					
				$attributes = $as->getAttributes();

				if (!array_key_exists($this->_sso_settings['saml_email'], $attributes)){
// 					TemplateController::setMessage(("A valid email is needed for account related communication").". ".("Check that the %s attribute (%s) defined in your configuration is correct",("Email"),$this->_sso_settings['saml_email']), 'error');
					$this->ssoLogout();
				}elseif(!array_key_exists($this->_sso_settings['saml_first_name'], $attributes)){
// 					TemplateController::setMessage(("'%s' is required",("First name")).". ".("Check that the %s attribute (%s) defined in your configuration is correct",("First name"),$this->_sso_settings['saml_first_name']), 'error');
					$this->ssoLogout();
				}elseif(!array_key_exists($this->_sso_settings['saml_last_name'], $attributes)){
// 					TemplateController::setMessage(("'%s' is required",("Last name")).". ".("Check that the %s attribute (%s) defined in your configuration is correct",("Last name"),$this->_sso_settings['saml_last_name']), 'error');
					$this->ssoLogout();
				}
				else{

					if (trim($attributes[$this->_sso_settings['saml_email']][0]) == ''){
						$attributes[$this->_sso_settings['saml_email']][0] = " ";
// 						TemplateController::setMessage(("A valid email is needed for account related communication"), 'error');
					}

					if(trim($attributes[$this->_sso_settings['saml_first_name']][0]) == '' && trim($attributes[$this->_sso_settings['saml_last_name']][0]) == ''){
						$attributes[$this->_sso_settings['saml_first_name']][0] = ' ';
						$attributes[$this->_sso_settings['saml_last_name']][0] = ' ';
					}
					else{
						if(trim($attributes[$this->_sso_settings['saml_first_name']][0]) == ''){
							$attributes[$this->_sso_settings['saml_first_name']][0] = $attributes[$this->_sso_settings['saml_last_name']][0];
						}

						if(trim($attributes[$this->_sso_settings['saml_last_name']][0]) == ''){
							$attributes[$this->_sso_settings['saml_last_name']][0] = $attributes[$this->_sso_settings['saml_first_name']][0];
						}
					}
						
					$this->_login($attributes);
					//pr($attributes);exit;
					//echo "redirect now";exit;
					//\SimpleSAML_Utilities::postRedirect("https://index.php", $attributes);
				}
			}
		} catch (\SimpleSAML_Error_Error $e) {
			$this->_samlErrorHandler($e);
		} catch (\Exception $e) {
			handleNormalFlowExceptions($e);
		}

		return $this;
	}

	/**
	 * Process the variables sent by the Idp and perform the login with SAML
	 * @param $sso array the value defined in domain's Configuration table
	 * @param $values array sent by IdP
	 */
	protected function _login($attributes){
		if(!empty($attributes[$this->_sso_settings['saml_targeted_id']])){	// user comes authenticated in index page
				
			$login = $attributes[$this->_sso_settings['saml_targeted_id']][0];
			try {
				$user = EfrontUserFactory::factory($login);
			} catch (\Exception $e) {
				$login = null;
			}
			if (is_null($login)){	// User doesn't exist. Create user
				if (0&&reachedPlanLimit()){	//@todo
// 					TemplateController::setMessage(("You have reached the maximum active users allowed by the selected plan."), 'warning');
				}
				else{
					$fields = array(
							'login' => $attributes[$this->_sso_settings['saml_targeted_id']][0],
							'password' => sha1($attributes[$this->_sso_settings['saml_targeted_id']][0]),
							'name' => $attributes[$this->_sso_settings['saml_first_name']][0],
							'surname' => $attributes[$this->_sso_settings['saml_last_name']][0],
							'active' => 1,
							'email' => $attributes[$this->_sso_settings['saml_email']][0]);

					$user = EfrontUser::createUser($fields);
					$user->login($user->user['password'], true);
					eF_redirect($user->user['user_type'].'.php');
				}
			}
			else{	// User exists
				$fields = array(
						'name' => $attributes[$this->_sso_settings['saml_first_name']][0],
						'surname' => $attributes[$this->_sso_settings['saml_last_name']][0],
						'email' => $attributes[$this->_sso_settings['saml_email']][0],
				);
				$user = EfrontUserFactory::factory($login);

				//$user->setFields($fields)->save();	//update whatever changed

				$user->login($user->user['password'],true);
				eF_redirect($user->user['user_type'].'.php');
			}
		}
		/*
		 else{//User is not authenticates, set SAML session to be ready for authentication
			
		$session = \SimpleSAML_Session::getInstance();
		$sso['domain']=$this->_domain;
		$session->setData("Array", "sso", $this->_sso_settings);
		}
		*/
	}

	/**
	 *  SSO logout and destruction of the SAML session
	 */
	public function ssoLogout(){
			
		if ($this->_sso_settings['saml_integration_type'] == self::SSO_TYPE_SAML && trim($this->_sso_settings['saml_sign_out']) == ''){
			$session = \SimpleSAML_Session::getInstance();
			$session->doLogout($this->_domain);
		} elseif ($this->_sso_settings['saml_integration_type'] == self::SSO_TYPE_SAML || $this->_sso_settings['saml_integration_type'] == self::SSO_TYPE_LDAP) {
			$as = new \SimpleSAML_Auth_Simple($this->_domain);
			$as->logout('index.php');
		}
		return $this;
	}

	/**
	 * Error handler for SAML-related errors
	 * @param string $errorcode
	 * @param string $errormsg
	 * @throws Exception
	 */
	protected function _samlErrorHandler(\SimpleSAML_Error_Error $e){
		switch ($e->getCode){
			case 'WRONGUSERPASS':
				throw new \Exception(("Your username or password is incorrect. Please try again, making sure that CAPS LOCK key is off"));
				break;
			case 'CREATEREQUEST':
				throw new \Exception(("An error occurred when trying to create the authentication request"));
				break;
			case 'LOGOUTREQUEST':
				throw new \Exception(("An error occurred when trying to process the Logout Request"));
				break;
			case 'METADATA':
				throw new \Exception(("There is some misconfiguration of your SSO metadata"));
				break;
			case 'PROCESSASSERTION':
				throw new \Exception(("We did not accept the response sent from the Identity Provider"));
				break;
			case 'LOGOUTINFOLOST':
				throw new \Exception(("Logout information lost"));
				break;
			case 'RESPONSESTATUSNOSUCCESS':
				throw new \Exception(("The Identity Provider responded with an error"));
				break;
			case 'NOTVALIDCERT':
				throw new \Exception(("You did not present a valid certificate"));
				break;
			case 'NOCERT':
				throw new \Exception(("Authentication failed: your browser did not send any certificate"));
				break;
			case 'UNKNOWNCERT':
				throw new \Exception(("Authentication failed: the certificate your browser sent is unknown"));
				break;
			case 'USERABORTED':
				throw new \Exception(("The authentication was aborted by the user"));
				break;
			case 'NOSTATE':
				throw new \Exception(("State information lost, and no way to restart the request"));
				break;
			default:
				throw new \Exception($e->getMessage());
				break;
		}
	}
	
	public static function getSamlAttributes() {
		$values = EfrontConfiguration::getValues();		
		return array(
				'saml_enabled' =>  $values['saml_enabled'],
				'saml_integration_type' => $values['saml_integration_type'],
				'saml_provider' => $values['saml_provider'],
				'saml_fingerprint' => $values['saml_fingerprint'],
				'saml_sign_in' => $values['saml_sign_in'],
				'saml_sign_out' => $values['saml_sign_out'],
				'saml_targeted_id' => $values['saml_targeted_id'],
				'saml_first_name' => $values['saml_first_name'],
				'saml_last_name' => $values['saml_last_name'],
				'saml_email' => $values['saml_email'],
				'saml_bool_redirect_validate' => $values['saml_bool_redirect_validate'] ? true : false,
				'saml_bool_redirect_sign' => $values['saml_bool_redirect_sign'] ? true : false,
		);
	}
	
}