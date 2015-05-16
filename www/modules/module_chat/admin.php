<?php

include("../../../libraries/configuration.php");

session_start();
global $dbh;
$dbh = mysql_connect(G_DBHOST,G_DBUSER,G_DBPASSWD) or die('Could not connect to mysql server.' );
mysql_selectdb(G_DBNAME,$dbh);

if ($_GET['force'] == "clearU2ULogs") { clearU2ULogs(); }
if ($_GET['force'] == "clearAllLogs") { clearAllLogs(); }
if ($_GET['force'] == "getLessonFromId") { getLessonFromId(); }


/////////////////////////////////////////////////////////////////////////////
function clearU2ULogs(){
	//$sql = "DELETE FROM module_chat WHERE isLesson='0'";
	eF_deleteTableData("module_chat", "isLesson='0'");
	//echo _CHAT_CHAT_HISTORY_SUCCESSFULY_DELETED;
	echo 'Chat history successfuly deleted';
}
///////////////////////////////////////////////////////////////////////////////
function clearAllLogs(){
	//$sql = "DELETE FROM module_chat WHERE 1";
	eF_deleteTableData("module_chat", "1");
	//echo _CHAT_CHAT_HISTORY_SUCCESSFULY_DELETED;
	echo 'Chat history successfuly deleted';
}
//////////////////////////////////////////////////////////////////////////////
function getLessonFromId(){
	if (eF_checkParameter($_GET['loglessonid'], 'id')) {
		$id = $_GET["loglessonid"];
		$result = eF_getTableData('lessons', 'id, name', "id='".$id."'");
		echo json_encode(array('name' => $result[0]['name'], 'id' => $result[0]['id']));
	}
}

///////////////////////////////////////////////////////////////////////////////

