<?php
    if(!DEFINED('EGP'))
		exit(header('Refresh: 0; URL=http://'.$_SERVER['SERVER_NAME'].'/404'));

	// Установка
	if($go)
	{
		include(LIB.'web/free.php');

		$aData = array();

		$aData['subdomain'] = isset($_POST['subdomain']) ? strtolower($_POST['subdomain']) : sys::outjs(array('e' => 'Необходимо указать адрес.'), $nmch);
		$aData['domain'] = isset($_POST['domain']) ? strtolower($_POST['domain']) : sys::outjs(array('e' => 'Необходимо выбрать домен.'), $nmch);
		$aData['desing'] = isset($_POST['desing']) ? strtolower($_POST['desing']) : sys::outjs(array('e' => 'Необходимо выбрать шаблон.'), $nmch);
		$aData['passwd'] = isset($_POST['passwd']) ? $_POST['passwd'] : sys::passwd($aWebParam[$url['subsection']]['passwd']);

		$aData['type'] = $url['subsection'];
		$aData['server'] = array_merge($server, array('id' => $id));

		$aData['config_sql'] = 'amx_sql_host "[host]"'.PHP_EOL
			.'amx_sql_user "[login]"'.PHP_EOL
			.'amx_sql_pass "[passwd]"'.PHP_EOL
			.'amx_sql_db "[login]"'.PHP_EOL
			.'amx_sql_table "admins"'.PHP_EOL
			.'amx_sql_type "mysql"';

		$aData['config_php'] = '<?php'.PHP_EOL
			.'    $config->db_host = "[host]";'.PHP_EOL
			.'    $config->db_user = "[login]";'.PHP_EOL
			.'    $config->db_pass = "[passwd]";'.PHP_EOL
			.'    $config->db_db = "[login]";'.PHP_EOL
			.'    $config->db_prefix = "amx";'.PHP_EOL
			.'?>';

		web::install($aData, $nmch);
	}

	$html->nav('Установка '.$aWebname[$url['subsection']]);

	$desing = '';

	// Генерация списка шаблонов
	foreach($aWebParam[$url['subsection']]['desing'] as $name => $desc)
		$desing .= '<option value="'.$name.'">'.$desc.'</option>';

	$domains = '';

	// Генерация списка доменов
	foreach($aWebUnit['domains'] as $domain)
		$domains .= '<option value="'.$domain.'">.'.$domain.'</option>';

	$html->get('install', 'sections/web/'.$url['subsection'].'/free');

		$html->set('id', $id);

		$html->set('desing', $desing);
		$html->set('domains', $domains);

	$html->pack('main');
?>