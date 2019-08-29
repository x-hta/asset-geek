<?php

return function($assets) {

global $CONF;

$main_type = $assets->_override['main_type'] ?: MAIN_TYPE;
if (!(is_console() || $assets->_override['main_type'] || $main_type == 'user')) {
	$bs_theme = common()->bs_current_theme($main_type, $force_default = false);
} else {
	$bs_theme = common()->bs_current_theme($main_type, $force_default = true);
}
$bs_major_version = '3';
$require_name = 'bootstrap'. $bs_major_version;
$fixes_name = 'yf_bootstrap_fixes_'.$main_type;

if ($bs_theme === 'bootstrap') {
	conf('bs3_no_default_theme', true);
	return [
		'require' => [
			'asset' => 'bootstrap3',
		],
		'add' => [
			'asset' => [
				'font-awesome4',
				$fixes_name,
			],
			'css' => $CONF['css_'.$main_type.'_override'],
			'jss' => $CONF['js_'.$main_type.'_override'],
		],
	];
} elseif ($bs_theme === 'bootstrap_theme') {
	return [
		'require' => [
			'asset' => 'bootstrap3',
		],
		'add' => [
			'asset' => [
				'font-awesome4',
				$fixes_name,
			],
			'css' => $CONF['css_'.$main_type.'_override'],
			'jss' => $CONF['js_'.$main_type.'_override'],
		],
	];
} else {
	conf('bs3_no_default_theme', true);
	return [
		'versions' => [
			'3.3.7' => [
				'css' => '//rawgit.yfix.net/thomaspark/bootswatch/v3.3.7/'.$bs_theme.'/bootstrap.min.css',
			],
		],
		'github' => [
			'name' => 'thomaspark/bootswatch',
			'version' => 'v3.3.7',
			'css' => [
				$bs_theme.'/bootstrap.css',
				$bs_theme.'/bootstrap.min.css',
			],
		],
		'require' => [
			'asset' => 'bootstrap3',
		],
		'add' => [
			'asset' => [
				'font-awesome4',
				$fixes_name,
			],
			'css' => $CONF['css_'.$main_type.'_override'],
			'jss' => $CONF['js_'.$main_type.'_override'],
		],
	];
}

};
