<?php
$db = DevblocksPlatform::getDatabaseService();

$tables = $db->metaTables();

// ***** Application
if(!isset($tables['rotation_log'])) {
	$sql ="
		CREATE TABLE IF NOT EXISTS rotation_log (
			id int(11) NOT NULL AUTO_INCREMENT,
			date int(32) NOT NULL,
			group_id int(11) NOT NULL,
			scheme_id int(11) NOT NULL,
			worker_id int(11) NOT NULL,
			ticket_id int(11) NOT NULL,
			reason varchar(50) NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	
	$db->Execute($sql);
	
	$tables['rotation_log'] = 'rotation_log';
}

if(!isset($tables['rotation_scheme'])) {
	$sql ="
		CREATE TABLE IF NOT EXISTS rotation_scheme (
			id int(11) NOT NULL AUTO_INCREMENT,
			group_id int(11) NOT NULL,
			name varchar(50) NOT NULL DEFAULT '',
			active int(1) NOT NULL,
			close_days int(11) NOT NULL,
			alert_days int(11) NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	
	$db->Execute($sql);
	
	$tables['rotation_scheme'] = 'rotation_scheme';
}

return TRUE;

?>