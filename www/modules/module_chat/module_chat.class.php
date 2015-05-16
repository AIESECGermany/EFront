<?php

//include_once ("../PEAR/Spreadsheet/Excel/Writer.php");

include("../../../libraries/configuration.php");

session_start();
global $dbh;
$dbh = mysql_connect(G_DBHOST,G_DBUSER,G_DBPASSWD) or die('Could not connect to mysql server.' );
mysql_selectdb(G_DBNAME,$dbh);

class module_chat extends eFrontModule{


	public function getName() {
		return _CHAT_CHAT;
	}

	public function getPermittedRoles() {
	 	return array("administrator", "professor", "student");
	}

    public function getModuleJS() {
		if (strpos(decryptUrl($_SERVER['REQUEST_URI']), $this -> moduleBaseUrl) !== false) {
			return $this->moduleBaseDir."js/admin.js";
		}
    }


	public function onInstall(){
		eF_executeNew("DROP TABLE IF EXISTS module_chat");
		$res1 = eF_executeNew("CREATE TABLE module_chat (
							id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
							from_user VARCHAR(255) NOT NULL DEFAULT '',
							to_user VARCHAR(255) NOT NULL DEFAULT '',
							message TEXT NOT NULL,
							sent DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
							isLesson INTEGER UNSIGNED NOT NULL DEFAULT 0,
							PRIMARY KEY (id)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8"
							);

		eF_executeNew("DROP TABLE IF EXISTS module_chat_users");
		$res2 = eF_executeNew("CREATE TABLE module_chat_users (username VARCHAR(100) NOT NULL,
							timestamp_ TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
							UNIQUE (username)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8"
							);

		eF_executeNew("DROP TABLE IF EXISTS module_chat_config");
		$res3 = eF_executeNew("CREATE TABLE module_chat_config (status INT NOT NULL DEFAULT  '1',
							chatHeartbeatTime INT NOT NULL DEFAULT  '1500',
							refresh_rate INT NOT NULL DEFAULT  '60000'
							) ENGINE=InnoDB DEFAULT CHARSET=utf8"
							);
		$res4 = eF_executeNew("INSERT INTO  module_chat_config
							(status, chatHeartbeatTime, refresh_rate) VALUES
							('1', '2000', '30000')"
							);


		return ($res1 && $res2 && $res3 && $res4);
	}

	public function onUninstall() {
            $res1 = eF_executeNew("DROP TABLE module_chat;");
			$res2 = eF_executeNew("DROP TABLE module_chat_users;");
			$res3 = eF_executeNew("DROP TABLE module_chat_config;");

			return ($res1 && $res2 && $res3);
    }

	public function getCenterLinkInfo() {
        return array('title' => _CHAT_CHAT,
                     'image' => $this -> moduleBaseDir.'img/chat.png',
                     'link' => $this -> moduleBaseUrl);
    }

    public function getModuleCSS() {
        return $this->moduleBaseDir."css/screen.css";
    }

	private function calculateCommonality($user){
		$currentUserLessons = array();
		$commonality = array();
		$common_lessons = array();
		$all_users = array();
		$users_lessons ;

		$result = eF_executeNew ("SELECT lessons_ID FROM users_to_lessons where archive=0 and users_LOGIN='$user'");

		foreach ($result as $value) {
			$currentUserLessons[] = ($value["lessons_ID"]);
		}

		$result = eF_executeNew ("SELECT login FROM users");
		$result2 = eF_executeNew ("SELECT users_LOGIN, lessons_ID FROM users_to_lessons WHERE archive=0");
		foreach ($result2 as $value2){
			$users_lessons_all[$value2['users_LOGIN']][] = $value2["lessons_ID"];
		}

		foreach ($result as $value) {
			if ($value["login"] != $user){
				$all_users[] = ($value["login"]);

				$rate = 0;

				//$result2 = eF_executeNew ("SELECT lessons_ID FROM users_to_lessons WHERE archive=0 and users_LOGIN='".$value['login']."'");

				//foreach ($result2 as $value2){
					//$users_lessons[] = $value2["lessons_ID"];
				$users_lessons = $users_lessons_all[$value["login"]];
				//}

				$common_lessons[$value["login"]] = array_intersect($users_lessons, $currentUserLessons);
				$rate =  sizeof($common_lessons[$value["login"]]);
				$commonality[$value["login"]] = $rate;

				unset($users_lessons); // unset array for the next user
			}
		}

		$_SESSION['commonality'] = $commonality;

	}

	public function isPopup() {
		if (isset($_GET['popup'])){
			if ($_GET['popup']==1){
				return true;
			}
		}
		return false;
	}


	public function addScripts() {
		return array("scriptaculous/effects", "scriptaculous/controls");
	}


	public function onPageFinishLoadingSmartyTpl() {

		if (!isset($_SESSION['lesson_rooms'])) {
			$_SESSION['lesson_rooms'] = array();
		}
		$smarty = $this -> getSmartyVar();

		$mainScripts = array_merge(array('../modules/module_chat/js/chat'),getMainScripts());
		$smarty -> assign("T_HEADER_MAIN_SCRIPTS", implode(",", $mainScripts));

		if ($this->isPopup()){
			$smarty -> assign("T_CHAT_MODULE_STATUS", "OFF");
		} else {
			$smarty -> assign("T_CHAT_MODULE_STATUS", "ON");
		}

		if (!$_SESSION['chatter']){
			$currentUser = $this -> getCurrentUser();
			$_SESSION['chatter'] = $currentUser -> login;
			$_SESSION['utype'] = $currentUser -> getType();
			$this -> calculateCommonality($currentUser -> login);
			eF_executeNew("INSERT IGNORE INTO module_chat_users (username ,timestamp_) VALUES ('".$_SESSION['chatter']."', CURRENT_TIMESTAMP);");
		}else{
			$currentUser = $this -> getCurrentUser();
			if ($_SESSION['chatter'] != $currentUser -> login){
				$_SESSION['chatter'] = $currentUser -> login;
				$_SESSION['utype'] = $currentUser -> getType();
				$this -> calculateCommonality($currentUser -> login);
				eF_executeNew("INSERT IGNORE INTO module_chat_users (username ,timestamp_) VALUES ('".$_SESSION['chatter']."', CURRENT_TIMESTAMP);");
			}
		}

        $smarty -> assign("T_CHAT_MODULE_BASEURL", $this -> moduleBaseUrl);
        $smarty -> assign("T_CHAT_MODULE_BASELINK", $this -> moduleBaseLink);
		$smarty -> assign("T_CHAT_MODULE_BASEDIR", $this -> moduleBaseDir);

		$onlineUsers = EfrontUser :: getUsersOnline();


		$smarty -> assign("T_CHAT_MODULE_ONLINEUSERS", $onlineUsers);

		return $this -> moduleBaseDir . "module_chat.tpl";
	}

	public function getModule(){
		return true;
	}	
	
	
	public function getSmartyTpl() {
		$smarty = $this -> getSmartyVar();

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Module rates
		$heartbeatForm = new HTML_QuickForm('module_chat_heartbeat_form', "post", $this->moduleBaseUrl, "", null, true);
		$heartbeatForm->addElement('text', 'engine_rate', _CHAT_ENGINE_RATE, 'class="inputText" style="width:100px;"');
		$heartbeatForm->addRule('engine_rate', _THEFIELD . ' '. _CHAT_ENGINE_RATE . ' ' . _ISMANDATORY, 'required', null, 'client');
		$heartbeatForm->addRule('engine_rate', _THEFIELD . ' '. _CHAT_ENGINE_RATE . ' ' . _CHAT_MUST_BE_NUMERIC_VALUE, 'numeric', null, 'client');
		$heartbeatForm->addRule('engine_rate', _THEFIELD . ' '. _CHAT_ENGINE_RATE . ' ' . _CHAT_MUST_BE_GREATER_THAN_ONE, 'callback', create_function('$a', '$res = ($a >= 1) ? true : false; return $res;'));
		$heartbeatForm->addElement('text', 'refresh_rate', _CHAT_USERLIST_REFRESH_RATE, 'class="inputText" style="width:100px;"');
		$heartbeatForm->addRule('refresh_rate', _THEFIELD . ' ' . _CHAT_USERLIST_REFRESH_RATE . ' ' . _ISMANDATORY, 'required', null, 'client');
		$heartbeatForm->addRule('refresh_rate', _THEFIELD . ' ' . _CHAT_USERLIST_REFRESH_RATE . ' ' . _CHAT_MUST_BE_NUMERIC_VALUE, 'numeric', null, 'client');
		$heartbeatForm->addRule('refresh_rate', _THEFIELD . ' '. _CHAT_ENGINE_RATE . ' ' . _CHAT_MUST_BE_GREATER_THAN_ONE, 'callback', create_function('$a', '$res = ($a >= 1) ? true : false; return $res;'));
		$heartbeatForm->addElement('submit', 'submit', _SUBMIT, 'class="flatButton"');

		$heartbeatForm -> setDefaults(array('engine_rate' => $this->getChatHeartbeat()/1000, 'refresh_rate' => $this->getRefreshRate()/1000));
		
		$renderer = prepareFormRenderer($heartbeatForm);
		$smarty->assign("T_CHAT_CHATHEARTBEAT_FORM", $renderer -> toArray());

		if ($heartbeatForm -> isSubmitted() && $heartbeatForm -> validate()) {
			$values = $heartbeatForm -> exportValues();
			$this -> setChatHeartbeat($values['engine_rate']*1000);
			$this -> setRefreshRate($values['refresh_rate']*1000);
			eF_redirect($this -> moduleBaseUrl . '&message_type=success&message=' . urlencode(_CHAT_VALUES_UPDATED_SUCCESSFULLY));
		}

			
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Create Log file	

		$logForm = new HTML_QuickForm("module_chat_createlog_form", "post", $this->moduleBaseUrl."&createLog=1", "", null, true);
		$logForm->addElement('hidden', 'hidden_lesson_id', '', 'id = "hidden_lesson_id"');
		$logForm->addElement('text', 'lesson_title', _CHAT_LESSON_TITLE, 'maxlength="100" size="100" class="autoCompleteTextBox" id="autocomplete"');
		$logForm->addRule('lesson_title', _THEFIELD.' '._CHAT_LESSON_TITLE. ' ' ._ISMANDATORY, 'required', null, 'client');
		$logForm->addElement('date', 'from',  _CHAT_FROM_DATE, array('format' => 'dMY', 'minYear' => 2010, 'maxYear' => date('Y')));
		$logForm->addElement('date', 'until', _CHAT_UNTIL_DATE, array('format' => 'dMY', 'minYear' => 2010, 'maxYear' => date('Y')));
		$logForm->addElement('submit', 'submit', _SUBMIT, 'class="flatButton"');
		
		$week_ago = $this->subtractDaysFromToday(7);
		$logForm->setDefaults(array('until' => array('d' => date('d'), 'M' => date('m'), 'Y' => date('Y')), 'from' => $week_ago));			
		
		$renderer = prepareFormRenderer($logForm);
		$smarty->assign("T_CHAT_CREATE_LOG_FORM", $renderer -> toArray());
		
		if (isset($_GET['createLog'])){
			$log = $this->createLessonHistory(
					$_POST['hidden_lesson_id'],
					$_POST['from']['Y'].'-'.$_POST['from']['M'].'-'.$_POST['from']['d'].' '."00:00:00" ,
					$_POST['until']['Y'].'-'.$_POST['until']['M'].'-'.$_POST['until']['d'].' '."23:59:59");
		
			$smarty->assign('T_LOG', $log);
			$smarty->assign('T_CHAT_LESSON_TITLE', $l2);
		}
		if($_POST['exportAdminChat']) {
			$this -> adminLogsExportToExcel($_POST['logTitle'], $_POST['data']);
			exit;
		}
		
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Clear chat logs		
		return $this -> moduleBaseDir . "control_panel.tpl";
	}



	private function adminLogsExportToExcel($title, $log) {
		require_once ('lib/PHPExcel_1.7.9_doc/Classes/PHPExcel.php');

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("eFront"); // TODO efront version nun
		$objPHPExcel->getProperties()->setLastModifiedBy("eFront"); // TODO efront version nun
		$objPHPExcel->getActiveSheet()->setTitle(_CHAT_LOG."-".$title);
		$objPHPExcel->getProperties()->setSubject(_CHAT_LOG."-".$title);		
		
		$objPHPExcel->setActiveSheetIndex(0);
		
		array_pop($log);
		
		$row = 2; $previousLoginNameLength = 1;$previousMsgLength = 1;$previousDateLength = 1;
		foreach($log as $line) {
			// first row is headers
			if($row == 2) {
				$objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $line[0]);
				$objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $line[1]);
				$objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $line[2]);
				$objPHPExcel->getActiveSheet()->getStyle("B".$row.":D".$row)->applyFromArray(array('font' => array('bold' => true), 'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN))));
			} else {
		
				if( (strlen($line[0])+ 5) > $previousLoginNameLength) {
					$objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $line[0])->getColumnDimension('B')->setWidth(strlen($line[0])+5);
					$previousLoginNameLength = strlen($line[0]) + 5;
				} else {
					$objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $line[0])->getColumnDimension('B')->setWidth($previousLoginNameLength);
				}
				
				if( (strlen($line[1])+ 5) > $previousMsgLength) {
					$objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $line[1])->getColumnDimension('C')->setWidth(strlen($line[1])+5);
					$previousMsgLength = strlen($line[1]) + 5;
				} else {
					$objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $line[1])->getColumnDimension('C')->setWidth($previousMsgLength);
				}
				
				if( (strlen($line[1])+ 5) > $previousDateLength) {
					$objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $line[2])->getColumnDimension('D')->setWidth(strlen($line[2])+5);
					$previousDateLength = strlen($line[2]) + 5;
				} else {
					$objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $line[2])->getColumnDimension('D')->setWidth($previousDateLength);
				}
		
			}
			$row++;
		}
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save(G_UPLOADPATH.$_SESSION['s_login']."/"._CHAT_LOG."-".$title.".xls");
		echo G_UPLOADPATH.$_SESSION['s_login']."/"._CHAT_LOG."-".$title.".xls";		
	}
	


	public function getNavigationLinks() {
		$currentUser = $this -> getCurrentUser();
        return array (array ('title' => _HOME, 'link'  => $currentUser -> getType() . ".php?ctg=control_panel"), array ('title' => "Chat Module", 'link'  => $this -> moduleBaseUrl));
	}


	private function getChatHeartbeat(){
		$rate = eF_getTableData("module_chat_config", "chatHeartbeatTime", "1");
		foreach( $rate as $r ){
			return $r['chatHeartbeatTime'];
		}

	}

	private function getRefreshRate(){
		$rate = eF_getTableData("module_chat_config", "refresh_rate", "1");
		foreach( $rate as $r ){
			return $r['refresh_rate'];
		}
	}


	private function setChatheartBeat($rate){
		ef_updateTableData('module_chat_config', array('chatHeartbeatTime' => $rate));
	}

	private function setRefreshRate($rate){
		ef_updateTableData('module_chat_config', array('refresh_rate' => $rate));
	}

	private function createLessonHistory($lesson, $from, $until){
		$i = 1;
		$results = eF_getTableData('module_chat', '*', "(module_chat.to_user = '".$lesson."' AND module_chat.sent >='".$from."' AND module_chat.sent <= '".$until."')");
		$log  = "<span style='float:right'>";
		$log .= "<a onclick='javascript:exportChatAdmin();' href='javascript:void(0)'>";
		$log .= "<img src = 'images/file_types/xls.png' title = '"._XLSFORMAT."' alt = '"._XLSFORMAT."' />";
		$log .= "</a>";
		$log .= "</span>";
		$log .= "<br>";
		$log .= "<table id='admin_chat_logs' class='sortedTable' width='100%'>";
		$log .= "<tr>";
		$log .= "<td class = 'topTitle'>"._FROM."</td>";
		$log .= "<td class = 'topTitle alignCenter'>"._MESSAGE."</td>";
		$log .= "<td class = 'topTitle' colspan='2'>"._DATE."/"._TIME."</td>";
		$log .= "</tr>";
		$rowColorClass = array("oddRowColor", "evenRowColor");
		foreach($results as $chat) {
			$log .= "<tr class='".$rowColorClass[$i%2]."'>";
			$log .= "<td class='sender'>".$chat["from_user"].":</td>";
			$log .= "<td class='alignCenter chatmsg'>".$chat["message"]."</td>";
			$log .= "<td class='alignLeft date'>".$chat["sent"]."</td>";
			$log .= "</tr>";
			$i++;
		}
		$log.= "</table>";	
		return $log;
	}

	private function subtractDaysFromToday($number_of_days) {
    	$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

    	$subtract = $today - (86400 * $number_of_days);

    	//choice a date format here
    	return date("d-M-Y", $subtract);
	}

    public function getModuleIcon() {
        return $this -> moduleBaseLink.'img/chat.png';
    }    
}
?>
