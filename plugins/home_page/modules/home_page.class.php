<?php

/**
 * Home page handling module.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class home_page {

    public function show()
    {
		echo json_encode(['ping' => 'OK']);
		exit;
    }
}
