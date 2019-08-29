<?php

class news {
    public $news_table = 'news';

    public $news_favorites = 'news_favorites';
    public $related_news_table = 'related_news';
    public $news_images_table = 'news_images';

    public $news_images_path = 'uploads/news/';

    public $object_name = 'news';
    public $related_news_count = 3;

    public $news_list_limit = 6;
    public $news_show_limit = 9;

    public $summary_length_preview = 60;

    public $PREVIEW_IMAGE_EXT = '.png';
    public $PREVIEW_IMAGE_XY = ['width'=>370, 'height'=>400];

    public $date_format_long = 'd.m.y H:i';
    public $date_format_local_short = 'd MMMM';

    public $date_format_local_long = 'd MMMM Y H:mm';

    public $pattern = '~\<pre(.*?)class=\"(.*?)\>(.*?)</pre\>~isu';

    public $object_title = 'news';

    public $pagination_pages_block_length = 4;


	function _site_title($title) {
		return $this->_my_site_title;
	}

    function _init() {
		header('Access-Control-Allow-Origin: *');

        $this->PREVIEW_IMAGE_FOLDER = PROJECT_PATH.'uploads/news_previews/';
		$this->PREVIEW_IMAGE_WEB_FOLDER = WEB_PATH.'uploads/news_previews/';

        if ($_GET['id'] == '0') $_GET['id'] = '';
        if (intval($_GET['id']) != 0 && $_GET['page'] == 1) {
            return js_redirect("./?object=news&action=show&id=".$_GET['id']);
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
				return $a['page_title'] ? $a['page_title'] .' :: '.t('news') : $a['title'] ? $a['title'].' :: '.t('news'): $title .' :: '.t('news');
			}
            return $a['page_title'] ?: $a['title'] ?: $title;
        }
		if($_GET['cat_id'] > 1){
			return t('news');
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
			->from($this->news_table.' as b, '.$this->cats_class->object_categories_table.' as oc, '.$this->cats_class->categories_items_table.' as c')
			->where_raw('(c.url IN("news","news") or c.parent_id='.$this->news_cat_id.') AND oc.object = "news" AND b.active = 1 AND b.id = oc.object_id AND oc.cat_id = c.id')
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

    function _get_info($news_id, $tiny_info = false, $no_cache = false) {
		$news_id = intval($news_id);
		if(!$news_id){
			return false;
		}
		return getset('news_info_by_id_'.$news_id, function() use ($news_id) {
			$data = db()
				->select('b.*')
				->from($this->news_table.' as b')
				->where('b.id', $news_id)
				->get();

			if (empty($data['id'])) {
				return [];
			}
			if(!$tiny_info) {
				$tags_class = module('tags');
				$data['tags'] = $tags_class->_tags_urls($this->object_name, $data['id']);
				$data['categories'] = $this->cats_class->_get_categories_for_object($news_id, $this->cats_class->news_category, $this->object_name);
				$data['category_id'] = $this->cats_class->_get_categories_ids($news_id, $this->cats_class->news_category, $this->object_name);
				$data['category_info'] = $this->cats_class->_get_info(reset($data['category_id']));
				$data['category_desc'] = t($data['category_info']['desc']);
				$data['category_url'] = url_user('/'.$data['category_info']['url']);
                $data['view_url'] = $this->_view_url($data['id'], $data);
			}
			return $data;
		}, 0, ['no_cache' => $no_cache]);
    }

    function _preview_image($news_id, $small = false) {
        $news_id = intval($news_id);
        $file_name_sm = $news_id."/".$news_id.'_sm'.$this->PREVIEW_IMAGE_EXT;
        $file_name = $news_id."/".$news_id.$this->PREVIEW_IMAGE_EXT;
        if($small){
            $result = file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name_sm) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name_sm :  (file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name : '');
        }
        else{
            $result = file_exists($this->PREVIEW_IMAGE_FOLDER.$file_name) ? $this->PREVIEW_IMAGE_WEB_FOLDER.$file_name : '';
        }
        return $result;
    }

    function _news_list_query() {
		$lang = conf('language');
        return db()->select("b.*")
            ->from($this->news_table.' as b')
            ->where('b.active', 1)
            ->where('b.lang', $lang)
            ->order_by('b.publish_date','desc');
    }

    function _news_list($category = false, $limit = false, $item_class = '', $template = '', $tiny_info = false) {
        if($category) {
			$lang = conf('language');
            $query = db()->select("b.*, c.url as category_url, c.parent_id")
                ->from($this->news_table.' as b, '.$this->cats_class->object_categories_table.' as oc, '.$this->cats_class->categories_items_table.' as c')
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
            $query = $this->_news_list_query();
        }

        if($limit){
            $query->limit($limit);
        }
        else{
            $query->limit($this->news_list_limit);
        }
        $news = $query->get_all();
        $news = $this->_add_for_preview($news, $tiny_info);
        if(empty($template)) {
            $template = 'news/news_list';
        }
        return tpl()->parse($template, ['news'=>$news, 'item_class'=>$item_class]);
    }

    function _get_id_by_url($url, $cat_id = false) {
        $id = common()->_get_id_by_url($url, $this->news_table);
        if($id) {
            if($cat_id !== false){
                $news_info = $this->_get_info($id);
                if(in_array($cat_id, $news_info['category_id'])){
                    return $id;
                }
            }
        }
        return false;
    }

    function _view_url($news_id, $news_obj = false) {
		if($news_obj != false && is_array($news_obj) && !empty($news_obj['category_info']['url'])){
            $news_url = $news_obj['url'];
            $category_url = $news_obj['category_info']['url'];
            $parent_id = $news_obj['category_info']['parent_id'];
            $url = url('?object=' . $this->object_name . '&action=view&id=' . $news_id.'&category_url='.$category_url.'&url='.$news_url.'&parent_id='.$parent_id.'&lang='.$news_obj['lang']);
        }
        else {
            $url = url('?object=' . $this->object_name . '&action=view&id=' . $news_id);
        }
        return $url;
    }

	public function show()
    {
		echo json_encode(['ping' => 'OK']);
		exit;
    }
	/* ---------------------------------------------------------------------------------------------------------------------------- */

	function get_news_list() {
		$tags_sql = "";
		$tags_sql_where = "";
		if(isset($_GET['tag']) && !empty($_GET['tag'])){
            $tag_url = _es($_GET['tag']);
			$tag_data = db()->get("SELECT `url`,`id` FROM `".db('tags')."` WHERE `url`='".$tag_url."'");
			if (!empty($tag_data)) {
				$tag_id = $tag_data['id'];
				$tags_sql = " JOIN db('object_tags').' as t ON t.object_id = b.id";
				$tags_sql_where = " AND t.object='news' AND t.tag_id=".intval($tag_id);
			}
        }
        $items = db()->get_all("SELECT `id`,`url`,`title`,`summary`,`publish_date` FROM `".db('news')."` ".$tags_sql." WHERE `active`='1' ".$tags_sql_where." ORDER BY `id` DESC");
        if(!empty($items)){
            foreach((array)$items as $k => $v){
                $items[$k]['preview_img'] = $this->_preview_image($k);
                $items[$k]['tags'] = module('tags')->_get_tags('news', $k);
            }
        }
		echo json_encode($items);
		exit;
	}

	function get_news_for_home() {
		$items = db()->get_all("SELECT `id`,`url`,`title`,`summary`,`publish_date` FROM `".db('news')."` WHERE `active`='1' ORDER BY `id` DESC LIMIT 3");
        if(!empty($items)){
            foreach((array)$items as $k => $v){
                $items[$k]['preview_img'] = $this->_preview_image($k);
                $items[$k]['tags'] = module('tags')->_get_tags('news', $k);
            }
        }
		echo json_encode($items);
		exit;
	}

	function get_news_item() {
		list ($id, $url) = explode("_",$_GET['url']);
		$item = db()->get("SELECT * FROM `".db('news')."` WHERE `id`=".intval($id)." AND `active`=1");
		if (!empty($item)) {
	        $item['preview_img'] = $this->_preview_image($item['id']);
	        $item['tags'] = module('tags')->_get_tags('news', $item['id']);
			echo json_encode($item);
		} else {
			echo json_encode(['error' => 'not_found']);
		}
		exit;
	}


}
