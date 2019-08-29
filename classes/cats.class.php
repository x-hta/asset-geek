<?php

load('cats', '', 'classes/');
class cats extends yf_cats{

    public $object_name = 'categories';
    public $categories_table = 'sys_categories';
    public $categories_items_table = 'sys_category_items';
    public $object_categories_table = 'object_cats';
    public $platforms_category = 'platforms';
    public $blog_category = 'blog_cats';
    public $categories_previews_folder = 'categories_previews/';
    public $PREVIEW_IMAGE_EXT = '.svg';
    public $PREVIEW_IMAGE_EXT_ALTERNATIVE = '.png';
    public $PREVIEW_IMAGE_XY = ['width'=>200, 'height'=>200];
    
    function _get_info($cat_id){
		return getset(__CLASS__.'__'.__FUNCTION__.'__'.$cat_id, function() use ($cat_id) {
	        $cat_id = intval($cat_id);
        	$cat_info = select('ci.*, c.name as category_type')
	            ->from($this->categories_items_table.' as ci')
            	->left_join($this->categories_table.' as c', 'c.id=ci.cat_id')
        	    ->where('ci.id', $cat_id)
    	        ->get();
	        return $cat_info;
		});
    }

    function _get_categories_ids($object_id, $category_type, $object) {
        $data = db()->select('oc.cat_id')
            ->from($this->object_categories_table.' as oc')
            ->left_join($this->categories_items_table.' as ci', 'ci.id=oc.cat_id' )
            ->left_join($this->categories_table.' as c', 'c.id=ci.cat_id' )
            ->where('oc.object_id', $object_id)
            ->where('oc.object', $object)
            ->where('c.name', $category_type)
            ->order_by('ci.name')
            ->get_all();
        $result = [];
        if(count((array)$data)>0) {
            foreach ($data as $item) {
                $cat_id = intval($item['cat_id']);
                $result[$cat_id] = $cat_id;
            }
        }
        return $result;
    }

    function _get_categories_for_object($object_id, $category_type, $object) {
        $data = db()->select('ci.id, ci.name')
            ->from($this->object_categories_table.' as oc')
            ->left_join($this->categories_items_table.' as ci', 'ci.id=oc.cat_id' )
            ->left_join($this->categories_table.' as c', 'c.id=ci.cat_id' )
            ->where('oc.object_id', $object_id)
            ->where('oc.object', $object)
            ->where('c.name', $category_type)
            ->order_by('ci.name')
            ->get_all();
        if(empty($data)) {
            return false;
        }
        else {
            return $data;
        }

    }

    function _get_categories_str_for_object($id, $category_type, $object){
        if(!empty($category_type) && !empty($object)) {
            $platforms = $this->_get_categories_for_object($id, $category_type, $object);
            $platforms_array = [];
            foreach ((array)$platforms as $item) {
                $platforms_array[] = $item['name'];
            }
            return implode($platforms_array, ', ');
        }
    }

    function _set_categories($object_id, $categories_ids, $category_type, $object_name) {
        if(!is_array($categories_ids)) {
            $categories_ids = [$categories_ids];
        }
        $object_id = intval($object_id);
        $all_categories = $this-> _get_items_for_box($category_type);
        $current_categories_ids = $this->_get_categories_ids($object_id, $category_type, $object_name);
        foreach($current_categories_ids as $cat_id) {
            $cat_id = intval($cat_id);
            if(array_key_exists($cat_id, $all_categories) && !(in_array($cat_id, $categories_ids))) {
                //delete category for object_name
                db()
                    ->from($this->object_categories_table)
                    ->where('object_id', $object_id)
                    ->where('object', $object_name)
                    ->where('cat_id', $cat_id)
                    ->delete();
            }
        }
        foreach($categories_ids as $cat_id) {
            $cat_id = intval($cat_id);
            if(array_key_exists($cat_id, $all_categories) && !(in_array($cat_id, $current_categories_ids))) {
                //add category for object_name
                db()->insert_safe($this->object_categories_table, [
                    'object_id' =>$object_id,
                    'object'=>$object_name,
                    'cat_id' =>$cat_id,
                    'add_time'=>date(MYSQL_TIME_FORMAT)
                ]);
            }
        }
    }

    function _get_categories_data($category_type){
        $categories = db()
            ->select('ci.id, ci.name, ci.desc')
            ->from($this->categories_items_table.' as ci')
            ->left_join($this->categories_table.' as c', 'c.id=ci.cat_id')
            ->where('c.name', $category_type)
            ->order_by('ci.name')
            ->get_all();
        return $this->_prepare_categories_data($categories, $category_type);
    }

    function _view_url($cat_id) {
        return url('?object='.$this->object_name.'&action=view&id=' . $cat_id);
    }

    function _prepare_categories_data($categories){
        foreach($categories as $key => $value) {
            $categories[$key]['item_url']=$this->_view_url($value['id']);
            $categories[$key]['img_url']=$this->_get_preview_image_url($value['id']);
        }
        if(empty($categories) || count((array)$categories) ==0) {
            $categories = '';
        }
        return $categories;
    }

	function _get_category_by_url($url, $category_type) {
        $cat_info = db()
            ->select('ci.*, c.name as category_type')
            ->from($this->categories_items_table.' as ci')
            ->left_join($this->categories_table.' as c', 'c.id=ci.cat_id')
            ->where('ci.url', _es($url))
            ->where('c.name', $category_type)
            ->get();
        if(empty($cat_info['id'])){
            return false;
        }
        else {
            return $cat_info;
        }
    }

    function _preview_image_folder(){
        return PROJECT_PATH.'uploads/'.$this->categories_previews_folder;
    }
    function _preview_image_web_folder(){
        return WEB_PATH.'uploads/'.$this->categories_previews_folder;
    }

    function _file_name($cat_id, $is_alternative){
        if($is_alternative){
            $ext = $this->PREVIEW_IMAGE_EXT_ALTERNATIVE;
        }
        else {
            $ext = $this->PREVIEW_IMAGE_EXT;
        }
        return $cat_id."/".$cat_id.$ext;
    }

    function _preview_image_path($cat_id, $is_alternative = false) {

        return $this->_preview_image_folder().$this->_file_name($cat_id, $is_alternative);
    }

    function _preview_image_web_path($cat_id, $is_alternative = false) {
        return $this->_preview_image_web_folder().$this->_file_name($cat_id, $is_alternative);
    }

    function _get_preview_image_url($cat_id) {
        $result = '';
        if(file_exists($this->_preview_image_path($cat_id))){
            $result = $this->_preview_image_web_path($cat_id);
        }
        if(file_exists($this->_preview_image_path($cat_id, true))){
            $result = $this->_preview_image_web_path($cat_id, true);
        }
        return $result;
    }
}
