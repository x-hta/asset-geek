<?php

class blog {
    public $blog_table = 'blog';

    public $blog_favorites = 'blog_favorites';
    public $related_blog_table = 'related_blog';
    public $blog_images_table = 'blog_images';

    public $blog_images_path = 'uploads/blog/';

    public $object_name = 'blog';
    public $blog_cat_id = 2;
    public $news_cat_id = 1;
    public $related_blog_count = 3;

    public $blog_list_limit = 6;
    public $blog_show_limit = 9;

    public $news_home_page_limit = 2;
    public $blog_home_page_limit = 1;

    public $summary_length_preview = 60;

    public $PREVIEW_IMAGE_EXT = '.png';
    public $PREVIEW_IMAGE_XY = ['width'=>370, 'height'=>400];

    public $date_format_long = 'd.m.y H:i';
    public $date_format_local_short = 'd MMMM';

    public $date_format_local_long = 'd MMMM Y H:mm';

    public $pattern = '~\<pre(.*?)class=\"(.*?)\>(.*?)</pre\>~isu';

    public $object_title = 'blog';

    public $pagination_pages_block_length = 4;


	function _site_title($title) {
		return $this->_my_site_title;
	}

    function _init() {
		header('Access-Control-Allow-Origin: *');

        $this->PREVIEW_IMAGE_FOLDER = PROJECT_PATH.'uploads/blog_previews/';
		$this->PREVIEW_IMAGE_WEB_FOLDER = WEB_PATH.'uploads/blog_previews/';

        if ($_GET['id'] == '0') $_GET['id'] = '';
        if (intval($_GET['id']) != 0 && $_GET['page'] == 1) {
            return js_redirect("./?object=blog&action=show&id=".$_GET['id']);
        }
        if ($_GET['object'] == 'archive' && $_GET['page'] == 1) {
            return js_redirect("./?object=archive");
        }
        $this->cats_class = _class('cats');
		$this->locales = conf('languages');
    }

    function _get_banner($item) {
        module('banners')->_log('show', $item);
        return [
            'url'   => $item['hash'] == '' ? '' : url_user("/banners/go/".$item['hash']),
            'desc'  => $item['desc'] == '' ? '' : $item['desc'],
            'src'   => $item['image'] == '' ? WEB_PATH."/templates/user/images/no_photo.jpg" : WEB_PATH .$item['image'],
        ];
    }

    function _set_global_info ($a = []) {
        $this->PAGE_HEADING	= _prepare_html(_ucfirst($a['page_heading']));
        $this->PAGE_TITLE	= _prepare_html(_ucfirst($a['title'] ? $a['title'] : $a['page_title']));
        $this->_my_site_title = $this->PAGE_TITLE . " : " . SITE_ADVERT_NAME;
        conf('meta_keywords', _prepare_html($a['meta_keywords']));
        conf('meta_description', _prepare_html($a['meta_desc']));
    }

    /**
     * Page header hook
     */
    function _show_header() {
        return [
            'header'	=> $this->PAGE_TITLE,
            'subheader'	=> $this->PAGE_HEADING,
        ];
    }

    /**
     * Meta tags injection
     */
    function _hook_meta($meta = []) {
        $a = $this->_current;
        if ($a) {
            $meta_desc = _truncate($a['meta_desc'], 250);
            $desc = _truncate(strip_tags($a['full_text']), 250);
            $url = $a['view_url'];
            if(preg_match('~(http\:|https\:)~', $a['preview_image'], $matches )==1){
                $preview_image = $a['preview_image'];
            }
            else {
                $host_protocol = explode(':', url('/'))[0] ?:'https';
                $preview_image = $host_protocol . ':' . $a['preview_image'];
            }
            $meta = [
                    'keywords'		=> $a['meta_keywords'] ?: $meta['keywords'],
                    'description'	=> $meta_desc ?: ($desc ?: $meta['description']),
                    'og:description'=> $meta_desc ?: ($desc ?: $meta['description']),
                    'og:title'		=> $a['title'],
                    'og:type'		=> 'article',
                    'twitter:card'  => 'summary',
                    'twitter:site'  => '@assetgeekcom',
                    'twitter:title' => $a['title'],
                    'twitter:description' => $meta_desc ?: ($desc ?: $meta['description']),
                    'twitter:image' => $preview_image,
                    'og:url'		=> $url,
                    'og:site_name'	=> SITE_ADVERT_NAME,
                    'og:image'	    => $preview_image,
                    'canonical'		=> $url,
                    'article:published_time' => date('Y-m-d', $a['publish_date']),
                ] + (array)$meta;
        }
        return $meta;
    }

    /**
     * Meta page title injection
     */
    function _hook_title($title) {
        $a = $this->_current;
        if ($a) {
			if($a['category_info']['id'] > 1){
				return $a['page_title'] ? $a['page_title'] .' :: '.t('blog') : $a['title'] ? $a['title'].' :: '.t('blog'): $title .' :: '.t('blog');
			}
            return $a['page_title'] ?: $a['title'] ?: $title;
        }
		if($_GET['cat_id'] > 1){
			return t('Blog');
		}
        return $title;
    }

    /**
     * Hook for the site_map
     */
    function _hook_sitemap($sitemap = false) {
        if (!is_object($sitemap)) {
            return false;
        }
		$sql = select('b.*')
			->from($this->blog_table.' as b, '.$this->cats_class->object_categories_table.' as oc, '.$this->cats_class->categories_items_table.' as c')
			->where_raw('(c.url IN("blog","news") or c.parent_id='.$this->blog_cat_id.') AND oc.object = "blog" AND b.active = 1 AND b.id = oc.object_id AND oc.cat_id = c.id')
			->order_by('b.publish_date','desc');
        $items = $sql->get_2d('b.id, b.publish_date');
        foreach($items as $id => $add_date) {
            $url = $this->_view_url($id);
            $sitemap->_add($url);
        }
        return true;
    }

    function _name_to_url($name) {
        return str_replace("_", "-", common()->_propose_url_from_name($name));
    }

    function _get_info($blog_id, $tiny_info = false, $no_cache = false) {
		$blog_id = intval($blog_id);
		if(!$blog_id){
			return false;
		}
		return getset('blog_info_by_id_'.$blog_id, function() use ($blog_id) {
			$data = db()
				->select('b.*')
				->from($this->blog_table.' as b')
				->where('b.id', $blog_id)
				->get();

			if (empty($data['id'])) {
				return [];
			}
			if(!$tiny_info) {
				$tags_class = module('tags');
				$data['tags'] = $tags_class->_tags_urls($this->object_name, $data['id']);
				$data['categories'] = $this->cats_class->_get_categories_for_object($blog_id, $this->cats_class->blog_category, $this->object_name);
				$data['category_id'] = $this->cats_class->_get_categories_ids($blog_id, $this->cats_class->blog_category, $this->object_name);
				$data['category_info'] = $this->cats_class->_get_info(reset($data['category_id']));
				$data['category_desc'] = t($data['category_info']['desc']);
				$data['category_url'] = url_user('/'.$data['category_info']['url']);
                $data['view_url'] = $this->_view_url($data['id'], $data);
			}
			return $data;
		}, 0, ['no_cache' => $no_cache]);
    }

    function _preview_image($blog_id, $small = false) {
        $blog_id = intval($blog_id);
        $file_name_sm = $blog_id."/".$blog_id.'_sm'.$this->PREVIEW_IMAGE_EXT;
        $file_name = $blog_id."/".$blog_id.$this->PREVIEW_IMAGE_EXT;
        if($small){
            $result = file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name_sm) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name_sm :  (file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name : '');
        }
        else{
            $result = file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name : '';
        }
        return $result;
    }

    function _blog_list_query() {
		$lang = conf('language');
        return db()->select("b.*")
            ->from($this->blog_table.' as b')
            ->where('b.active', 1)
            ->where('b.lang', $lang)
            ->order_by('b.publish_date','desc');
    }

    function _blog_list($category = false, $limit = false, $item_class = '', $template = '', $tiny_info = false) {
        if($category) {
			$lang = conf('language');
            $query = db()->select("b.*, c.url as category_url, c.parent_id")
                ->from($this->blog_table.' as b, '.$this->cats_class->object_categories_table.' as oc, '.$this->cats_class->categories_items_table.' as c')
				->where('b.active', 1)
				->where('oc.object', $this->object_name)
				->where('b.lang', $lang)
                ->where_raw('b.id = oc.object_id AND oc.cat_id = c.id')
				->order_by('b.publish_date','desc');
			if($category == 'news'){
				$query = $query->where('c.url', _es($category));
			}else{
				$cats = _class('cats')->_get_recursive_cat_ids(2);
				if($cats){
					$query = $query->where_raw('oc.cat_id IN ('.implode(',', $cats).')');
				}
			}
        }
        else {
            $query = $this->_blog_list_query();
        }

        if($limit){
            $query->limit($limit);
        }
        else{
            $query->limit($this->blog_list_limit);
        }
        $blog = $query->get_all();
        $blog = $this->_add_for_preview($blog, $tiny_info);
        if(empty($template)) {
            $template = 'blog/blog_list';
        }
        return tpl()->parse($template, ['blog'=>$blog, 'item_class'=>$item_class]);
    }

    function _get_id_by_url($url, $cat_id = false) {
        $id = common()->_get_id_by_url($url, $this->blog_table);
        if($id) {
            if($cat_id !== false){
                $blog_info = $this->_get_info($id);
                if(in_array($cat_id, $blog_info['category_id'])){
                    return $id;
                }
            }
        }
        return false;
    }

    function _view_url($blog_id, $blog_obj = false) {
		if($blog_obj != false && is_array($blog_obj) && !empty($blog_obj['category_info']['url'])){
            $blog_url = $blog_obj['url'];
            $category_url = $blog_obj['category_info']['url'];
            $parent_id = $blog_obj['category_info']['parent_id'];
            $url = url('?object=' . $this->object_name . '&action=view&id=' . $blog_id.'&category_url='.$category_url.'&url='.$blog_url.'&parent_id='.$parent_id.'&lang='.$blog_obj['lang']);
        }
        else {
            $url = url('?object=' . $this->object_name . '&action=view&id=' . $blog_id);
        }
        return $url;
    }

	public function show()
    {
		echo json_encode(['ping' => 'OK']);
		exit;
    }

	/* ---------------------------------------------------------------------------------------------------------------------------- */

	function get_categories() {
		$items = db()->get_all("SELECT `url`,`name` FROM `".db('category_items')."` WHERE `cat_id`=1 AND `active`=1");
		echo json_encode($items);
		exit;
	}

    function get_blogs_list($limit = 0) {
        $items = db()->select('b.`id`,b.`url`,b.`title`,b.`summary`,ba.`name` as author_name,`ba`.`url` as author_url,b.author_id,b.`add_date`,b.`publish_date`')
            ->from(db('blog'). ' as b')
            ->join(db('blog_authors').' as ba', 'ba.id = b.author_id');
        if(isset($_GET['tag']) && !empty($_GET['tag'])){
            $tag_url = _es($_GET['tag']);
			$tag_data = db()->get("SELECT `url`,`id` FROM `".db('tags')."` WHERE `url`='".$tag_url."'");
			if (!empty($tag_data)) {
				$tag_id = $tag_data['id'];
	            $items = $items->join(db('object_tags').' as t', 't.object_id = b.id')
	                ->where('t.object', 'blog')
	                ->where('t.tag_id', $tag_id);
			}
        }

        if(isset($_GET['cat']) && !empty($_GET['cat'])){
			$items_cats = db()->get_2d("SELECT `url`,`id` FROM `".db('category_items')."` WHERE `cat_id`=1 AND `active`=1");
			if (!empty($items_cats[$_GET['cat']])) {
				$cat_id = $items_cats[$_GET['cat']];
				$items = $items->join(db('object_cats').' as oc', 'oc.object_id = b.id')
	                ->where('oc.object', 'blog')
	                ->where('oc.cat_id', intval($cat_id));
			}
        }
        if(isset($_GET['author']) && !empty($_GET['author'])){
			$author_url = $_GET['author'];
            $author_data = db()->get("SELECT `url`,`id` FROM `".db('blog_authors')."` WHERE `url`='".$author_url."'");
			if (!empty($author_data)) {
				$author_id = $author_data['id'];
            	$items = $items->where('author_id', intval($author_id));
			}
        }
        $items = $items->where('active', 1);
        if($limit > 0){
            $items = $items->limit(intval($limit));
        }
        $items = $items->order_by('b.id desc')
            ->get_all();
        $ret = [];
        if(!empty($items)){
            foreach((array)$items as $k => $v){
                $ret[$k] = $v;
                $ret[$k]['preview_img'] = $this->_preview_image($k);
                $ret[$k]['avatar_url'] = _class('blog_handler')->_get_autor_avatar($v['author_id']);
                $ret[$k]['tags'] = module('tags')->_get_tags('blog', $k);
				$cats = _class('blog_handler')->_get_cats('blog', $k);
				foreach ($cats as $cat) {
					$ret[$k]['cat'] = $cat;
					break;
				}
            }
        }
		echo json_encode($ret);
		exit;
	}

	function get_blogs_for_home() {
        $items = $this->get_blogs_list(3);
		echo json_encode($items);
		exit;
	}

	function get_blog_item() {
		list ($id, $url) = explode("_",$_GET['url']);
        $item = db()->get("
            SELECT b.*, ba.`name` as author_name,`ba`.`url` as author_url
            FROM `".db('blog')."` as b
            JOIN `".db('blog_authors')."` as ba ON (ba.id = b.author_id)
            WHERE b.`id`=".intval($id)
        );
		if (!empty($item)) {
	        $item['preview_img'] = $this->_preview_image($item['id']);
	        $item['avatar_url'] = _class('blog_handler')->_get_autor_avatar($item['author_id']);
	        $item['tags'] = module('tags')->_get_tags('blog', $item['id']);

			$cats = _class('blog_handler')->_get_cats('blog', $item['id']);
			foreach ($cats as $cat) {
				$item['cat'] = $cat;
				break;
			}
			echo json_encode($item);
		} else {
			echo json_encode(['error' => 'not_found']);
		}
		exit;
	}

}
