<?php
class EfrontConnect
{
	protected static $_instance = null;
	
	public static $config = null;

	public static $db_host = null;
	public static $db_user = null;
	public static $db_name = null;
	public static $db_passwd = null;
	

	public function __construct() {
// 		$this->getConfig();

		$path = dirname(dirname(dirname(__DIR__))).'/';
		$file_contents = file_get_contents($path."configuration.php");                            //Load sample configuration file

		preg_match("/define\(['\"]G_DBHOST['\"], ['\"](.*)['\"]\);/", $file_contents, $matches);
		$host = $matches[1];
		preg_match("/define\(['\"]G_DBUSER['\"], ['\"](.*)['\"]\);/", $file_contents, $matches);
		$user = $matches[1];
		preg_match("/define\(['\"]G_DBPASSWD['\"], ['\"](.*)['\"]\);/", $file_contents, $matches);
		$passwd = $matches[1];
		preg_match("/define\(['\"]G_DBNAME['\"], ['\"](.*)['\"]\);/", $file_contents, $matches);
		$name = $matches[1];

		self::$config['db_host'] =  $host;
		self::$config['db_name'] =  $name;
		self::$config['db_user'] =  $user;
		self::$config['db_passwd'] =  $passwd;
		
		$this->_getConfig();
	}

	public static function getInstance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	protected function _getConfig() {
		$path = dirname(dirname(dirname(__DIR__))).'/';
		
		require_once($path.'adodb/adodb.inc.php');
		require_once($path.'adodb/adodb-exceptions.inc.php');
		$ADODB_CACHE_DIR = $path."adodb/cache";
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		
// 		if (function_exists('mysqli_connect')) {
// 			$db = ADONewConnection('mysqli');
// 		} else {
			$db = ADONewConnection('mysql');
// 		}
		$db->Connect(self::$config['db_host'], self::$config['db_user'], self::$config['db_passwd'], self::$config['db_name']);
		$result = $db->Execute("select login,name,surname,email,password from users where user_type='administrator' and active=1 order by login limit 1");
		$admin = $result->GetAll();
		
		//$admin = EfrontSystem::getAdministrator();
		self::$config['admin_email'] = $admin[0]['email'];
		self::$config['admin_name'] = $admin[0]['name'].' '.$admin[0]['surname'];
		self::$config['admin_hash'] = $admin[0]['password'];
		self::$config['salt'] = 'cDWQR#$Rcxsc';

		$result = $db->Execute('SELECT * FROM configuration WHERE name like "saml_%"');
		foreach($result->GetAll() as $key => $value) {
			if (strpos($value['name'], 'saml_bool_') !== false) {	//because we need strict type checking and configuration values come as strings
				$value['value'] ? self::$config[$value['name']] = true : self::$config[$value['name']] = false;
			} else if (strpos($value['name'], 'saml_') !== false) {
				self::$config[$value['name']] = $value['value'];
			}
		}
// 		var_dump(self::$config);exit;
/*
		if (is_null(self::$config)) {
			$store = SimpleSAML_Store::getInstance();
			$query = 'SELECT * FROM configuration WHERE name like "saml_%"';
			$query = $store->pdo->prepare($query);
			$query->execute();
			foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $value) {
				$sys_config[$value['name']] = $value['value'];
			}
			self::$config = $sys_config;
		}
		return self::$config;
*/
		
		return self::$config;
	}
}