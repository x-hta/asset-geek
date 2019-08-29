<?php

class blog_handler {

    public $blog_table = 'blog';
    public $cats_table = 'category_items';
    public $object_cats_table = 'object_cats';

    public $blog_favorites = 'blog_favorites';
    public $related_blog_table = 'related_blog';
    public $blog_images_table = 'blog_images';

    public $blog_images_path = 'uploads/blog/';

    public $object_name = 'blog';
    public $related_blog_count = 3;

    public $blog_list_limit = 6;
    public $blog_show_limit = 9;

    public $news_home_page_limit = 2;
    public $blog_home_page_limit = 1;

    public $summary_length_preview = 60;

    public $PREVIEW_IMAGE_EXT = '.png';
    public $PREVIEW_IMAGE_XY = ['width'=>665, 'height'=>420];

    public $date_format_long = 'd.m.y H:i';
    public $date_format_local_short = 'd MMMM';
    public $date_format_local_long = 'd MMMM Y H:m';


    public $pattern = '~\<pre(.*?)class=\"(.*?)\>(.*?)</pre\>~isu';

    public $object_title = 'blog';

    public $pagination_pages_block_length = 4;
	public $SUBCAT_LIMIT = 4;

	public $AUTHOR_IMAGE_EXT = '.png';

	function _init(){
        $this->PREVIEW_IMAGE_FOLDER = PROJECT_PATH.'uploads/blog_previews/';
		$this->PREVIEW_IMAGE_WEB_FOLDER = WEB_PATH.'uploads/blog_previews/';
		$this->locales = conf('languages');

		$this->AUTHOR_IMAGE_BASE = 'uploads/blog_authors/';
		$this->AUTHOR_IMAGE_FOLDER = PROJECT_PATH.$this->AUTHOR_IMAGE_BASE;
		$this->AUTHOR_IMAGE_WEB_FOLDER = WEB_PATH.$this->AUTHOR_IMAGE_BASE;

	}

	function _get_data_by_cat($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(@$_cat_id < 1){
			return [];
		}
		$limit = @$_limit > 0 ? $_limit : $this->SUBCAT_LIMIT;
		$lang = @$_lang ?: conf('language');
		$data = db()->select("b.*")
            ->from($this->blog_table . ' as b, '._class('cats')->object_categories_table.' as c')
            ->where('b.lang', $lang)
            ->where('c.cat_id', $_cat_id)
            ->where('b.active', 1)
            ->where_raw('b.id = c.object_id')
			->order_by('b.publish_date','desc');
		if(@$_only_sql > 0){
			$data = $data->sql();
		}else{
			$data = $data->limit($limit)
			->get_all();
		}
		return $data;
	}

	function _prepare_data($blog = []){
		$lang = conf('language');
		$locale = $this->locales[$lang]['locale'] ?: '';
        if(!empty($blog)) {
            foreach ($blog as $blog_item) {
                $blog_id = $blog_item['id'];
                $add_date = $blog_item['publish_date'];
				$tags = module('tags')->_tags_urls($this->object_name, $blog_id);
				$tag_data = '';
				if($tags){
					foreach($tags as $k => $v){
						$tag_data .= ' | '.$v['name'];
					}
					$blog[$blog_id]['tags'] = '<span>'.$tag_data.'</span>';
				}
                $blog[$blog_id]['date'] = date($this->date_format_long, $add_date);
                $blog[$blog_id]['date_local_short'] = common()->_intl_date_format($this->date_format_local_short, $add_date, $locale);
                $blog[$blog_id]['date_local_long'] = common()->_intl_date_format($this->date_format_local_long, $add_date, $locale);
				$blog[$blog_id]['preview_image'] = $this->_preview_image($blog_id);
				$blog[$blog_id]['preview_image_sm'] = $this->_preview_image($blog_id, true);
                $blog[$blog_id]['item_url'] = $this->_view_url($blog_id, $blog_item);
                $blog[$blog_id]['title'] = _truncate($blog_item['title'], 49, false, true);
                if(!empty($blog_item['summary'])) {
                    $blog[$blog_id]['summary'] = _truncate($blog_item['summary'], 100, false, true);
                }
                else {
                    $blog[$blog_id]['summary'] = _truncate(strip_tags($blog_item['full_text']), 100, false, true);

                }
                if(!$tiny_info) {
                }
            }
        }
        return $blog;
	}

    function _view_url($blog_id, $blog_obj = false) {
        if($blog_obj != false && is_array($blog_obj) && !empty($blog_obj['category_url'])){
            return url_user('/'._strtolower($blog_obj['category_url']).'/'.trim($blog_obj['url']));
        }
        else {
            return url_user('?object=' . $this->object_name . '&action=view&id=' . $blog_id);
        }
    }

    function _preview_image($blog_id, $small = false) {
		return module('blog')->_preview_image($blog_id, $small);
    }

	function _get_blog_data($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$sql = _class('blog_handler')->_get_data_by_cat(['cat_id' => $_cat_id, 'only_sql' => 1, 'lang' => @$_lang]);
		list($add_sql, $pages, $total) = common()->divide_pages($sql,false,false,$_per_page,false,false,1,array('requested_page' => (int)$_curr_page));
		$data = db()->get_all($sql. $add_sql);
		$blog['blog_data'] = _class('blog_handler')->_prepare_data($data);

		$blog['total'] = $total;
		return $blog;
	}

	function _get_all_blogs($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(@count((array)$_cat_ids) < 1){
			return [];
		}
		$lang = @$_lang ?: conf('language');
		$data = db()->select("b.*")
            ->from($this->blog_table . ' as b, '._class('cats')->object_categories_table.' as c')
            ->where('b.lang', $lang)
            ->where_raw('c.cat_id IN ('.implode(',', $_cat_ids).')')
            ->where('b.active', 1)
            ->where_raw('b.id = c.object_id')
			->order_by('b.publish_date','desc');
		if(@$_only_sql > 0){
			$data = $data->sql();
		}else{
			$data = $data->limit($limit)
			->get_all();
		}
		return $data;
	}

    function _get_autor_avatar($author_id){
        $author_image_info = "";
        $file_name = $this->AUTHOR_IMAGE_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
        if (file_exists($file_name)) {
            $author_image_info = $this->AUTHOR_IMAGE_WEB_FOLDER . $author_id . '/' . $author_id . $this->AUTHOR_IMAGE_EXT;
        }
        return $author_image_info;
    }

    function _get_cats($object = null, $object_id = null) {
        $query = db()->select('c.id, c.name, c.url')
            ->from($this->cats_table.' as c');
        if(!empty($object)) {
            $object = _es($object);
            $object_id = intval($object_id);
            $query = $query
                ->left_join($this->object_cats_table.' as ot', 'c.id=ot.cat_id')
                ->where_raw('ot.object_id='.$object_id)
                ->where_raw('ot.object=\''. $object.'\'');
        }
//        echo $query->sql();exit;
        return $query->get_all();
    }
}
