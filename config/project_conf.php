<?php

$CONF['PROJECT_LAUNCH_DATE'] = '2016-10-01';

$CONF['css_framework'] = 'bs3';
$CONF['DEF_BOOTSTRAP_THEME'] = 'bootstrap';
$CONF['DEF_BOOTSTRAP_THEME_ADMIN'] = 'slate';

$CONF['date_short_format'] = '%d.%m.%y';
$CONF['date_long_format'] = '%d.%m.%y %H:%M';
$CONF['date_short_format_en'] = '%m/%d/%y';
$CONF['date_long_format_en'] = '%m/%d/%y %H:%M';

$CONF['site_title'] = 'Assetgeek';

define('SITE_UPLOADS_DIR',	'uploads/');
define('SITE_AVATARS_DIR',	'uploads/avatars/');
if (!defined('SITE_ADVERT_NAME')) {
	define('SITE_ADVERT_NAME',	$_SERVER['HTTP_HOST']);
}
define('SITE_ADVERT_TITLE',	$_SERVER['HTTP_HOST']);
define('SITE_ADVERT_URL',	defined('WEB_PATH') ? WEB_PATH : '');
define('SITE_ADMIN_NAME',	'admin');
define('SITE_ADMIN_EMAIL',	'admin@'.WEB_DOMAIN);

$CONF['NO_LANG_OBJECT'] = [
	'api',
	'dynamic',
];

define('AVATAR_MAX_X',	100);	// Avatar max sizes
define('AVATAR_MAX_Y',	100);
define('THUMB_WIDTH',	120);	// Thumbnail width (default value)
define('THUMB_HEIGHT',	1000);	// Thumbnail maximum height (default value)
define('THUMB_QUALITY',	75);	// JPEG quality
define('MAX_IMAGE_SIZE',5000000);// Max image file size (in bytes)
define('FORCE_RESIZE_IMAGE_SIZE',500000);
define('FORCE_RESIZE_WIDTH',	1280);	// width for force resize
define('FORCE_RESIZE_HEIGHT',	1024);	// height for force resize

define('MYSQL_TIME_FORMAT', 'Y-m-d H:i:s');

$CONF['GOOGLE_ANALYTICS_ID'] = 'UA-76045335-1';
$CONF['YANDEX_METRIKA_ID'] = strtoupper($_SERVER['GEOIP_COUNTRY_CODE']) == 'UA' ? '' : '36516525';

$CONF['COMMISION'] = '0.1';
$CONF['SMTP'] = require __DIR__.'/smtp.php';

define('WINTER_TIME',	true);
$CONF['ADD_WINTER_HOUR'] = WINTER_TIME ? 3600 : 0;

if (!function_exists('my_array_merge')) {
	function my_array_merge($a1, $a2) {
		foreach ((array)$a2 as $k => $v) { if (isset($a1[$k]) && is_array($a1[$k])) { if (is_array($a2[$k])) {
			foreach ((array)$a2[$k] as $k2 => $v2) { if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) { $a1[$k][$k2] += $v2; } else { $a1[$k][$k2] = $v2; }
		} } else { $a1[$k] += $v; } } else { $a1[$k] = $v; } }
		return $a1;
	}
}
$host = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) ?: $_SERVER['HTTP_HOST'];

!isset($CONF['language']) && $CONF['language'] = 'en';

!isset($CONF['prod_hosts']) && $CONF['prod_hosts'] = [
//	'assetgeek.com',
];

$PROJECT_CONF = my_array_merge((array)$PROJECT_CONF, [
	'main'	=> [
		'USE_CUSTOM_ERRORS'			=> true,
//		'USE_SYSTEM_CACHE'			=> true,
		'SPIDERS_DETECTION'			=> true,
//		'ALLOW_FAST_INIT'			=> true,
		'LOG_EXEC'					=> true,
		'STATIC_PAGES_ROUTE_TOP'	=> true,
		'ENABLE_NOTIFICATIONS_USER' => false,
//		'TRACK_ONLINE_STATUS'		=> true,
//		'TRACK_ONLINE_DETAILS'		=> true,
		'SESSION_LIFE_TIME'			=> 3600 * 36, // 36 hours
	],
	'assets'	=> [
#		'ALLOW_GET_FROM_BOWER' => true,
		'ALLOW_GET_FROM_GITHUB' => true,
		'ALLOW_GET_FROM_CDN' => true,
	],
	'graphics' => [
		'META_DESCRIPTION' => '',
#		'META_ADD' => [
#			'google-site-verification' => '',
#		],
	],
	'site_map' => [
		'SITE_MAP_ENABLED' => true,
		'MODULES_TO_INCLUDE' => [
			'blog',
			'static_pages',
			'faq',
#			'home_page',
#			'profile',
#			'tournaments',
#			'battles',
		],
#		'TEST_MODE' => true,
	],
    'cache' => [
        'CACHE_NS'  => 'cache:',
        'DRIVER'  => 'redis',
    ],
	'user_handler' => [
		'HOST_NAME' => '//assetgeek.com/',
	],
	'auth_user' => [
		'URL_SUCCESS_LOGIN' => './?object=home_page',
		'EXEC_AFTER_LOGIN'		=> [
			['_add_login_activity'],
			function( $auth_user ) {
				$api_desktop = _class( 'api_desktop' )->login( $auth_user );
			},
		],
		'EXEC_AFTER_LOGOUT' => [
			function( $auth_user ) {
				$api_desktop = _class( 'api_desktop' )->logout( $auth_user );
			},
		],
		'EXEC_AFTER_INIT' => [
			function( $auth_user ) {
				$api_desktop = _class( 'api_desktop' )->auth_check( $auth_user );
			},
		],
		'SESSION_LOCK_TO_IP' => false,
		'SESSION_LOCK_TO_UA' => false,
		'SESSION_LOCK_TO_HOST' => true,
		'SET_MEMBER_ID_COOKIE' => 'member_id',
		'SET_IS_LOGGED_COOKIE' => 'is_logged_in',
#		'USER_SECURITY_CHECKS' => true,
	],
	'auth_admin' => [
		'EXEC_AFTER_LOGIN' => [
			function($auth_user) {
				$api_desktop = _class('api_desktop')->login($auth_user);
			},
		],
		'EXEC_AFTER_LOGOUT' => [
			function($auth_user) {
				$api_desktop = _class('api_desktop')->logout($auth_user);
			},
		],
		'EXEC_AFTER_INIT' => [
			function($auth) {
				$uid = main()->USER_ID;
				if ($uid) {
					$param = $auth_user->SET_MEMBER_ID_COOKIE;
					if ($param && !$_COOKIE[$param]) {
						$auth->_cookie_set($param, $uid);
					}
					$param = $auth->SET_IS_LOGGED_COOKIE;
					if ($param && !$_COOKIE[$param]) {
						$auth->_cookie_set($param, '1');
					}
				}
			},
			function($auth_user) {
				$api_desktop = _class('api_desktop')->auth_check($auth_user);
			},
		],
	],
	'tpl' => [
		'ALLOW_LANG_BASED_STPLS' => 1,
		'REWRITE_MODE'			=> 1,
	],
	'i18n' => [
		'TRACK_TRANSLATED'  => 1,
	],
	'debug_info' => [
		'_SHOW_NOT_TRANSLATED'  => 1,
		'_SHOW_I18N_VARS'   => 1,
	],
	'rewrite'	=> [
		'BUILD_RULES' => require_once __DIR__.'/rewrite_build_rules.php',
		'PARSE_RULES' => require_once __DIR__.'/rewrite_parse_rules.php',
		'_rewrite_add_extension'	=> '/',
	],
	'comments'	=> [
		'USE_TREE_MODE' => 1,
	],
	'captcha' => [
		'USE_GRECAPTCHA' => true,
	],
	'logs'	=> [
		'_LOGGING'			=> true,
		'STORE_USER_AUTH'	=> true,
		'UPDATE_LAST_LOGIN'	=> true,
		'LOG_EXEC_USER'		=> true,
	],
	'logs_exec_user' => [
		'LOGGING'				=> true,
		'LOG_DRIVER'			=> ['file','db'],
		'USE_STOP_LIST'			=> true,
		'STOP_LIST'				=> ['object=(aff|dynamic).*',/* 'task=(login|logout)'*/],
		'LOG_IS_USER_GUEST'		=> true,
		'LOG_IS_USER_MEMBER'	=> true,
		'LOG_IS_COMMON_PAGE'	=> true,
		'LOG_IS_HTTPS'			=> true,
		'LOG_IS_POST'			=> true,
		'LOG_IS_NO_GRAPHICS'	=> true,
		'LOG_IS_AJAX'			=> false,
		'LOG_IS_SPIDER'			=> false,
		'LOG_IS_REDIRECT'		=> false,
		'LOG_IS_UNIT_TEST'		=> false,
		'LOG_IS_CONSOLE'		=> false,
		'LOG_IS_DEV'			=> false,
		'LOG_IS_DEBUG'			=> false,
		'LOG_IS_BANNED'			=> false,
		'LOG_IS_404'			=> true,
		'LOG_IS_403'			=> true,
		'LOG_IS_503'			=> false,
		'LOG_IS_CACHE_ON'		=> true,
		'EXCLUDE_IPS'			=> [
			'127.0.0.1',
			'46.46.72.161', // Yfix team office
		],
	],
	'redirect' => [
		'LOOP_COUNT'	=> 7,
		'LOOP_TTL'		=> 5,
		'LOOP_EXCLUDE_SOURCE' => [
			'~^/[a-z0-9_]+/filter_save/~i',
			'~&action=filter_save&~i',
            '~show_card_ajax~i',
            '~user_search~i',
#			'~^/lines/~i',
		],
	],
	'db' => [
		'QUERY_REVISIONS' => true,
#		'QUERY_REVISIONS_METHODS' => [],
		'QUERY_REVISIONS_TABLES' => [
			'static_pages',
			'news',
			'faq',
			'tips',
			'blog',
			'news',
			'emails_templates',
			'wall_templates',
			'global_reviews',
			'sys_admin',
			'sys_admin_groups',
			'sys_blocks',
			'sys_block_rules',
			'sys_categories',
			'sys_category_items',
			'sys_conf',
			'sys_locale_langs',
			'sys_locale_vars',
			'sys_locale_translate',
			'sys_menus',
			'sys_menu_items',
			'sys_user_groups',
		],
	],
	'multi_upload_image' => [
		'small_image_width'   => 120,
		'small_image_height'  => 120,
		'medium_image_width'  => 600,
		'medium_image_height' => 600,
	],
	'payment' => [
		'URL_REDIRECT' => '/payments',
		'PAYIN_LIMIT_MIN' => 100,
		'PAYIN_LIMIT_MAX' => 1000000,
		'PAYIN_AMOUNT_DEF' => 300,
		'PAYOUT_AMOUNT_DEF' => 500,
		'show_yandexmoney_payin' => false,
		'show_yandexmoney_payout' => false,
	],
	'email' => [
#		'SMTP_CONFIG_DEFAULT' => $CONF['SMTP']['google'],
		'SMTP_CONFIG_DEFAULT' => $CONF['SMTP']['yandex'],
#		'SMTP_CONFIG_ALTERNATE' => $CONF['SMTP']['mailru'],
		'ADMIN_EMAIL' => 'support@assetgeek.com',
		'ADMIN_NAME' => 'Support',
		'SEND_ALL_COPY_TO' => [
			'yuri.vysotskiy@gmail.com',
		],
		'SEND_ADMIN_COPY_TO' => [
		],
		'ASYNC_SEND' => true,
	],
	'send_mail'	=> [
		'DRIVER'			=> 'phpmailer',
		'DEFAULT_CHARSET'	=> 'UTF-8',
//		'MAIL_DEBUG'		=> true,
#		'SMTP_OPTIONS'		=> $CONF['SMTP']['google'],
		'SMTP_OPTIONS'		=> $CONF['SMTP']['yandex'],
		'ON_BEFORE_SEND' => function($mail, $params) {
			$mail->XMailer = 'AssetGeek Mailer';
			$mail->Version = '2.0.0';
			$mail->ReturnPath = $params['from_mail'];
			$mail->AddReplyTo($params['from_mail']);
			$mail->SMTPOptions = [
			    'ssl' => [
			        'verify_peer' => false,
			        'verify_peer_name' => false,
			        'allow_self_signed' => true,
			    ],
			];
		},
	],
	'oauth_driver_facebook' => [
		'scope' => 'public_profile, email',
	],
	'api_lol' => [
		'API_KEY' => 'RGAPI-a27f0e6e-c602-4f23-a831-c1be421198c9',
	],
	'api_server' => [
		'ttl_rnd'     => 10,            // +/- 10%
		'ttl_value'   => 3 * 60 * 60,   // 3 hours, sec
		'limit_query' => 10000,
		'NS' => [
			'api'           => [ 'request'  ],
			'system'        => [ 'request'  ],
			'logger'        => [ 'response' ],
			'socket'        => [ 'response' ],
			'service'       => [ 'response' ],
			'manager'       => [ 'response' ],
		],
		'POOLS' => [
			'hi'   => true,
			'mid'  => true,
			'user' => true,
		],
	],
	'twitch_api' => [
		'API_ID' => 'cbzjp144ovfp4n43cekrlgbj4w85fqy',
	],
	'api_handler' => [
		'LOG_ALL_REQUESTS' => false,
	],
	'switch_langs' => true,
]);

$OVERRIDE_CONF_FILE = dirname(dirname(__FILE__)).'/.dev/override_conf_after.php';
if (file_exists($OVERRIDE_CONF_FILE)) {
	include_once $OVERRIDE_CONF_FILE;
}
// Load auto-configured file
$AUTO_CONF_FILE = dirname(__FILE__).'/_auto_conf.php';
if (file_exists($AUTO_CONF_FILE)) {
	@eval('?>'.file_get_contents($AUTO_CONF_FILE));
}
