<?php

class manage_blog_authors  {

	/** @const */
	const table = 'blog_authors';
	public $AUTHOR_IMAGE_EXT = '.png';
    public $AUTHOR_IMAGE_XY = ['width'=>45, 'height'=>45];

	/**
	*/
	function _init() {
		$this->AUTHOR_IMAGE_BASE = 'uploads/blog_authors/';
		$this->AUTHOR_IMAGE_FOLDER = PROJECT_PATH.$this->AUTHOR_IMAGE_BASE;
		$this->AUTHOR_IMAGE_WEB_FOLDER = WEB_PATH.$this->AUTHOR_IMAGE_BASE;

	}

	/**
	*/
	function _show_quick_filter () {
		return '';
	}

	/**
	*/
	function show () {
		$quick_filter = $this->_show_quick_filter();
		$filter = _class('admin_methods')->_get_filter();
		return
			'<div class="col-md-12">'.
				($quick_filter ? '<div class="col-md-6 pull-right" title="'.t('Quick filter').'">'.$quick_filter.'</div>' : '').
			'</div>'.
			table(from(self::table), [
				'hide_empty' => true,
				'filter' => true,
				'filter_params' => [
					'title'			=> 'like',
				],
				'custom_fields' => [],
			])
			->on_before_render(function($p, $data, $table) {})
			->text('id')
			->image('id', ['width' => '50px', 'img_path_callback' => function ($_p1, $_p2, $row) {
				$author_image_info = "";
				$author_id = $row['id'];
				$file_name = $this->AUTHOR_IMAGE_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
				if (file_exists($file_name)) {
					$author_image_info = $this->AUTHOR_IMAGE_BASE . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
				}
                return $author_image_info;
            }])
   			->text('name')
			->text('url')
  			->btn_edit('Edit', url('/@object/edit/%d'), ['no_ajax' => 1, 'btn_no_text' => 1])
			->btn_delete('Delete', url('/@object/delete/%d'), ['btn_no_text' => 1])
			->header_add('Add', url('/@object/add'), ['no_ajax' => 1, 'class_add' => 'btn-primary'])
			->footer_add('Add', url('/@object/add'), ['no_ajax' => 1, 'class_add' => 'btn-primary'])
		;
	}

	/**
	*/
	function add() {
		$author_id = 0;
		return form($_POST, [
			'legend' => 'Add new author',
			'enctype' => 'multipart/form-data',
			'class' => 'form-vertical',
		])
		->validate([
			'name' => 'trim|required',
			'url' => ['trim|xss_clean', function($in, $tmp, $data, &$error_msg) use ($author_id) {
				if(preg_match('~^[a-z0-9\-_]*$~isu', $in) !== 1 ){
					$error_msg = t('Wrong url');
					return false;
				}
				$url_author_id = db()->get_one("SELECT `id` FROM `".db("blog_authors")."` WHERE `url`='"._es($in)."'");
				if($url_author_id !== false && $url_author_id !== $author_id ){
					$error_msg = t('Url already in use');
					return false;
				}
				return true;
			}],
		])
		->on_validate_ok(function() {
			db()->insert_safe(self::table, [
				'name'			=> $_POST['name'],
				'url'			=> $_POST['url'],
			]);
			$author_id = db()->insert_id();

			if (!empty($_FILES['author_image']) && !empty($_FILES['author_image']['name'])) {
				$file = $_FILES['author_image'];
				$tmp_img = $file['tmp_name'];
				if (file_exists($tmp_img)) {
					$img_info = getimagesize($tmp_img);
				}
				if ($img_info['mime'] !== 'image/jpeg' && $img_info['mime'] !== 'image/png' && $img_info['mime'] !== 'image/gif') {
					common()->message_error(t('File is not an image'));
				} else {
					_mkdir_m($this->AUTHOR_IMAGE_FOLDER . $author_id);

					require_php_lib('php_image');
					$image = new PHPImage($tmp_img);

					$x = $this->AUTHOR_IMAGE_XY['width'];
					$y = $this->AUTHOR_IMAGE_XY['height'];
					$image->resize($x, $y, 'C', $upscale = false);
					$dest_sm = $this->AUTHOR_IMAGE_FOLDER. $author_id.'/'.$author_id.$this->AUTHOR_IMAGE_EXT;
					$this->_upload_author_image($image, $dest_sm);
					unlink($tmp_img);
				}
			}
			common()->message_info('Author was added');
			return js_redirect('/@object/');
		})
		->text('name', ['desc' => 'Author name'])
		->text('url', ['desc' => 'URL'])
		->image('author_image',['desc' => t('Author image')])
		->save();
	}

	/**
	*/
	function edit() {

		$author_info = db()->get("SELECT * FROM `".db('blog_authors')."` WHERE `id`=".intval($_GET['id']));
		if (empty($author_info)) {
			common()->message_error("Author not found");
			return js_redirect('/@object/');
		}
		$author_id = $author_info['id'];


		$author_image_info = "";
		$file_name = $this->AUTHOR_IMAGE_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
		if (file_exists($file_name)) {
			$author_image_info = "<img src=\"".$this->AUTHOR_IMAGE_WEB_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT."\">";
		}

		return form((array)$_POST + $author_info, [
			'legend' => 'Edit author',
			'enctype' => 'multipart/form-data',
			'class' => 'form-vertical',
		])
		->validate([
			'name' => 'trim|required',
			'url' => ['trim|xss_clean', function($in, $tmp, $data, &$error_msg) use ($author_id) {
				if(preg_match('~^[a-z0-9\-_]*$~isu', $in) !== 1 ){
					$error_msg = t('Wrong url');
					return false;
				}
				$url_author_id = db()->get_one("SELECT `id` FROM `".db("blog_authors")."` WHERE `url`='"._es($in)."'");
				if($url_author_id !== false && $url_author_id !== $author_id ){
					$error_msg = t('Url already in use');
					return false;
				}
				return true;
			}],
		])
		->on_validate_ok(function() use ($author_id){
			if ($_POST['author_image_delete'] == 1) {
				$file_name = $this->AUTHOR_IMAGE_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
				if (file_exists($file_name)) {
					@unlink($file_name);
					common()->message_info(t('File was deleted'));
				}
			}

			db()->update_safe(self::table, [
				'name'			=> $_POST['name'],
				'url'			=> $_POST['url'],
			], "`id`=".intval($author_id));

			if (!empty($_FILES['author_image']) && !empty($_FILES['author_image']['name'])) {
				$file = $_FILES['author_image'];
				$tmp_img = $file['tmp_name'];
				if (file_exists($tmp_img)) {
					$img_info = getimagesize($tmp_img);
				}
				if ($img_info['mime'] !== 'image/jpeg' && $img_info['mime'] !== 'image/png' && $img_info['mime'] !== 'image/gif') {
					common()->message_error(t('File is not an image'));
				} else {
					_mkdir_m($this->AUTHOR_IMAGE_FOLDER . $author_id);

					require_php_lib('php_image');
					$image = new PHPImage($tmp_img);

					$x = $this->AUTHOR_IMAGE_XY['width'];
					$y = $this->AUTHOR_IMAGE_XY['height'];
					$image->resize($x, $y, 'C', $upscale = false);
					$dest_sm = $this->AUTHOR_IMAGE_FOLDER. $author_id.'/'.$author_id.$this->AUTHOR_IMAGE_EXT;
					$this->_upload_author_image($image, $dest_sm);
					unlink($tmp_img);
				}
			}
			common()->message_info('Author was edited');
			return js_redirect('/@object/');
		})
		->text('name', ['desc' => 'Author name'])
		->text('url', ['desc' => 'URL'])
		->container($author_image_info, ['desc' => t('Author image')])
		->image('author_image',' ')
		->save();
	}

	/**
	*/
	function delete() {
		$author_id = intval($_GET['id']);
		$author_info = db()->get("SELECT * FROM `".db('blog_authors')."` WHERE `id`=".$author_id);
		if (!empty($author_info['id'])) {
			db()->delete(self::table, $author_id);
			$file_name = $this->AUTHOR_IMAGE_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
			if (file_exists($file_name)) {
				@unlink($file_name);
			}
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $blog_id;
		} else {
			common()->message_info('Author was deleted');
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function browse_images() {
		return _class('manage_blog_browse_images', 'admin_modules/manage_blog/')->{__FUNCTION__}();
	}

	/**
	*/
	function upload_image() {
		return _class('manage_blog_upload_image', 'admin_modules/manage_blog/')->{__FUNCTION__}();
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], ['show'])) {
			return false;
		}

		return form($r, ['filter' => true])
			->number('id', ['style' => 'width:100px', 'no_label' => 1])
			->text('title', ['no_label' => 1])
			->save_and_clear();
		;
	}

	function _upload_author_image($image, $dest){
		$input = $image->getResource();
		$width = imagesx($input);
		$height = imagesy($input);
		$output = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($output,  255, 255, 255);
		imagefilledrectangle($output, 0, 0, $width, $height, $white);
		imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
		imagepng($output, $dest);
	}

}
