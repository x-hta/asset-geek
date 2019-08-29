<?php

class tags {
    public $tags_table = 'tags';
    public $object_tags_table = 'object_tags';
    public $items_view_limit = 9;


    function _get_info($tag_id) {
        $tag_id = intval($tag_id);
        $data = db()
            ->select('t.*')
            ->from($this->tags_table.' as t')
            ->where('t.id', $tag_id)
            ->get();

        if(empty($data['id'])) {
            return false;
        }
        return $data;
    }

	function view() {
        $blog_class = module('blog');
        $tag_id = intval($_GET['tag_id']);
		$tag_info = $this->_get_info($tag_id);
        $items_list = '';
        $pages = '';
		$tag_name = '';
		$cur_page = (int)$_GET['page'];
		if($cur_page == 0){
			$cur_page = 1;
		}
        if(!empty($tag_info['id'])) {
            $tag_name = $tag_info['name'];
            $query = db()
                ->select('b.*, ci.name as cat_name')
				->from($this->object_tags_table . ' as ot')
				->left_join($blog_class->blog_table . ' as b' ,'ot.object_id = b.id')
				->left_join('object_cats as oc' ,'oc.object_id = ot.object_id')
				->left_join('sys_category_items as ci ' ,'ci.id = oc.cat_id')
                ->where_raw('ot.tag_id = ' . $tag_id . '
                         and ot.object = \'' . $blog_class->object_name . '\'')
                ->order_by('b.add_date', 'desc')
                ->sql();
			list($add_sql, $pages, $total) = common()->divide_pages($query, null, null, $this->items_view_limit);
 //																   ($sql, $url_path, $render_type, $records_on_page = 0, $num_records = 0, $tpls_path = '', $add_get_vars = 1, $extra = [])
            $blog = db()->get_all($query . $add_sql);
			$blog = $blog_class->_add_for_preview($blog);
			$total_pages = $total > $blog_class->blog_show_limit ? intval(ceil($total/$blog_class->blog_show_limit)) : 1;
			$pages = $blog_class->_pagination($cur_page, $total_pages, false, url_user('/tags/'.$tag_info['url']));
            $items_list = tpl()->parse('blog_next/new_blog_list', ['blog' => $blog]);
        }
        return tpl()->parse('tags/view', ['items_list' => $items_list, 'pages' => $pages, 'tag_name' => $tag_name]);
    }

    function _add_tag($tag_name) {
        $tag_url = $this->_name_to_url($tag_name);
        $tag_id = $this->_get_id_by_url($tag_url);
        if($tag_id === false) {
            db()->insert_safe($this->tags_table, [
                'url' => $tag_url,
                'name' => $tag_name,
                'add_time' => date(MYSQL_TIME_FORMAT),
            ]);
            $tag_id = db()->insert_id();
        }
        if($tag_id>0) {
            return $tag_id;
        }
        else {
            return false;
        }
    }

    function _set_tags($object_id, $tags_ids, $object_name) {
        $object_id = intval($object_id);
        $all_tags = $this->_tags_control_data();
        $current_ids = $this->_tags_ids($object_name, $object_id);
        foreach($current_ids as $tag_id) {
            $tag_id = intval($tag_id);
            if(array_key_exists($tag_id, $all_tags) && !(in_array($tag_id, $tags_ids))) {
                //delete tag for object_name
                db()
                    ->from($this->object_tags_table)
                    ->where('object_id', $object_id)
                    ->where('object', $object_name)
                    ->where('tag_id', $tag_id)
                    ->delete();
            }
        }
        foreach($tags_ids as $tag_id) {
            $tag_id = intval($tag_id);
            if(array_key_exists($tag_id, $all_tags) && !(in_array($tag_id, $current_ids))) {
                //add tag for object_name
                $this->_add_for_object([
                    'object' => $object_name,
                    'object_id' => $object_id,
                    'tag_id' => $tag_id
                ]);
            }
        }
    }

    function _add_for_object($params = []) {
        $object_id = isset($params['object_id'])?intval($params['object_id']) : false;
        $object = isset($params['object'])?$params['object'] : false;
        $tag_name = isset($params['tag_name'])?$params['tag_name'] : false;
        $tag_id = isset($params['tag_id'])?intval($params['tag_id']) : false;
        $show_message = isset($params['show_message']) ? $params['show_message'] : false;
        $result = false;
        if(($object !== false)&&($object_id!==false)) {
            $can_add = false;

            if(($tag_id === false)&&($tag_name!==false)) {
                $tag_url = $this->_name_to_url($tag_name);
                $tag_id = $this->_get_id_by_url($tag_url);
                if($tag_id === false) {
                    $tag_id = $this->_add_tag($tag_name);
                }
            }
            if($tag_id !== false) {
                $object_has_tag = db()
                    ->select('count(*)')
                    ->from($this->object_tags_table)
                    ->where('object_id', $object_id)
                    ->where('tag_id', $tag_id)
                    ->get_one();
                if($object_has_tag == 0){
                    $can_add = true;
                }
            }
            if($can_add) {
                $this->_add_object_tags($object, $object_id, $tag_id);
                if($show_message) {
                    common()->message_info('Тэг добавлен');
                }
                $result = true;
            }
        }
        return $result;
    }

    function _name_to_url($name) {
        return common()->_propose_url_from_name($name);
    }

    function _add_object_tags($object, $object_id, $tag_id) {
        if(!empty($object)&&!empty($object_id)&&!empty($tag_id)) {
            return  db()->insert_safe($this->object_tags_table, [
                'object_id'=>intval($object_id),
                'object'=>$object,
                'tag_id'=>intval($tag_id),
                'add_time'=>date (MYSQL_TIME_FORMAT)
            ]);
        }
        else {
            return false;
        }

    }

    function _delete_tags($object, $object_id) {
        if(!empty($object)&&!empty($object_id)) {
            return db()->from($this->object_tags_table)->where('object_id', $object_id)->where('object',$object)->delete();
        }
        else {
            return false;
        }
    }

	function _get_id_by_url($url) {
        $tag_id = db()->select('id')
            ->from($this->tags_table)
            ->where('url', _es($url))
            ->get_one();
        if(!empty($tag_id)) {
            return intval($tag_id);
        }
        else {
            return false;
        }
    }

    function _get_tags($object = null, $object_id = null) {
        $query = db()->select('t.*')
            ->from($this->tags_table.' as t');
        if(!empty($object)) {
            $object = _es($object);
            $object_id = intval($object_id);
            $query = $query
                ->left_join($this->object_tags_table.' as ot', 't.id=ot.tag_id')
                ->where_raw('ot.object_id='.$object_id)
                ->where_raw('ot.object=\''. $object.'\'');
        }
        return $query->get_all();
    }



    function _tags_control_data() {
        $data = $this->_get_tags();
        $values_array =[];
        foreach((array)$data as $item) {
            $values_array[$item['id']]=$item['name'];
        }
        return $values_array;
    }


    function _tags_ids($object, $object_id) {
        $data = $this->_get_tags($object, $object_id);
        $values_array =[];
        if(!empty($data)) {
            foreach ($data as $key => $value) {
                $values_array[$key] = $key;
            }
        }
        return $values_array;
    }

    function _tags_urls($object, $object_id) {
        $data = $this->_get_tags($object, $object_id);
        $values_array = [];
        if (count((array)$data) > 0) {
            foreach ($data as $item) {
                $values_array[] = [
                    'name' => $item['name'],
                    'url' => url_user('/tags/' . $item['url'])
                ];
            }
        }
        return $values_array;
    }

    function _tags_block($object, $object_id) {
        $replace = ['tags' => $this->_tags_urls($object, $object_id)];
        return tpl()->parse('tags/tags_block', $replace);
    }

    function _chosen_tags_script($chosen_box_id) {
        $chosen_box_id = '#'.$chosen_box_id;
        jquery(<<<SCRIPT_TEXT
        setTimeout(function()
            {
                $("$chosen_box_id").parent().find(".search-field input").keyup(
                    function (evt) {
                        var stroke, _ref, target, list;
                       // get keycode
                       stroke = (_ref = evt.which) != null ? _ref : evt.keyCode;
                       if (stroke === 9 || stroke === 13) {
                           var tag_name = $("$chosen_box_id").parent().find(".chosen-results span").text();
                           if(tag_name != "") {
                               $.ajax({
                                  type: "POST",
                                  data: {tag_name: tag_name},
                                  url: "?object=manage_tags&action=add",
                                  dataType : "json",
                                  success: function(data){
                                    if((data != "")&&(parseInt(data.tag_id)>0)) {
                                        var values = $("$chosen_box_id").chosen().val();
                                        if($(values).size()>0) {
                                            values.push(data.tag_id);
                                        }
                                        else {
                                            values = data.tag_id;
                                        }
                                        $("$chosen_box_id").append("<option value=\""+data.tag_id+"\">"+data.tag_name+"</option>");
                                        $("$chosen_box_id").chosen().val(values);
                                        $("$chosen_box_id").trigger("chosen:updated");
                                    }
                                  }
                               });
                           }
                       }
                    }
                );
            }, 100);
SCRIPT_TEXT
        );
    }
}
