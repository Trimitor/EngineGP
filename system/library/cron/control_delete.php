<?php
	if(!DEFINED('EGP'))
		exit(header('Refresh: 0; URL=http://'.$_SERVER['SERVER_NAME'].'/404'));

	class control_delete extends cron
	{
		function __construct()
		{
			global $cfg, $sql, $argv;

			$sql->query('SELECT `id` FROM `control` WHERE `user`="-1" LIMIT 1');

			if(!$sql->num())
				return NULL;

			$unit = $sql->get();

			$servers = $sql->query('SELECT `id` FROM `control__servers` WHERE `unit`="'.$unit['id'].'"');
			while($server = $sql->get($servers))
			{
				$sql->query('DELETE FROM `control__admins_'.$server['game'].'` WHERE `server`="'.$server['id'].'"');
				$sql->query('DELETE FROM `control__copy` WHERE `server`="'.$server['id'].'"');
				$sql->query('DELETE FROM `control__firewall` WHERE `server`="'.$server['id'].'"');
				$sql->query('DELETE FROM `control__plugins_install` WHERE `server`="'.$server['id'].'"');
			}

			// Удаление различной информации игрового сервера
			$sql->query('DELETE FROM `control__servers` WHERE `unit`="'.$unit['id'].'"');
			$sql->query('DELETE FROM `control` WHERE `id`="'.$unit['id'].'"');

			$sql->query('INSERT INTO `logs__sys` set `user`="0", `control`="'.$unit['id'].'", `text`="Удаление подключенного сервера #'.$unit['id'].' ('.$unit['address'].') passwd: #'.$unit['passwd'].'", `time`="'.$start_point.'"');

			return NULL;
		}
	}
?>