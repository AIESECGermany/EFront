<?php
session_cache_limiter('none');          //Initialize session
session_start();

$path = "../../../libraries/";                //Define default path

/** The configuration file.*/
require_once $path."configuration.php";

//Set headers in order to eliminate browser cache (especially IE's)
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past



if (!isset($_SESSION['chatter'])){
	exit(1);
}

if (!isset($_SESSION['chatboxesnum']))
	$_SESSION['chatboxesnum'] = 0;

if (!isset($_SESSION['chatHistory'])) {
	$_SESSION['chatHistory'] = array();
}

if (!isset($_SESSION['openChatBoxes'])) {
	$_SESSION['openChatBoxes'] = array();
}

global $dbh;
$dbh = mysql_connect(G_DBHOST,G_DBUSER,G_DBPASSWD) or die('Could not connect to mysql server.' );
mysql_selectdb(G_DBNAME,$dbh);

if ($_GET['action'] == "chatheartbeat") { chatHeartbeat(); }
if ($_GET['action'] == "sendchat") { sendChat(); }
if ($_GET['action'] == "closechat") { closeChat(); }
if ($_GET['action'] == "startchatsession") { startChatSession(); }
if ($_GET['action'] == "logoutfromchat") { logoutFromChat(); }
if ($_GET['action'] == "logintochat") { loginToChat(); }
if ($_GET['action'] == "getchatheartbeat") { getChatHeartbeat(); }
if ($_GET['action'] == "getrefreshrate") { getRefresh_rate(); }
if ($_GET['action'] == "getchathistory") { getChatHistory(); }

function getChatHeartbeat(){
	$rate = eF_getTableData("module_chat_config", "chatHeartbeatTime", "1");
	foreach( $rate as $r ){
		echo($r['chatHeartbeatTime']);
	}
}

function getRefresh_rate(){
	$rate = eF_getTableData("module_chat_config", "refresh_rate", "1");
	foreach( $rate as $r ){
		echo $r['refresh_rate'];
	}
}


function logoutFromChat(){
		eF_executeNew("DELETE FROM module_chat_users WHERE username='".$_SESSION['chatter']."'");
}

function loginToChat(){
		eF_executeNew("INSERT IGNORE INTO module_chat_users (username ,timestamp_) VALUES ('".$_SESSION['chatter']."', CURRENT_TIMESTAMP);");
}

function chatHeartbeat() {
	if (!$_SESSION['last_msg']) {
		//$my_t=getdate();
		//$_SESSION['last_msg'] = $my_t[year].'-'.$my_t[mon].'-'.$my_t[mday].' '.$my_t[hours].':'.$my_t[minutes].':'.$my_t[seconds];
		$_SESSION['last_msg'] = date("Y-m-d H:i:s", time()-date("Z"));  //fix for timezone differences
	}

	if (!$_SESSION['last_lesson_msg']) {
		//$my_t=getdate();
		//$_SESSION['last_lesson_msg'] = $my_t[year].'-'.$my_t[mon].'-'.$my_t[mday].' '.$my_t[hours].':'.$my_t[minutes].':'.$my_t[seconds];
		$_SESSION['last_lesson_msg'] = date("Y-m-d H:i:s", time()-date("Z"));  //fix for timezone differences
	}

	$lesson_rooms = join("','",$_SESSION['lesson_rooms']);

	if (!$_SESSION['s_lessons_ID']) {
		$results = eF_getTableData('module_chat', '*', "(module_chat.to_user = '".$_SESSION['chatter']."' AND sent>'".$_SESSION['last_msg']."')", 'id ASC');	
	} else {
		$results = eF_getTableData('module_chat', '*', "(module_chat.to_user = '".mysql_real_escape_string($_SESSION['chatter'])."' AND sent>'".$_SESSION['last_msg']."') OR (module_chat.to_user IN ('$lesson_rooms') AND module_chat.from_user != '".$_SESSION['chatter']."' AND sent>'".$_SESSION['last_lesson_msg']."')", 'id ASC');
	}

	$items = '';
	$chatBoxes = array();
	foreach ($results as $chat) {
		if (in_array($chat['to_user'],$_SESSION['lesson_rooms'])) {
			$title = $chat['to_user'];
			$chatboxname = $_SESSION["room_".$title];
		} else {
			$title = $chat['from_user'];
			$chatboxname = $title;
		}
		
		$_SESSION['last_msg'] = $chat['sent'];
		$_SESSION['last_lesson_msg'] = $chat['sent'];
		if (!isset($_SESSION['openChatBoxes'][$title]) && isset($_SESSION['chatHistory'][$title])) {
			$items = $_SESSION['chatHistory'][$title];
		}
		
		$items .= <<<EOD
					   {
			"s": "0",
			"t": "{$title}",
			"f": "{$chat['from_user']}",
			"m": "{$chat['message']}",
			"n": "{$chatboxname}"
	   },
EOD;
		
		if (!isset($_SESSION['chatHistory'][$title])) {
			$_SESSION['chatHistory'][$title] = '';
		}
		
		$_SESSION['chatHistory'][$title] .= <<<EOD
						   {
			"s": "0",
			"t": "{$title}",
			"f": "{$chat['from_user']}",
			"m": "{$chat['message']}",
			"n": "{$chatboxname}"
	   },
EOD;
		//}
		
		//unset($_SESSION['tsChatBoxes'][$chat['from_user']]);
		if (!isset( $_SESSION['openChatBoxes'][$title] )){
			$_SESSION['openChatBoxes'][$title] = $_SESSION['chatboxesnum'];
			$_SESSION['chatboxesnum'] = $_SESSION['chatboxesnum'] + 10;
		}		
	}

	if ($items != '') {
		$items = substr($items, 0, -1);
	}
header('Content-type: application/json');
?>
{
		"items": [
			<?php echo $items;?>
        ]
}

<?php
			exit(0);
}

function getChatHistory() {

	if (eF_checkParameter($_POST['chat_with'], 'login')) {
	
		if($_POST['type'] == 'user') {
			$results = eF_getTableData("module_chat", "*", "from_user='".$_SESSION['chatter']."' AND to_user='".$_POST['chat_with']."' OR from_user='".$_POST['chat_with']."' AND to_user='".$_SESSION['chatter']."'", "id ASC");
		}
	
		if($_POST['type'] == 'lesson') {
			$results = eF_getTableData('module_chat', "*", "to_user='".$_POST['chat_with']."'", "id ASC");
		}
		$items = array();
		foreach($results as $chat) {
			$items[] = array(
				"s" => "0",
				"t" => "",
				"f" => $chat['from_user'],
				"m" => $chat['message'],
				"n" => ""
			);
		}
	}

	echo json_encode($items);

}

function chatBoxSession($chatbox) {
	$items = '';
	if (isset($_SESSION['chatHistory'][$chatbox])) {
		$items = $_SESSION['chatHistory'][$chatbox];
	}

	return $items;
}

function startChatSession() {
	$items = '';
	asort($_SESSION['openChatBoxes']);
	if (!empty($_SESSION['openChatBoxes'])) {
		foreach ($_SESSION['openChatBoxes'] as $chatbox => $void) {
			$items .= chatBoxSession($chatbox);
		}
	}

	//asort($_SESSION['openChatBoxes']);
	if ($items != '') {
		$items = substr($items, 0, -1);
	}

header('Content-type: application/json');
?>
{
		"username": "<?php echo $_SESSION['chatter'];?>",
		"items": [
			<?php echo $items;?>
        ]
}

<?php


	exit(0);
}

function sendChat() {

	$from = $_SESSION['chatter'];
	$to = $_POST['to'];
	$message = $_POST['message'];
	$chatboxname = $_POST['chatboxname'];

	if ( !isset($_SESSION['openChatBoxes'][$_POST['to']])){
		$_SESSION['openChatBoxes'][$_POST['to']] = $_SESSION['chatboxesnum'];
		$_SESSION['chatboxesnum'] = $_SESSION['chatboxesnum'] + 10;
	}

	$messagesan = sanitize($message);

	if (!isset($_SESSION['chatHistory'][$_POST['to']])) {
		$_SESSION['chatHistory'][$_POST['to']] = '';
	}

	$_SESSION['chatHistory'][$_POST['to']] .= <<<EOD
					   {
			"s": "1",
			"t": "{$to}",
			"f": "{$to}",
			"m": "{$messagesan}",
			"n": "{$chatboxname}"
	   },
EOD;


	//unset($_SESSION['tsChatBoxes'][$_POST['to']]);

	if ($to != $_SESSION['lessonid']){
		ef_insertTableData('module_chat', array(
			'from_user' => $from,
			'to_user' => $to,
			'message' => $message,
			'sent' => date("Y-m-d H:i:s", time()-date("Z")),
			'isLesson' => 0
			
		));
		//$sql = "insert into module_chat (module_chat.from_user,module_chat.to_user,message,sent,module_chat.isLesson) values ('".mysql_real_escape_string($from)."', '".mysql_real_escape_string($to)."','".mysql_real_escape_string($message)."','".date("Y-m-d H:i:s", time()-date("Z"))."', '0')";
	}
	else{
		ef_insertTableData('module_chat', array(
			'from_user' => $from,
			'to_user' => $to,
			'message' => $message,
			'sent' => date("Y-m-d H:i:s", time()-date("Z")),
			'isLesson' => 1
	
		));
		//$sql = "insert into module_chat (module_chat.from_user,module_chat.to_user,message,sent,module_chat.isLesson) values ('".mysql_real_escape_string($from)."', '".mysql_real_escape_string($to)."','".mysql_real_escape_string($message)."','".date("Y-m-d H:i:s", time()-date("Z"))."', '1')";
	}
	//$query = mysql_query($sql);
	echo $_SESSION['chatboxesnum'];
	exit(0);
}

function closeChat() {

	unset($_SESSION['openChatBoxes'][$_POST['chatbox']]);
	if (str_replace(' ','_',$_POST['chatbox']) != $_SESSION["lessonid"] && in_array(str_replace(' ','_',$_POST['chatbox']),$_SESSION['lesson_rooms']))
		$_SESSION['lesson_rooms'] = remove_item_by_value($_SESSION['lesson_rooms'], str_replace(' ','_',$_POST['chatbox']));


	echo $_POST['chatbox'];
	exit(0);
}


function remove_item_by_value($array, $val = '') {
	if (empty($array) || !is_array($array)) return false;
	if (!in_array($val, $array)) return $array;

	foreach($array as $key => $value) {
		if ($value == $val) unset($array[$key]);
	}

	return array_values($array);
}


function sanitize($text) {
	$text = htmlspecialchars($text, ENT_QUOTES);
	$text = str_replace("\n\r","\n",$text);
	$text = str_replace("\r\n","\n",$text);
	$text = str_replace("\n","<br>",$text);
	return $text;
}
?>
