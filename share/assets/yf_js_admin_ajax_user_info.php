<?php

return function() {

return [
	'require' => [
		'asset' => [
			'jquery',
			'jquery-ajax-queue',
			'bootstrap-theme',
			'yf_js_popover_fix',
			'assetgeek_admin_ajax_popovers',
		],
	],
	'config' => [
		'no_cache' => true,
		'main_type' => 'admin',
	],
];

};