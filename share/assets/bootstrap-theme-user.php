<?php

return function($assets) {

global $CONF;
$main_type = $assets->_override['main_type'] ?: MAIN_TYPE;

return [
	'versions' => [
		'3.3.7' => [
			'js' => [
				'//rawgit.yfix.net/twbs/bootstrap/v3.3.7/dist/js/bootstrap.min.js',
			],
		],
	],
	'github' => [
		'name' => 'twbs/bootstrap',
		'version' => 'v3.3.7',
		'js' => [
			'dist/js/bootstrap.min.js',
		],
	],
	'require' => [
		'asset' => [
			'jquery',
			'jquery-ui'
		],
	],
	'add' => [
		// 'asset' => [
			// 'font-awesome4',
		// ],
		'css' => [
			PROJECT_PATH. 'css/style.min.css',
			$CONF['css_'.$main_type.'_override'],
		],
		'js' => $CONF['js_'.$main_type.'_override'],
	],
];

};
