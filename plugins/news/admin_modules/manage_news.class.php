<?php

class manage_news  {

	/** @const */
	const table = 'news';
	/** @var int Text preview cutoff */
	public $TEXT_PREVIEW_LENGTH	= 200;
	/** @var bool Filter on/off */
	public $USE_FILTER				= true;
	/** @var bool */
	public $USE_BB_CODES			= true;
	/** @var array Params for the comments */
	public $_comments_params			= [
		'return_action' => 'view',
		'object_name'	=> 'news',
	];
	/** @var array */
	public $per_page_values = [25 => 25, 50 => 50, 100 => 100, 200 => 200];

	/**
	*/
	function _init() {
		main()->USE_SYSTEM_CACHE = false;

		if ($_GET['action'] != 'show') {
			main()->NO_SIDE_AREA_TOGGLER = 1;
			css('
				.center_area { margin-left:1%; width:98%; }
				.left_area { display: none; }
			');
		}

		$this->_account_types	= main()->get_data('account_types');
		$this->_news_statuses = [
			'new'		=> t('new'),
			'edited'	=> t('edited'),
			'suspended'	=> t('suspended'),
			'active'	=> t('active'),
		];
	}

	/**
	*/
	function _show_quick_filter () {
		$a = [];
		$a[] = a('/@object/filter_save/clear/?filter=active:0', 'Filter inactive news', 'fa fa-ban', '', 'btn-warning', '');
		$a[] = a('/@object/filter_save/clear/?filter=active:1', 'Filter active news', 'fa fa-check', '', 'btn-success', '');
		$a[] = a('/@object/filter_save/clear/', 'Clear filter', 'fa fa-close', '', 'btn-primary', '');
		return $a ? '<div class="pull-right">'.implode(PHP_EOL, $a).'</div>' : '';
	}

	/**
	*/
	function show () {
		$quick_filter = $this->_show_quick_filter();
		$filter = _class('admin_methods')->_get_filter();
		return
			'<div class="col-md-12">'.
				($quick_filter ? '<div class="col-md-6 pull-right" title="'.t('Quick filter').'">'.$quick_filter.'</div>' : '').
			'</div>'.
			table(from(self::table), [
				'hide_empty' => true,
				'filter' => true,
				'filter_params' => [
					'title'			=> 'like',
					'add_date'		=> 'daterange_between',
					'edit_date'		=> 'daterange_between',
					'publish_date'	=> 'daterange_between',
					'cat_id'		=> function($a){ return $a['value'] ? '`id` IN(SELECT object_id FROM '.db('object_cats').' WHERE `object` = "news" AND cat_id = '.(int)$a['value'].')' : ''; },
					'subquery_date'	=> function($a){ return ''; },
					'per_page'		=> function($a){ return ''; },
					'__default_order' => 'ORDER BY add_date DESC',
				],
				'custom_fields' => [],
			])
			->on_before_render(function($p, $data, $table) {})
			->text('id')
   			->text('title')
			->date('add_date', ['format' => 'full', 'nowrap' => 1])
			->date('publish_date', ['format' => 'full', 'nowrap' => 1])
  			->btn_edit('Edit', url('/@object/edit/%d'), ['no_ajax' => 1, 'btn_no_text' => 1])
			->btn_delete('Delete', url('/@object/delete/%d'), ['btn_no_text' => 1])
   			->btn_active('', url('/@object/activate/%d'))
			->header_add('Add', url('/@object/add'), ['no_ajax' => 1, 'class_add' => 'btn-primary'])
			->footer_add('Add', url('/@object/add'), ['no_ajax' => 1, 'class_add' => 'btn-primary'])
		;
	}

	/**
	*/
	function add() {
		return form($_POST, [
			'legend' => 'Add news'
		])
		->validate(['title' => 'trim|required'])
		->on_validate_ok(function() {
			db()->insert_safe(self::table, [
				'title'			=> $_POST['title'],
				'lang'			=> $_POST['lang'],
				'url'			=> module('news')->_name_to_url($title),
				'add_date'		=> time(),
				'publish_date'	=> time(),
				'active'		=> 0,
				'user_id'		=> main()->ADMIN_ID,
			]);
			$news_id = db()->insert_id();
			common()->message_info('News entry was added');
			return js_redirect('/@object/edit/'. $news_id);
		})
		->text('title', ['desc' => 'News entry title'])
		->save();
	}

	/**
	*/
	function edit() {
		return _class('manage_news_edit', 'admin_modules/manage_news/')->{__FUNCTION__}();
	}

	/**
	*/
	function delete() {
		$news_id = intval($_GET['id']);
		$news_info = module('news')->_get_info($news_id, false, $no_cache = true);
		if (!empty($news_info['id'])) {
			db()->delete(self::table, $news_id);
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $news_id;
		} else {
			common()->message_info('News entry was deleted');
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function activate() {
		$news_id = intval($_GET['id']);
		$news_info = module('news')->_get_info($news_id, false, $no_cache = true);
		$active = $news_info['active'] == 1 ? 0 : 1;
		if (!empty($news_info['id'])) {
			$values = [
				'active' => $active,
			];
			if ($active === 1) {
				$values['publish_date'] = time();
			}
			db()->update_safe(self::table, $values, $news_id);
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $active;
		} else {
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function browse_images() {
		return _class('manage_news_browse_images', 'admin_modules/manage_news/')->{__FUNCTION__}();
	}

	/**
	*/
	function upload_image() {
		return _class('manage_news_upload_image', 'admin_modules/manage_news/')->{__FUNCTION__}();
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], ['show'])) {
			return false;
		}
		$order_fields = [];
		foreach (explode('|', 'id|title|active|add_date') as $f) {
			$order_fields[$f] = $f;
		}
		$c = _class('cats');
		$cats = $c->_prepare_for_box($c->news_category);
		unset($cats[' ']);

		$used_langs = from(self::table)->where_raw('lang != ""')->group_by('lang')->get_2d('lang, lang as l2');
		$row_tpl = '%icon %name %code';
		foreach ((array)main()->get_data('languages_new') as $v) {
			if (!isset($used_langs[$v['code']])) {
				continue;
			}
			$r = [
				'%icon'	=> ($v['country'] ? '<i class="bfh-flag-'.strtoupper($v['country']).'"></i> ' : ''),
				'%name'	=> $v['native'],
				'%code'	=> '['.$v['code'].']',
			];
			$langs[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}

		$min_day = conf('PROJECT_LAUNCH_DATE') ?: date('Y-m-d', strtotime('-60 days'));
		$min_date = from(self::table)->one('UNIX_TIMESTAMP(IF(MIN(add_date_day) < "'.$min_day.'", "'.$min_day.'", add_date_day))');
		$date_params = [
			'format'		=> 'YYYY-MM-DD',
			'min_date'		=> date('Y-m-d', $min_date ?: (time() - 86400 * 30)),
			'max_date'		=> date('Y-m-d', time() + 86400),
			'autocomplete'	=> 'off',
			'no_label'		=> 1,
		];

		return form($r, ['filter' => true])
			->number('id', ['style' => 'width:100px', 'no_label' => 1])
			->text('title', ['no_label' => 1])
			->daterange('add_date', $date_params + ['desc' => 'Add date'])
			->daterange('publish_date', $date_params + ['desc' => 'Publish date'])
			->row_start()
				->select_box('order_by', $order_fields, ['show_text' => '-- '.t('Order by').' --', 'desc' => 'Order by'])
				->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
				->select_box('per_page', $this->per_page_values, ['style' => 'width:100px', 'no_label' => 1])
			->row_end()
			->yes_no_box('active', ['show_text' => 1, 'desc' => 'Published'])
			->save_and_clear();
		;
	}
}
