<?php

class manage_news_edit {

	public $tags_box_id = 'chosen_box_tags';
	public $multiple_categories = 0;

	/**
	*/
	function _init() {
		main()->USE_SYSTEM_CACHE = false;
	}

	/**
	*/
	function edit() {
		$news_id = intval($_GET['id']);
		$news_class = module('news');
		$news_info = $news_class->_get_info($news_id, false, $no_cache = true);
		if (empty($news_info['id'])) {
			common()->message_info(t('News entry not found'));
			return js_redirect('./?object=manage_news', true, t('Wrong news id'));
		}
		$tags_class = module('tags');
		$tags_class->_chosen_tags_script($this->tags_box_id);
		$tags = $tags_class->_tags_control_data();
		$news_info['tag_id'] = $tags_class->_tags_ids($news_class->object_name,$news_info['id']);
		$file_name = $news_id.'/'.$news_id.$news_class->PREVIEW_IMAGE_EXT;
		$img_fs = $news_class->PREVIEW_IMAGE_FOLDER. $file_name;
		$img_web = $news_class->PREVIEW_IMAGE_WEB_FOLDER.$file_name.'?t='.$_SERVER['REQUEST_TIME'];
		$news_info['preview_image'] = file_exists($img_fs) ? '<img src="'.$img_web.'" style="max-height:200px">' : '';

		$news_info['back_link'] = url('/@object');

#		$h = _class('html5fw_bs3', 'classes/html5fw/');
#		$h->CLASS_LABEL = 'control-label col-md-2';
#		$h->CLASS_CONTROLS = 'controls col-md-offset-2';
#		$h->CLASS_CONTROLS_BUTTONS = 'controls col-md-offset-2';

		return form((array)$_POST + $news_info, [
				'enctype' => 'multipart/form-data',
				'legend' => t('Edit news entry'),
				'class' => 'form-vertical',
			])
			->validate([
				'title' => 'trim|xss_clean|required',
				'summary' => 'trim|xss_clean',
				'full_text' => 'trim|required',
				'url' => ['trim|xss_clean', function($in, $tmp, $data, &$error_msg) use ($news_class, $news_id) {
					if(preg_match('~^[a-z0-9\-_]*$~isu', $in) !== 1 ){
						$error_msg = t('Wrong url');
						return false;
					}
					$url_news_id = $news_class->_get_id_by_url($in);
					if($url_news_id !== false && $url_news_id !== $news_id ){
						$error_msg = t('Url already in use');
						return false;
					}
					return true;
				}],
			])
			->on_validate_ok(function () use($news_class, $tags_class) {
				$news_id = intval($_GET['id']);
				$url = !empty($_POST['url']) ? $_POST['url'] : $news_class->_name_to_url($_POST['title']);
				$tag_id_array = !empty($_POST['tag_id']) ? $_POST['tag_id'] : [];
				$category_id_array = !empty($_POST['category_id']) ?$_POST['category_id'] :[];
				if ($_POST['preview_image_delete'] == 1) {
					$file_name = $news_class->PREVIEW_IMAGE_FOLDER . $news_id . '/' . $news_id . $news_class->PREVIEW_IMAGE_EXT;
					if (file_exists($file_name)) {
						@unlink($file_name);
						common()->message_info(t('File was deleted'));
					}
				}
				if (!empty($_FILES['preview_image']) && !empty($_FILES['preview_image']['name'])) {
					$file = $_FILES['preview_image'];
					$tmp_img = $file['tmp_name'];
					if (file_exists($tmp_img)) {
						$img_info = getimagesize($tmp_img);
					}
					if ($img_info['mime'] !== 'image/jpeg' && $img_info['mime'] !== 'image/png' && $img_info['mime'] !== 'image/gif') {
						common()->message_error(t('File is not an image'));
					} else {
						_mkdir_m($news_class->PREVIEW_IMAGE_FOLDER . $news_id);
						$dest = $news_class->PREVIEW_IMAGE_FOLDER. $news_id.'/'.$news_id.$news_class->PREVIEW_IMAGE_EXT;

						require_php_lib('php_image');
						$image = new PHPImage($tmp_img);
						$this->_upload_preview_image($image, $dest);

						$x = $news_class->PREVIEW_IMAGE_XY['width'];
						$y = $news_class->PREVIEW_IMAGE_XY['height'];
						$image->resize($x, $y, false, $upscale = true);
						$dest_sm = $news_class->PREVIEW_IMAGE_FOLDER. $news_id.'/'.$news_id.'_sm'.$news_class->PREVIEW_IMAGE_EXT;
						$this->_upload_preview_image($image, $dest_sm);
						unlink($tmp_img);
					}
				}
				$related_items = $this->_related_items($news_id);
				db()->update_safe($news_class->news_table, [
					'title' => $_POST['title'],
					'summary' => $_POST['summary'],
					'full_text' => $_POST['full_text'],
					'page_title' => $_POST['page_title'],
					'page_heading' => $_POST['page_heading'],
					'meta_keywords' => $_POST['meta_keywords'],
					'lang' => $_POST['lang'],
					'meta_desc' => $_POST['meta_desc'],
					'related_items' => json_encode($related_items),
					'url' => trim($url),
					'active'=> intval($_POST['active']),
					'edit_date'=> $_SERVER['REQUEST_TIME'],
					'publish_date'=> strtotime($_POST['publish_date']),
					'author_id' => intval($_POST['author_id']),
				], 'id=' . $news_id);
				$tags_class->_set_tags($news_id, $tag_id_array, $news_class->object_name);
				$cats_class = _class('cats');

				$indexed_text = $this->_create_indexed_text($news_id);
				db()->update_safe($news_class->news_table, ['indexed_text' => $indexed_text], 'id=' . $news_id);
				common()->message_info(t('News entry was edited'));
				js_redirect('/@object/');
			})
			->row_start()
				->save_and_back()
			->row_end()
			->text('title', ['desc' => t('Title')])
			->textarea('summary')
			->textarea('full_text', ['ckeditor' => ['config' => $this->_get_cke_config()]])
			->container($news_info['preview_image'], ['desc' => t('Preview image')])
			->image('preview_image',' ')
#			->chosen_box('category_id', $cats, $categories_options)
			->chosen_box('tag_id', $tags, ['multiple' => 1,
				'no_translate' => 1,
				'js_options' => [
					'max_selected_options' => 50
				],
				'force_id' => $this->tags_box_id,
				'desc' => t('Tags')
			])
//			->locale_box('lang', ['edit_link' => 0])
			->datetime_select('publish_date', [
				'with_time'		=> true,
				'side_by_side'	=> true,
				'autocomplete'	=> 'on',
				'value'			=> $news_info['publish_date'],
			])
			->active_box()
			->text('url', ['desc'=>t('url')])
//			->text('meta_keywords')
//			->text('meta_desc')
//			->text('page_title')
//			->text('page_heading')
		;
	}

	/**
	* Return default config used by CKEditor
	*/
	function _get_cke_config($params = []) {
		$id = intval($_GET['id']);

		$config = _class('admin_methods')->_get_cke_config();
		$extra_plugins = explode(',', $config['extraPlugins']);
		foreach($extra_plugins as $key=>$value) {
			//disable autosave
			if($value == 'autosave'){
				unset($extra_plugins[$key]);
				break;
			}
		}
		$config['extraPlugins'] = implode(',',$extra_plugins);

		$config['filebrowserImageBrowseUrl'] = url('/@object/browse_images/'.$id);
#		$config['filebrowserImageUploadUrl'] = url('/@object/upload_image/'.$id);
		$config['filebrowserImageUploadUrl'] .= '&path='.urlencode('news/item_images');
		return $config;
	}

	/**
	*/
	function _related_items($id) {
		$id = intval($id);
		$news_class = module('news');
		$tags_class = module('tags');
		$cats_class = module('cats');
		$object_name = $news_class->object_name;
		$news_table = db($news_class->news_table);
		$object_tags_table = db($tags_class->object_tags_table);
		$object_categories_table = db($cats_class->object_categories_table);
		$limit = $news_class->related_news_count;
		$data = db()->query(
			"select a.id from $news_table as a
			left join $object_categories_table as oc on oc.object_id = a.id and oc.object = '$object_name'
			left join $object_tags_table as ot on ot.object_id = a.id and ot.object = '$object_name'
			where
				a.id != $id
				AND a.active = 1
				AND	(
					(oc.cat_id is not null and oc.cat_id in
						(
							select cat_id
							from $object_categories_table
							where object_id = $id and object = '$object_name'
						)
					)
					or
					(ot.tag_id is null or ot.tag_id in
						(
							select tag_id
							from $object_tags_table
							where object_id = $id and object = '$object_name'
						)
					)
				)
			group by a.id
			order by rand()
			limit $limit
			"
		);
		$related_ids = [];
		if(count((array)$data)>0) {
			foreach($data as $item) {
				$related_ids[] = $item['id'];
			}
		}
		return $related_ids;
	}

	/**
	*/
	function _create_indexed_text($news_id) {
		$news_class = module('news');
		$news_info = $news_class->_get_info($news_id, false, $no_cache = true);

		require_php_lib('phpmorphy');
		$normalize_text = _class('normalize_text');

		$values_array = [];
		$values_array[] = $news_info['title'];
		$values_array[] = strip_tags($news_info['full_text']);

		$cats_array =[];
		foreach($news_info['categories'] as $cat){
			$cats_array[] = $cat['name'];
		}
		$values_array[] = implode($normalize_text->separator, $cats_array);
		return  $normalize_text->normalize(implode($normalize_text->separator, $values_array));
	}

	function _upload_preview_image($image, $dest){
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
