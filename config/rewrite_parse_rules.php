<?php

return [
    function(&$url, $query, $host, $class_rewrite) {
        $is_db = main()->is_db();
        if (!$is_db) {
            return; // fast_init
        }
        $i18n_class = _class('i18n');
        if (!isset($class_rewrite->langs)) {
            $class_rewrite->langs = $i18n_class->_get_langs();
            $class_rewrite->default_lang = $i18n_class->_get_default_lang();
        }
        if (strlen($url[0]) === 2 && in_array($url[0], ['en', 'ru', 'uk', 'zh', 'es'])) {
            $try_lang = $url[0];
            if (isset($class_rewrite->langs[$try_lang])) {
                // Remove first element from url pattern and allow to continue with other rules
                array_shift($url);
                if ($try_lang === $class_rewrite->default_lang) {
                    _302('/'.ltrim(implode('/', $url), '/'));
                } else {
                    $_GET['lang'] = $try_lang;
#                    conf('language', $try_lang);
                    if ($i18n_class->CUR_LOCALE != $try_lang) {
                        $i18n_class->_get_current_lang($force = true);
                        $i18n_class->init_locale();
                    }
                    $class_rewrite->current_lang = $try_lang;
                }
            }
        } else {
            $lang = $class_rewrite->default_lang;
            $_GET['lang'] = $lang;
#            conf('language', $lang);
            if ($i18n_class->CUR_LOCALE != $lang) {
                $i18n_class->_get_current_lang($force = true);
                $i18n_class->init_locale();
            }
            $class_rewrite->current_lang = $lang;
        }
    },
    function($url, $query, $host, $class_rewrite) {
        $s = '';
        foreach ((array)$url as $k => $v) {
            if ($v == '') {
                unset($url[$k]);
            }
        }
        function _get_id_by_url($url, $object, $cat_id = false) {
            if (count((array)$url) == 2 || count((array)$url) == 3) {
                $item_url = $cat_id == 1 ? $url[1] : $url[2];
                load($object);
                $obj = _class($object);
                if (!$cat_id) {
                    return $obj->_get_id_by_url($item_url);
                } else {
                    return $obj->_get_id_by_url($item_url, $cat_id);
                }
            } else {
                return false;
            }
        }

        function _valid_object($url) {
            $valid_values = ['tags'];
            if (is_array($url) && (count((array)$url) >= 1) && in_array($url[0], $valid_values)) {
                $object = $url[0];
                return $object;
            } else {
                return false;
            }
        }

        function _valid_blog_category($url){
			if (is_array($url) && (count((array)$url) >= 1)) {
				if(count((array)$url) == 1) {
					$key = $url[0];
				}else {
					$page_number = _page_number($url[1]);
					if($page_number){
						$key = $url[0];
					}else{
						$key =  $url[0] == 'news' ? $url[0] : $url[1];
					}

				}
				$cat_info = getset('rewrite_parse_blog_cat_'.crc32($key ), function() use ($key) {
					$cats_class = _class('cats');
					return $cats_class->_get_category_by_url($key, $cats_class->blog_category) ?: [];
				});
				if (!empty($cat_info['id'])) {
					return $cat_info;
				}
			}
			return false;
        }

        function _page_number($str) {
            if(preg_match('/^page\-([0-9]{1,})$/',$str, $matches) ===1){
                return intval($matches[1]);
            } else {
                return false;
            }
        }

        $object = _valid_object($url);
        if ($object !== false) {
            switch (count((array)$url)) {
			case 1:
				$s = 'object=' . $object . '&action=show';
				break;
			case 2:
				$item_id = _get_id_by_url($url, $object);
				if ($item_id !== false) {
					if($object == 'tags'){
						$s = 'object=' . $object . '&action=view&tag_id=' . $item_id;
					}else{
						$s = 'object=' . $object . '&action=view&id=' . $item_id;
					}
				} else {
					$page_number = _page_number($url[1]);
					if ($page_number !== false) {
						$s = 'object=' . $object . '&action=show&page=' . $page_number;
					}
				}
				break;
			case 3:
				$item_id = _get_id_by_url($url, $object);
				$page_number = _page_number($url[2]);
				if ($item_id !== false) {
				//	$s = 'object=' . $object . '&action='.$url[1];
					$s = 'object=' . $object . '&action=view&tag_id=' . $item_id;
					if ($page_number !== false) {
						$s .= '&page=' . $page_number;
					}
				} else {
					if ($page_number !== false) {
						$s = 'object=' . $object . '&action=show&page=' . $page_number;
					}
				}
				break;
            }
        } else {
			$cat_info = _valid_blog_category($url);
            $object = module('blog')->object_name;
            if ($cat_info != false) {
                switch (count((array)$url)) {
                    case 1:
                        $s = 'object='.$object.'&action=show&cat_id=' . $cat_info['id'];
                        break;
                    case 2:
						if($cat_info['id'] == 1){
							$page_number = _page_number($url[1]);
							if ($page_number !== false) {
								$s = 'object='.$object.'&action=show&cat_id=' . $cat_info['id'].'&page=' . $page_number;
							} else {
								$item_id = _get_id_by_url($url, $object, $cat_info['id']);
								$s = 'object=' . $object . '&action=view&blog_id=' . $item_id;
							}
						}else{
							$page_number = _page_number($url[1]);
							if ($page_number !== false) {
								$s = 'object='.$object.'&action=show&cat_id=' . $cat_info['id'].'&page=' . $page_number;
							} else {
								$s = 'object='.$object.'&action=show&cat_id=' . $cat_info['id'];
							}
						}
						break;
					case 3:
						$page_number = _page_number($url[2]);
						if ($page_number !== false) {
							$s = 'object='.$object.'&action=show&cat_id=' . $cat_info['id'].'&page=' . $page_number;
						}else{
							$item_id = _get_id_by_url($url, $object, $cat_info['id']);
							$s = 'object=' . $object . '&action=view&blog_id=' . $item_id .'&cat_id='. $cat_info['id'];
						}
						break;
                }
            }
		}
        return $s;
    },
];
