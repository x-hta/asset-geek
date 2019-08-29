<?php

class manage_tags {
    function add()
    {
        $tag_name = isset($_POST['tag_name']) ? $_POST['tag_name'] : '';
        $tag_id = 0;
        if(!empty($tag_name)) {
            $tags_class = module('tags');
            $tag_id = $tags_class->_add_tag($tag_name);
        }
        echo json_encode(['tag_name'=>$tag_name, 'tag_id'=>$tag_id]);
        die();
    }
}