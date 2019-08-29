<?php

class news_handler {

    public $news_table = 'news';

    public $news_favorites = 'news_favorites';
    public $related_news_table = 'related_news';
    public $news_images_table = 'news_images';

    public $news_images_path = 'uploads/news/';

    public $object_name = 'news';
    public $related_news_count = 3;

    public $news_list_limit = 6;
    public $news_show_limit = 9;

    public $news_home_page_limit = 2;
    public $news_home_page_limit = 1;

    public $summary_length_preview = 60;

    public $PREVIEW_IMAGE_EXT = '.png';
    public $PREVIEW_IMAGE_XY = ['width'=>665, 'height'=>420];

    public $date_format_long = 'd.m.y H:i';
    public $date_format_local_short = 'd MMMM';
    public $date_format_local_long = 'd MMMM Y H:m';


    public $pattern = '~\<pre(.*?)class=\"(.*?)\>(.*?)</pre\>~isu';

    public $object_title = 'news';

    public $pagination_pages_block_length = 4;
	public $SUBCAT_LIMIT = 4;

	function _init(){
        $this->PREVIEW_IMAGE_FOLDER = PROJECT_PATH.'uploads/news_previews/';
		$this->PREVIEW_IMAGE_WEB_FOLDER = WEB_PATH.'uploads/news_previews/';
		$this->locales = conf('languages');
	}

	function _get_data_by_cat($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(@$_cat_id < 1){
			return [];
		}
		$limit = @$_limit > 0 ? $_limit : $this->SUBCAT_LIMIT;
		$lang = @$_lang ?: conf('language');
		$data = db()->select("b.*")
            ->from($this->news_table . ' as b, '._class('cats')->object_categories_table.' as c')
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

	function _prepare_data($news = []){
		$lang = conf('language');
		$locale = $this->locales[$lang]['locale'] ?: '';
        if(!empty($news)) {
            foreach ($news as $news_item) {
                $news_id = $news_item['id'];
                $add_date = $news_item['publish_date'];
				$tags = module('tags')->_tags_urls($this->object_name, $news_id);
				$tag_data = '';
				if($tags){
					foreach($tags as $k => $v){
						$tag_data .= ' | '.$v['name'];
					}
					$news[$news_id]['tags'] = '<span>'.$tag_data.'</span>';
				}
                $news[$news_id]['date'] = date($this->date_format_long, $add_date);
                $news[$news_id]['date_local_short'] = common()->_intl_date_format($this->date_format_local_short, $add_date, $locale);
                $news[$news_id]['date_local_long'] = common()->_intl_date_format($this->date_format_local_long, $add_date, $locale);
				$news[$news_id]['preview_image'] = $this->_preview_image($news_id);
				$news[$news_id]['preview_image_sm'] = $this->_preview_image($news_id, true);
                $news[$news_id]['item_url'] = $this->_view_url($news_id, $news_item);
                $news[$news_id]['title'] = _truncate($news_item['title'], 49, false, true);
                if(!empty($news_item['summary'])) {
                    $news[$news_id]['summary'] = _truncate($news_item['summary'], 100, false, true);
                }
                else {
                    $news[$news_id]['summary'] = _truncate(strip_tags($news_item['full_text']), 100, false, true);

                }
                if(!$tiny_info) {
                }
            }
        }
        return $news;
	}

    function _view_url($news_id, $news_obj = false) {
        if($news_obj != false && is_array($news_obj) && !empty($news_obj['category_url'])){
            return url_user('/'._strtolower($news_obj['category_url']).'/'.trim($news_obj['url']));
        }
        else {
            return url_user('?object=' . $this->object_name . '&action=view&id=' . $news_id);
        }
    }

    function _preview_image($news_id, $small = false) {
		return module('news')->_preview_image($news_id, $small);
    }

	function _get_news_data($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$sql = _class('news_handler')->_get_data_by_cat(['cat_id' => $_cat_id, 'only_sql' => 1, 'lang' => @$_lang]);
		list($add_sql, $pages, $total) = common()->divide_pages($sql,false,false,$_per_page,false,false,1,array('requested_page' => (int)$_curr_page));
		$data = db()->get_all($sql. $add_sql);
		$news['news_data'] = _class('news_handler')->_prepare_data($data);

		$news['total'] = $total;
		return $news;
	}

	function _get_all_newss($options = null){
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(@count((array)$_cat_ids) < 1){
			return [];
		}
		$lang = @$_lang ?: conf('language');
		$data = db()->select("b.*")
            ->from($this->news_table . ' as b, '._class('cats')->object_categories_table.' as c')
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
}
