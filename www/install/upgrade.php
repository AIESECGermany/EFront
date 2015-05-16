<?php
$failed_queries = array();

// Added this in order to avoid non-existing table erros when upgrading to different editions
if (strcmp($GLOBALS['configuration']['version_type'], G_VERSIONTYPE_CODEBASE) != 0) {
	try {	
		$GLOBALS['db'] -> Execute("set foreign_key_checks=0");
		foreach (explode(";\n", str_replace("\r\n", "\n", file_get_contents(G_VERSIONTYPE.'_nodrops.sql'))) as $command) {
			if (trim($command)) {		
				$GLOBALS['db'] -> execute(trim($command));
			}
		}
		$GLOBALS['db'] -> Execute("set foreign_key_checks=1");
	} catch (Exception $e) {
		if ($e ->getCode() != 1050) {
			$failed_queries[] = $e->getMessage();
		}
	}
}


//3.6.12 queries
if (version_compare($dbVersion, '3.6.12') == -1) {
	try {
		$db -> Execute("alter table users add last_login int(10) unsigned default NULL");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try {
		$db->Execute("update users u set last_login=(select max(timestamp) from logs where users_LOGIN=u.login and action='login')");
	} catch (Exception $e) {}
	
	try {
		$db->Execute("alter table lessons add access_limit int(10) default 0");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	try {
		$db->Execute("alter table users_to_lessons add access_counter int(10) default 0");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	try {
		$db->Execute("alter table user_profile add field_order int(10) default null");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	try {
		$db->Execute("alter table completed_tests engine=innodb");
		$db->Execute("
	CREATE TABLE IF NOT EXISTS `completed_tests_blob` (
	  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	  `completed_tests_ID` mediumint(8) unsigned NOT NULL,
	  `test` longblob,
	  PRIMARY KEY (`id`),
	  KEY `ibfk_completed_tests_blob_1` (`completed_tests_ID`),
	  CONSTRAINT `ibfk_completed_tests_blob_1` FOREIGN KEY (`completed_tests_ID`) REFERENCES `completed_tests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");	
		$db->Execute("insert into completed_tests_blob (completed_tests_ID, test) select id, test from completed_tests");
		$db->Execute("alter table completed_tests drop test");
	} catch (Exception $e) {
		if ($e ->getCode() != 1054) {
			$failed_queries[] = $e->getMessage();
		}
	}
}

//3.6.13 queries
if (version_compare($dbVersion, '3.6.13') == -1) {
	try {
		$db->Execute("alter table `lessons_to_courses` ADD start_period int(10) UNSIGNED default NULL");
		$db->Execute("alter table `lessons_to_courses` ADD end_period int(10) UNSIGNED default NULL");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}

	try {
		$db -> Execute("alter table user_profile add rule varchar(255) default null");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}

	try {
		$db->Execute("alter table content add linked_to mediumint(8) unsigned default null");
		$db->Execute("alter table questions add linked_to mediumint(8) unsigned default null");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}

	try {
		$db->Execute("alter table users ADD simple_mode tinyint(1) default 0");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try {
		//change all tables' engine to innodb, except for these containing a fulltext index, which don't support innodb
		$result = $db->getCol("SELECT CONCAT('ALTER TABLE `',table_schema,'`.`',table_name,'` ENGINE=InnoDB;') FROM information_schema.tables WHERE engine='MyISAM' AND table_schema='{$db->database}' AND  table_name not in (select table_name FROM information_schema.statistics WHERE index_type='FULLTEXT' and table_schema='{$db->database}')");
		foreach ($result as $value) {
			$db->Execute($value);
		}
	} catch (Exception $e) {
		$failed_queries[] = $e->getMessage();
	}
	
	
	try {
		if (is_file('tincan_queries.txt')) {
			$GLOBALS['db'] -> Execute("set foreign_key_checks=0");
			foreach (explode(";\n", file_get_contents('tincan_queries.txt')) as $command) {
				if (trim($command)) {
					$GLOBALS['db'] -> execute(trim($command));
				}
			}
			$GLOBALS['db'] -> Execute("set foreign_key_checks=1");
		}
	} catch (Exception $e) {
		$failed_queries[] = $e->getMessage();
	}
}

if (version_compare($dbVersion, '3.6.13') == 0) {
	try {
		$db->Execute("alter table `users` change `last_login` `last_login` int(10) unsigned NOT NULL DEFAULT 0");
		//$db->Execute("alter table `users_to_lessons` add `progress` float default 0");
	} catch (Exception $e) {
		if ($e ->getCode() != 1265 && $e ->getCode() != 1138) {  //Error Code: 1265. Data truncated for column
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try {
		if (is_file('tincan_queries.txt')) {
			$GLOBALS['db'] -> Execute("set foreign_key_checks=0");
			foreach (explode(";\n", file_get_contents('tincan_queries.txt')) as $command) {
				if (trim($command)) {
					$GLOBALS['db'] -> execute(trim($command));
				}
			}
			$GLOBALS['db'] -> Execute("set foreign_key_checks=1");
		}
	} catch (Exception $e) {
		$failed_queries[] = $e->getMessage();
	}
				
}

//3.6.14 queries
if (version_compare($dbVersion, '3.6.14') == -1) {

	try{
		$db->Execute("ALTER TABLE `users_to_projects` ADD `professor_upload_filename` VARCHAR( 255) NULL DEFAULT NULL");
		$db->Execute("ALTER TABLE `users_to_projects` ADD `text_grade` VARCHAR( 100 ) NULL DEFAULT NULL");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try{
		$db->Execute("create index pm_index ON f_personal_messages (users_LOGIN)");
	} catch (Exception $e) {
		if ($e ->getCode() != 1061) {
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try{
		$db->Execute("create index `scd_users_LOGIN` ON scorm_data(users_LOGIN)");
	} catch (Exception $e) {
		if ($e ->getCode() != 1061) {
			$failed_queries[] = $e->getMessage();
		}
	}		
}		

try{
	$db->Execute("ALTER TABLE `users` ADD `email_block` tinyint(1) NOT NULL default '0'");
} catch (Exception $e) {
	if ($e ->getCode() != 1060) {
		$failed_queries[] = $e->getMessage();
	}
}

if (version_compare($dbVersion, '3.6.15') == -1) {
	try{
		$db->Execute("ALTER TABLE `events` modify `id` int(11) unsigned NOT NULL AUTO_INCREMENT");
	} catch (Exception $e) {}
	try{
		$db->Execute("ALTER TABLE `user_times` modify `id` int(11) unsigned NOT NULL AUTO_INCREMENT");
	} catch (Exception $e) {}
	try{
		$db->Execute("ALTER TABLE `logs` modify `id` int(11) unsigned NOT NULL AUTO_INCREMENT");
	} catch (Exception $e) {}
	
}

if (version_compare($dbVersion, '3.6.15') == -1) {
	try{
		$db->Execute("create index user_type ON users_to_lessons(user_type)");
	} catch (Exception $e) {
		if ($e ->getCode() != 1061) {
			$failed_queries[] = $e->getMessage();
		}
	}
	
	try{
		$db->Execute("ALTER TABLE `module_hcd_job_description_requires_skill` ADD `specification` VARCHAR( 255 ) NULL");
	} catch (Exception $e) {
		if ($e ->getCode() != 1061 && $e ->getCode() != 1146) { //1146 is for non-existing table in community edition
			$failed_queries[] = $e->getMessage();
		}
	}	
	
	try {
		$db->Execute("
CREATE TABLE IF NOT EXISTS `users_to_files` (
  `users_LOGIN` varchar(100) NOT NULL,
  `files_ID` mediumint(8) unsigned NOT NULL,
  `counter` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`users_LOGIN`,`files_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
	} catch (Exception $e) {
		$failed_queries[] = $e->getMessage();		
	}
		
}		

//3.6.14 queries
if (version_compare($dbVersion, '3.6.14') <= 0) {

	try{
		$db->Execute("ALTER TABLE `users_to_content` ADD `visits` int default NULL");
		$db->Execute("ALTER TABLE `users_to_content` ADD `attempt_identifier` char(32) default NULL");
	} catch (Exception $e) {
		if ($e ->getCode() != 1060) {
			$failed_queries[] = $e->getMessage();
		}
	}

}

try{
	$db->Execute("ALTER TABLE `users` ADD `email_block` tinyint(1) NOT NULL default '0'");
} catch (Exception $e) {
	if ($e ->getCode() != 1060) {
		$failed_queries[] = $e->getMessage();
	}
}

if (version_compare($dbVersion, '3.6.15') <= 0) {
	try{
		$db->Execute("ALTER TABLE `logs` modify `comments` VARCHAR(256) NOT NULL default '0'");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index con_index1 ON content (lessons_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index con_index2 ON content (ctg_type)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index con_index3 ON content (parent_content_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index con_index4 ON content (previous_content_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index con_index5 ON content (active)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("ALTER TABLE `scorm_data` 
ADD INDEX `scd_lesson_status` (`lesson_status`), 
ADD INDEX `scd_content_ID` (`content_ID`);");
	} catch (Exception $e) {}

	try{
		$db->Execute("create index cou_indx1 ON courses (archive)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index cou_indx2 ON courses (directions_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index cou_indx3 ON courses (instance_source)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index users_indx1 ON users (user_type)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index cal_indx1 ON calendar (type)");
	} catch (Exception $e) {}

	try{
		$db->Execute("create index cal_indx2 ON calendar (foreign_ID,type)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index com_indx2 ON comments (content_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index event_indx1 ON events (type)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index forum_indx1 ON f_forums (lessons_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index fm_indx1 ON f_messages (f_topics_ID)");
	} catch (Exception $e) {}

	try{
		$db->Execute("create index ftop_indx1 ON f_topics (f_forums_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index lescon_indx1 ON lesson_conditions (lessons_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index les_indx1 ON lessons (directions_ID)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index les_indx2 ON lessons (active)");
	} catch (Exception $e) {}
	
	try{
		$db->Execute("create index files_path ON files (path(50))");
	} catch (Exception $e) {}
	
	try{
	    $db->Execute("alter table scorm_data add objectives text");
	} catch (Exception $e) {}

	try{
		$db->Execute("ALTER TABLE `sent_notifications` ADD `html_message` TINYINT( 1 ) NULL DEFAULT '0';");
	} catch (Exception $e) {}	
	
}


if (!empty($failed_queries)) {
	throw new Exception(implode('<br/>', $failed_queries));
}

