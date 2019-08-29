<?php

return [
	function(&$a, $class_rewrite, &$is_last) {
		$is_db = main()->is_db();
		if (!$is_db) {
			return; // fast_init
		}
        $add_pagination = function($params, $str) {
            if(isset($params['page'])) {
                return $str.'page-'.intval($params['page']).'/';
            }
            else {
                return $str;
            }
        };

        $valid_objects = ['articles', 'traders', 'bots'];
        // view-show traders, articles
        if (in_array($a['object'], $valid_objects) && ($a['action'] === 'view' || $a['action'] === 'show')) {
            load($a['object']);
            $obj = _class($a['object']);
            $object = $a['object'];
            if($object == 'articles') {
                $object = $obj->rewrite_name;
            }
            switch($a['action'])
            {
                case 'view':
                    if(!empty($a['url'])){
                        $view_url = $a['url'];
                        unset($a['url']);
                    }
                    else {
                        $info = $obj->_get_info($a['id']);
                        $view_url = $info['url'] ?: false;
                    }
                    if($view_url) {
                        $str = $object . '/' . urlencode(strtolower($view_url)) . '/';
                    }
                    break;
                case 'show':
                    $str = $object . '/';
                    $str = $add_pagination($a, $str);
                    break;
            }
            return $str;
        }

        //view static pages
        if (($a['object'] === 'static_pages') && $a['action'] === 'show') {
            load(($a['object']));
            $static_pages_class = _class($a['object']);
            $page_info = $static_pages_class->_get_info($a['id']);
            $str = $page_info['url'];
            return $str;
        }
    },
];
