<?php
    if(!DEFINED('EGP'))
		exit(header('Refresh: 0; URL=http://'.$_SERVER['SERVER_NAME'].'/404'));

	$sql->query('SELECT `unit`, `address`, `game`, `status`, `plugins__use`, `ftp_use`, `console_use`, `stats_use`, `copy_use`, `web_use`, `time` FROM `servers` WHERE `id`="'.$id.'" LIMIT 1');
	$server = $sql->get();

	sys::nav($server, $id, 'settings');

	include(sys::route($server, 'settings', $go));
?>