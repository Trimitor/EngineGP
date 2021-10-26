<?php
	if(!DEFINED('EGP'))
		exit(header('Refresh: 0; URL=http://'.$_SERVER['SERVER_NAME'].'/404'));
    
    $html->get('index', 'sections/settings');

    $html->pack('main');
?>