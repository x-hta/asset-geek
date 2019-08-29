<?php
$dev_settings = dirname(__DIR__).'/.dev/override.php';
if (file_exists($dev_settings)) {
    require_once $dev_settings;
}
$saved_settings = __DIR__.'/saved_settings.php';
if (file_exists($saved_settings)) {
    require_once $saved_settings;
}
define('BETA_TEST_MODE', true);
define('YF_PATH', '../yf/');
define('WEB_PATH', '//'.$_SERVER['HTTP_HOST'].'/');
define('SITE_DEFAULT_PAGE', './?object=blog');
define('SITE_ADVERT_NAME', 'Asset Geek Blog');
require dirname(__DIR__).'/config/project_conf.php';
$PROJECT_CONF['tpl']['REWRITE_MODE'] = true;
define('DEBUG_MODE', true);
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', $no_db_connect = false, $auto_init_all = true);
$dev_settings = dirname(dirname(__FILE__)).'/.dev/override_after.php';
if (file_exists($dev_settings)) {
    require_once $dev_settings;
}
