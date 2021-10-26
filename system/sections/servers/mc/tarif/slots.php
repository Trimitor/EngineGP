<?php
    if(!DEFINED('EGP'))
		exit(header('Refresh: 0; URL=http://'.$_SERVER['SERVER_NAME'].'/404'));

	if(!isset($nmch))
		$nmch = false;

	// Если фикс. значение слот
	if($tarif['slots_min'] == $tarif['slots_max'])
		sys::outjs(array('e' => 'На данном тарифе нельзя изменить количество слот.'), $nmch);

	$slots = isset($url['slots']) ? sys::int($url['slots']) : sys::outjs(array('e' => 'Переданы не все данные.'), $nmch);

	$aPrice = explode(':', $tarif['price']);
	$aRAM = explode(':', $tarif['ram']);

	$ram = $server['ram_fix'] ? $server['ram'] : $server['ram']/$server['slots'];

	$overdue = $server['time'] < $start_point;

	if($cfg['change_slots'][$server['game']]['days'] || $overdue)
	{
		// Цена за 1 день
		$price = $aPrice[array_search($ram, $aRAM)]/30;

		// Цена аренды за остаток дней (с текущим кол-вом слот)
		$price_old = ($server['time']-$start_point)/86400*$price*$server['slots'];
	}

	$max = $tarif['slots_max']-$server['slots'];

	// Сумма за добавляемые слоты
	$sum = round(($server['time']-$start_point)/86400*($aPrice[array_search($ram, $aRAM)]/30)*$slots, 2);

	// Изменение кол-ва слот за счет пересчета дней аренды или закончился срок аренды (иначе аренда дополнительных слот)
	if($cfg['change_slots'][$server['game']]['days'] || $overdue)
	{
		$ram = $server['ram_fix'] ? $server['ram'] : $server['ram']/$server['slots']*$slots;

		// Если просрочен
		if($overdue)
		{
			sys::outjs(array('i' => ''));

			if($go)
			{
				$start = $server['slots_start'] > $slots ? ', `slots_start`="'.$slots.'"' : '';

				$sql->query('UPDATE `servers` set `slots`="'.$slots.'" '.$start.', `ram`='.$ram.' WHERE `id`="'.$id.'" LIMIT 1');

				// Запись логов
				$sql->query('INSERT INTO `logs__sys` set `user`="'.$user['id'].'", `server`="'.$id.'", `text`="'.sys::text('syslogs', 'change_slots').'", `time`="'.$start_point.'"');

				sys::outjs(array('s' => 'ok'), $nmch);
			}
		}

		// При возможности уменьшить
		if($cfg['change_slots'][$server['game']]['down'] || $overdue)
		{
			// Проверка кол-ва слот
			if($slots < $tarif['slots_min'] || $slots > $tarif['slots_max'])
				sys::outjs(array('e' => 'Переданые неверные данные.'), $nmch);

			if($server['slots'] == $slots)
			{
				if($go)
					sys::outjs(array('s' => 'ok'), $nmch);

				sys::outjs(array('s' => 'Сервер будет арендован до: '.date('d.m.Y - H:i', $server['time']).' ('.sys::date('min', $server['time']).')'), $nmch);
			}
		}else{
			// Установлено макс. значение
			if($server['slots'] == $tarif['slots_max'] AND !$overdue)
				sys::outjs(array('e' => 'На игровом сервере установлено максимальное значение.'), $nmch);

			if($slots < 1 || $slots > $max)
				sys::outjs(array('e' => 'Переданы неверные данные'), $nmch);

			$slots += $server['slots'];
		}

		$date = date('H.i.s.d.m.Y', round($start_point+$price_old/($price*$slots)*86400-86400));

		$aDate = explode('.', $date);

		$time = mktime($aDate[0], $aDate[1], $aDate[2], $aDate[4], $aDate[3], $aDate[5]);

		// При уменьшении кол-ва слот не добавлять дни
		if($slots < $server['slots'] AND ($cfg['change_slots'][$server['game']]['days'] AND $cfg['change_slots'][$server['game']]['down'] AND !$cfg['change_slots'][$server['game']]['add']))
			$time = $server['time'];

		// Выполнение операции
		if($go)
		{
			sys::benefitblock($id, $nmch);

			$start = $server['slots_start'] > $slots ? ', `slots_start`="'.$slots.'"' : '';

			$sql->query('UPDATE `servers` set `time`="'.$time.'", `slots`="'.$slots.'" '.$start.', `ram`='.$ram.' WHERE `id`="'.$id.'" LIMIT 1');

			if(in_array($server['status'], array('working', 'start', 'restart')) AND $slots < $server['slots_start'])
			{
				include(LIB.'games/'.$server['game'].'/action.php');

				action::start($id, 'restart');
			}

			// Запись логов
			$sql->query('INSERT INTO `logs__sys` set `user`="'.$user['id'].'", `server`="'.$id.'", `text`="'.sys::text('syslogs', 'change_slots').'", `time`="'.$start_point.'"');

			sys::outjs(array('s' => 'ok'), $nmch);
		}

		// Выхлоп информации
		sys::outjs(array('s' => 'Сервер будет арендован до: '.$arenda.' '.date('d.m.Y - H:i', $time).' ('.sys::date('min', $time).')'));
	}

	if($slots < 1 || $slots > $max)
		sys::outjs(array('e' => 'Переданые неверные данные'), $nmch);

	// Выполнение операции
	if($go)
	{
		sys::benefitblock($id, $nmch);

		$slots_new = $server['slots']+$slots;

		// Проверка баланса
		if($user['balance'] < $sum)
			sys::outjs(array('e' => 'У вас не хватает '.(round($sum-$user['balance'], 2)).' '.$cfg['currency']), $nmch);

		// Списание средств с баланса пользователя
		$sql->query('UPDATE `users` set `balance`="'.($user['balance']-$sum).'" WHERE `id`="'.$user['id'].'" LIMIT 1');

		// Реф. система
		games::part($user['id'], $sum);

		$start = $server['slots_start'] == $server['slots'] ? ', `slots_start`="'.$slots_new.'"' : '';

		$ram = $server['ram_fix'] ? $server['ram'] : $server['ram']/$server['slots']*$slots_new;

		// Обновление информации
		$sql->query('UPDATE `servers` set `slots`="'.$slots_new.'" '.$start.', `ram`='.$ram.' WHERE `id`="'.$id.'" LIMIT 1');

		if(in_array($server['status'], array('working', 'start', 'restart')) AND $slots_new != $server['slots_start'])
		{
			include(LIB.'games/'.$server['game'].'/action.php');

			action::start($id, 'restart');
		}

		// Запись логов
		$sql->query('INSERT INTO `logs` set `user`="'.$user['id'].'", `text`="'.sys::updtext(sys::text('logs', 'buy_slots'),
			array('slots' => $slots, 'money' => $sum, 'id' => $id)).'", `date`="'.$start_point.'", `type`="buy", `money`="'.$sum.'"');

		sys::outjs(array('s' => 'ok'), $nmch);
	}

	// Выхлоп информации
	sys::outjs(array('s' => 'Цена за дополнительные слоты: '.$sum.' '.$cfg['currency']));
?>