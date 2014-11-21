<?php

$db = new MySQLi('localhost', 'root', '1234', 'radius');

require __DIR__ . '/class/users.php';
require __DIR__ . '/class/devices.php';
require __DIR__ . '/class/site.php';

$ssid = 'WiFi Mobile';

$susers = array(
		'restricted1'	=> 2,
		'restricted2'	=> 2,
		'restricted3'	=> 2
);

$notifyList = array( 'alerts@infitialis.com' );

$s = new Site($susers, @$_SERVER['PHP_AUTH_DIGEST']);

$u = new Users($db, $ssid, $s->getSite(), $notifyList);
$d = new Devices($db, $ssid, $s->getSite(), $u, $s->getUsername());
