<?php

/**
 * Administrators home page.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class admin_stats
{



    public function show()
    {
		return "AssetGeek content management system";
    }

	/**
     * @param mixed $url
     */
    public function _url_allowed($url = '')
    {
        return _class('admin_methods')->_admin_link_is_allowed($url);
    }

}
