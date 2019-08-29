<?php

class manage_news_upload_image {

	/**
	*/
	function _init() {
		main()->USE_SYSTEM_CACHE = false;
	}

	/**
	*/
	function upload_image () {
		$funcNum = $_GET['CKEditorFuncNum'];
		$url = '';
		$news = module('news');
		$news_table = $news->news_table;
		$news_images_table = $news->news_images_table;
		$news_images_path = $news->news_images_path;
		if (!isset($_FILES['upload'])) {
			return $this->_show_message('Image was not uploaded');
		}
		$filename = $_FILES['upload']['tmp_name'];
		$message = '';
		$news_id = intval($_GET['id']);
		$news_info = $news->_get_info($news_id, false, $no_cache = true);
		if (empty($news_info['id'])) {
			return $this->_show_message('Wrong news entry ID');
		}
		$img_info = [];
		if (file_exists($filename)) {
			$img_info = getimagesize($filename);
		}
		if (!in_array($img_info['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
			return $this->_show_message('File is not an image');
		}
		db()->insert_safe($news_images_table, [
			'news_id'	=> $news_id,
			'name'		=> '',
			'name_orig'	=> $_FILES['upload']['name'],
			'mime'		=> $img_info['mime'],
			'size'		=> filesize($filename),
			'width'		=> $img_info[0],
			'height'	=> $img_info[1],
			'add_date'	=> $_SERVER['REQUEST_TIME'],
		]);
		$new_id = db()->insert_id();
		$new_name = $_FILES['upload']['name'];
		$ext = pathinfo($new_name, PATHINFO_EXTENSION);
		$new_name = str_replace('.'.$ext, '', $new_name);
		$new_name = $new_id.'_'.common()->_propose_url_from_name($new_name).'.'.$ext;

		db()->update_safe($news_images_table, ['name' => $new_name], '`id`='.$new_id);

		$img_path = $news_images_path. $news_id. '/'. $new_name;
		_mkdir_m(PROJECT_PATH. dirname($img_path));
		move_uploaded_file($filename, PROJECT_PATH. $img_path);

		if (file_exists(PROJECT_PATH. $img_path)) {
			$url = WEB_PATH. $img_path;
		} else {
			db()->delete($news_images_table, $new_id);
			return $this->_show_message('Image upload error');
		}
		return $this->_show_message($message, $url);
	}

	/**
	*/
	function _show_message($message, $url = '') {
		$funcNum = $_GET['CKEditorFuncNum'];
		echo '<script type="text/javascript">window.opener.CKEDITOR.tools.callFunction('.$funcNum.', "'.$url.'", "'.$message.'");</script>';
		exit;
	}
}
