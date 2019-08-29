<?php

class test_403 {
	function show() {
		no_graphics(true);
		header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
		header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 403 Forbidden');
		header('Cache-Control: private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
		header('Content-Type: text/html; charset=utf8');
		$accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$has_ru = (false !== strpos($accept, 'ru'));
		echo '<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">';
		echo 'This website is not available in your country.'. ($has_ru ? PHP_EOL. '<br />Сайт недоступен в вашей стране' : '');
		exit;
	}
}
