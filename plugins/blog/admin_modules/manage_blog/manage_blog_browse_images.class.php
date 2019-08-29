<?php

class manage_blog_browse_images {

	public $ALLOWED_EXTS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
	];

	/**
	*/
	function _init() {
		main()->USE_SYSTEM_CACHE = false;
	}

	function browse_images () {
		return $this->show();
	}

	function show () {
		$blog = module('blog');
		$blog_table = $blog->blog_table;
		$blog_images_table = $blog->blog_images_table;
		$funcNum = $_GET['CKEditorFuncNum'];
		$blog_id = (int)$_GET['id'];
		$blog_info = $blog->_get_info($blog_id, false, $no_cache = true);
		if (!$blog_info) {
			return common()->show_error_message('Wrong article ID');
		}
#		$blog_images_path = $blog->blog_images_path. $blog_id;
		$blog_images_path = 'uploads/blog/item_images/'. $blog_id;

		$url = './?object=manage_blog&action=browse_images&id='.$blog_id.'&CKEditor='.$_GET['CKEditor'].'&CKEditorFuncNum='.$funcNum.'&langCode='.$_GET['langCode'];

		$items = [];
		$i = 0;
		foreach (glob(rtrim(PROJECT_PATH. $blog_images_path, '/'). '/*') as $f) {
			if (!strlen($f) || !file_exists($f)) {
				continue;
			}
			if (is_dir($f)) {
				continue;
			}
			$item = basename($f);
			$ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
			if (!in_array($ext, $this->ALLOWED_EXTS)) {
				continue;
			}
			list($w, $h) = getimagesize($f);
			$items[urlencode($item)] = [
				'url'		=> WEB_PATH. $blog_images_path. '/'. $item,
				'size'		=> intval(filesize($f) / 1024).'Kb',
				'resolution'=> $w.'x'.$h,
				'add_date'	=> date('Y-m-d H:i:s', filemtime($f)),
				'name'		=> $item,
				'select_url'=> url_admin($url. '&select_id='.urlencode($item)),
				'delete_url'=> url_admin($url. '&delete_id='.urlencode($item)),
				'do_clear'	=> ++$i % 4 == 0,
			];
		}
		if ($_GET['select_id']) {
			$name = urldecode(str_replace('/', '', basename($_GET['select_id'])));
			if ($name) {
				$img_path = PROJECT_PATH. $blog_images_path. '/'. $name;
				if (!file_exists($img_path)) {
					common()->message_error('Wrong image ID');
					return js_redirect($url);
				} else {
					return $this->_ck_show_message('', WEB_PATH. $blog_images_path. '/'. $name);
				}
			}
		} elseif ($_GET['delete_id']) {
			$name = urldecode(str_replace('/', '', basename($_GET['delete_id'])));
			if ($name) {
				$img_path = PROJECT_PATH. $blog_images_path. '/'. $name;
				if (!file_exists($img_path)) {
					common()->message_error('Wrong image ID');
				} else {
					if (file_exists($img_path)) {
						@unlink($img_path);
					}
#					common()->message_info('Image deleted');
				}
			}
			return js_redirect($url);
		}
		return common()->show_empty_page(
			tpl()->parse(__CLASS__.'/main', ['items' => (array)$items])
		);
	}

	/**
	*/
	function _ck_show_message($message, $url = '') {
		no_graphics();
		$funcNum = $_GET['CKEditorFuncNum'];
		echo '<script type="text/javascript">
			try {
				window.opener.CKEDITOR.tools.callFunction('.$funcNum.', "'.$url.'", "'.$message.'");
			} catch (e) {
				window.parent.CKEDITOR.tools.callFunction('.$funcNum.', "'.$url.'", "'.$message.'");
			}
			window.close();
		</script>';
		exit;
	}
}
