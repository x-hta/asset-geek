<?php

function _load_framework_functions() {
	$paths = [
		YF_PATH.'share/functions/',
		YF_PATH.'functions/',
	];
	foreach ($paths as $prefix) {
		$file = $prefix. YF_PREFIX. 'common_funcs.php';
		if (file_exists($file)) {
			include_once $file;
			break;
		}
	}
};
_load_framework_functions();

if (!function_exists('recursive_array_search')) {
	function recursive_array_search($needle,$haystack) {
		if (!$needle) {
			return false;
		}
		if (!is_array($haystack)) {
			return false;
		}
		foreach ($haystack as $key=>$value) {
			$current_key = $key;
			if($needle === $value || (is_array($value) && recursive_array_search($needle,$value) !== false)) {
				return $current_key;
			}
		}
		return false;
	}
}
if (!function_exists('smart_redirect')) {
	function smart_redirect($location = '/', $rewrite = true, $text = '', $ttl = 0, $redirect = false) {
        if (conf('IS_API')) {
//            module('api')->_process_redirect($location, $rewrite, $text, $ttl);
            exit;
        }
		if (main()->is_ajax()) {
			if($redirect){
				echo $location;
				exit;
			}
			echo 'refresh';
			exit;
		}
		return js_redirect($location, $rewrite, $text, $ttl);
	}
}

