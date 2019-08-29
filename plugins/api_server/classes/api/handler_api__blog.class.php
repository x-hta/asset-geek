<?php

class handler_api__blog extends api_handler__base {

	function get_data( $raw = null){
		$options = $this->get($raw,['curr_page','cat_id', 'per_page']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(@$_cat_id < 1 ){
			return $this->result( array(
				'data'   => false,
				'status' => -1,
			));
		}
		$lang = $this->language_id;
		$per_page = @$_per_page > 0 ? intval($_per_page) : module('blog_next')->PER_PAGE;
		$_curr_page = @$_curr_page > 1 ? $_curr_page : 1 ;
		$blog = _class('blog_handler')->_get_blog_data(['cat_id' => $_cat_id, 'lang' => $lang, 'per_page' => $per_page, 'curr_page' => $_curr_page]);

		return $this->result( array(
			'data' => $blog,
		));
	}

	function get_cats( $raw = null){
		$blog_cats = getset('blog_cats', function() {
			$existings_cats = _class('category')->get_exist_cats_2d();
			$cats = _class('cats')->_get_recursive_cat_ids(2);
			foreach($cats as $id){
				$cats_data['cats'][$id] = $existings_cats[$id];
			}
			return $cats_data;
		});
		return $this->result( array(
			'data' => $blog_cats,
		));
	}
}
