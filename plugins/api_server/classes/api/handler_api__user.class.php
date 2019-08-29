<?php

class handler_api__user extends api_handler__base {

	const LOGIN_ERROR_EMPTY   = 1;
	const data_fake = false;
	public $use_base64 = false;

	function profile( $raw = null ) {
		// input data
		$options = $this->get( $raw );
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$user_id = (int)$this->auth[ 'user_id' ];
		$data = _class( 'user_handler' )->_get_profile( array(
			'user_id' => $user_id,
		));
		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function register($raw = null){

		$options = $this->get( $raw, ['name', 'email', 'password'] );
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(!isset($_email) || !isset($_name) || !isset($_password)) {
			return  $this->result( array(
				'data' => 'Data empty!',
				'status' => 1,
			));
		}
		$errors = false;
		$status  = 0;
		$is_valid_email = _class('validate')->valid_email($_email);
		if(!$is_valid_email){
			$errors['email_not_valid']  = true;
		}else{
			$exist_email = module('register')->_email_not_exists($_email);
			if(!$exist_email){
				$errors['exist_email']  = true;
			}
		}
        $errors['register_temporary_disabled']  = true;

		$data = $errors;
		$status = 1;

		return $this->result( array(
			'data' => $data,
			'status' => $status,
		));
	}

	function _email_not_exists($raw = null){

		$options = $this->get( $raw, ['email'] );
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if(!isset($_email)) return false;
		$valid_email = module('register')->_email_not_exists($_email);
		// response
		return $this->result( array(
			'data' => $valid_email,
		));

	}


	function get_accounts_data($raw = null){
		// input data
		$options = $this->get($raw, ['url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return false;
		}
		$is_owner = false;
		if(!$_get_id || ($_get_id == $user_id)){
			$is_owner = true;
		}
		$accounts_data = _class('games_handler')->_get_game_accounts(['user_id' => $_user_id]);
		$return = [
			'is_owner' => $is_owner,
			'accounts' => $accounts_data,
		];
		return $this->result( array(
			'data' => $return,
		));
	}

	function delete_game_account($raw = null){
		// input data
		$options = $this->get($raw, ['account_id']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$user_id = (int)$this->user_id;
		$data = _class('games_handler')->_delete_game_account(['user_id' => $user_id, 'account_id' => $_account_id]);
		if(is_null($data)){
			return $this->result( array(
				'status' => -1,
				'data' => t('Ошибка удаления аккаунта'),
			));
		}
		return $this->result( array(
			'data' => true,
		));
	}

	function get_edit_profile_data( $raw = null ) {
		// input data
		$options = $this->get( $raw );

		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$user_id = (int)$this->user_id;
		$data = _class( 'user_handler' )->_get_profile_data(['user_id' => $user_id,]);
		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function get_country_list( $raw = null ){
		$data = _class( 'user_handler' )->_country_list();
		// response
		return $this->result( array(
			'data' => $data,
		));
	}


	function set_profile_data( $raw = null ){
		$validate_extended = [
			'name'      => 'trim|xss_clean',
			'nick'      => 'trim|xss_clean',
			'email'     => 'trim|xss_clean',
			'country'   => 'trim|xss_clean',
			'lang'      => 'trim|xss_clean',
			'fb_url'    => 'trim|xss_clean',
			'gplus_url' => 'trim|xss_clean',
 		];

		// input data
		$options = $this->get( $raw, array_keys($validate_extended) );

		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// start
		$user_id = (int)$this->user_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$errors = false;
		if(!$_name){
			$errors['empty_name_text'] = t('Настоящее имя не указано');
		}
		if(!$_nick){
			$errors['empty_nick_text'] = t('Имя не указано');
		}
		if(!$_email){
			$errors['empty_email_text'] = t('Поле email обязательно');
		}
		if(!_class('validate')->valid_email($_email)){
			$errors['email_not_valid_text'] = t('Неправильный формат email');
		}elseif(!_class( 'user_handler' )->_unique_email(['user_id' => $user_id, 'email' => $_email])){
			$errors['exist_email_text']  = t('Этот email уже используется');
		}
		$clean_fb_url = common()->_check_url($_fb_url);
		$clean_gplus_url = common()->_check_url($_gplus_url);
		if($clean_fb_url === false){
			$errors['incorrect_fb_url']  = t('Неправильный урл');
		}
		if($clean_gplus_url === false){
			$errors['incorrect_gplus_url']  = t('Неправильный урл');
		}
		if($errors){
			return $this->result( array(
				'data'   => $errors,
				'status' => 1,
			));
		}
		$options['fb_url'] = $clean_fb_url;
		$options['gplus_url'] = $clean_gplus_url;
		_class('validate')->_input_is_valid($options, $validate_extended);
		$options['user_id'] = $user_id;
		$data = _class( 'user_handler' )->_update_profile($options);
		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function get_avatar($raw = null){
		// input data
		$options = $this->get($raw, ['url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return false;
		}
		$data = _class('user_avatar')->_show_avatar($_user_id, false, true, false, $this->use_base64);
		// response
		return $this->result( array(
			'data' => $data['img_path'],
		));
	}


    function delete_avatar($raw = null){
		// input data
		$options = $this->get($raw, ['url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
            return $this->result( array(
    			'data' => '',
                'status' => 0,
    		));
		}else{
			$user_id = (int)$this->user_id;
		}
		$data = _class('user_avatar')->_delete_avatar($user_id);
		// response
		return $this->result( array(
			'data' => '',
            'status' => 1,
		));
	}

	function get_tournament_battles($raw = null){
		return $this->get_user_battles($raw);
	}

	function get_user_games($raw = null){
		$options = $this->get($raw,['url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return $this->result( array(
				'message'   => t('no user_id'),
				'status' => -1,
			));
		}

		$played_games = getset('user_played_games_'.$_user_id, function() use($_user_id) {
		$played_games = db()->get_all("SELECT bsu.game_id, g.name, g.short_name
			FROM `e_battle_stats_by_user` as bsu
			left join e_games as g on (g.id = bsu.game_id)
			WHERE `user_id` = '".$_user_id."'
			group by game_id
			order by game_id asc
			");
			return $played_games ?: [];
		});
		$cur_game =  $played_games[0]['game_id'];
		return $this->result( array(
			'data' => [
				'played_games' => $played_games,
				 'cur_game' =>  $cur_game
			],
		));
	}

	function get_user_battles($raw = null){
		$options = $this->get($raw,['status_id','type_id', 'game_id', 'filter', 'url', 'total', 'curr_page']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_status_id = 3;
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return $this->result( array(
				'message'   => t('no user_id'),
				'status' => -1,
			));
		}
		$options['battle_user'] = (int) $_user_id;
		$options['status'] = (int) $_status_id;
		$options['filter_data'] = $_filter;
		$options['per_page'] = 5;

		$battles = _class('battles_handler')->_get_battles_info($options );
		$battles_info = $battles[0];
		if(!$battles[0]){
			return $this->result( array(
				'message'   => t('no data'),
				'status' => -1,
			));

		}

		if($options){
			$key_for_cache = "_".crc32(json_encode($options));
		}

		$_this = $this;
		$user_battles = getset('user_battles'.$key_for_cache, function() use($options, $battles_info, $battles,$_user_id) {
			is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
			if(is_array($_type_id) && (in_array(5,$_type_id) ||in_array(6,$_type_id) )){
				$phases = _class('tournaments_handler')->_get_phase_by_ids(array_keys($battles[0]));
			}
			$rating_log = _class('rating_handler')->_get_rating_by_battle_ids(array_keys($battles_info), $_user_id);
			foreach($battles_info as $key => $val){
				$battles_info[$key]['elo']   = (int) $rating_log[$key];
				$battles_info[$key]['profile_owner_id']   = $_user_id;
				$battles_info[$key]['phase'] =  $phases[$key]['phase'] ?:'';
				$battles_info[$key]['prize'] =  $phases[$key]['prize'] ?:'';
				if($battles_info[$key]['type_id'] == 3 || $battles_info[$key]['type_id'] == 6){
					$opponent_avatar = _class('user_avatar')->_show_avatar($val['clan_opponent_id'], false, true, false, false);
					$owner_avatar = _class('user_avatar')->_show_avatar($val['clan_owner_id'], false, true, false, false);
					$battles_info[$key]['owner_side'] = $_user_id == $val['account_id'] ? 1 :2;
					$battles_info[$key]['side_1_avatar']   = $owner_avatar['img_path'];
					$battles_info[$key]['side_1_acc_name'] = $val['clan_owner_name'];
					$battles_info[$key]['side_1_id']       = (int) $val['clan_owner_id'];
					$battles_info[$key]['side_2_id']       = (int) $val['clan_opponent_id'];
					$battles_info[$key]['side_2_avatar']   = $opponent_avatar['img_path'];
					$battles_info[$key]['side_2_acc_name'] = $val['opponent_clan_name'];
					$battles_info[$key]['is_win'] = $val['winner'] == $battles_info[$key]['owner_side'] ? 1 : 0;
				}else{
					$battles_info[$key]['owner_side'] = $_user_id == $val['account_id'] ? 1 :2;
					$battles_info[$key]['side_1_avatar']   = $val['account_avatar'];
					$battles_info[$key]['side_1_acc_name'] = $val['account_name'];
					$battles_info[$key]['side_1_id']       = (int) $val['account_id'];
					$battles_info[$key]['side_2_id']       = (int) $val['opponent_id'];
					$battles_info[$key]['side_2_avatar']   = $val['opponent_avatar'];
					$battles_info[$key]['side_2_acc_name'] = $val['opponent_account_name'];
					$battles_info[$key]['is_win'] = $val['winner'] == $battles_info[$key]['owner_side'] ? 1 : 0;
				}
				if($scores[$key]){
					if( (int)$scores[$key]['t'] == 0 && (int)$scores[$key]['ct'] == 0){
						$battles_info[$key]['score']  = $battles_info[$key]['is_win'] == 1 ? '0-1' : '1-0';
					}else{
						$battles_info[$key]['score'] = $scores[$key]['t'] .'-'.$scores[$key]['ct'];
					}
				}else{
					$battles_info[$key]['score']  = $battles_info[$key]['is_win'] == 1 ? '0-1' : '1-0';
				}
			}
			$data = [
				'battles' => array_values($battles_info),
				'total'   => $battles[2],
			];
			return $data;
		});
		return $this->result( array(
			'data' => $user_battles,
		));
	}

	function get_current_battles($raw = null){
		$options = $this->get($raw,['status_id', 'game_id', 'filter', 'url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return $this->result( array(
				'message' => t('empty user_id'),
				'status' => -1,
			));
			return false;
		}
		$_status_id = [1,2];
		$battles = _class('battles_handler')->_get_battles_info(['status' => $_status_id, 'filter_data' => $_filter, 'battle_user' => (int) $_user_id] );
		if(!$battles[0]){
			return $this->result( array(
				'message' => t('empty battle list'),
				'status' => -1,
			));
		}
		$battles_info = $battles[0];
		$opponent_ids = [];
		$isset_waiting_battles = false;
		$isset_redy_battles = false;
		foreach($battles_info as $key => $val){
			if($val['status_id'] == 1){
				$isset_waiting_battles = true;
			}
			if($val['status_id'] == 2){
				$isset_redy_battles = true;
			}
			if(!$val['opponent_account_name']){
				$val['opponent_account_name'] = t('Нет соперника');
			}
			if($val['account_id'] == $_user_id ){
				$battles_info[$key]['avatar']   = $val['opponent_avatar'];
				$battles_info[$key]['acc_name'] = $val['opponent_account_name'];
			}else{
				$battles_info[$key]['opponent_id']   = $val['account_id'];
				$battles_info[$key]['avatar']   = $val['account_avatar'];
				$battles_info[$key]['acc_name'] = $val['account_name'];
				$battles_info[$key]['opponent_rating'] = $battles_info[$key]['owner_rating'];
				$battles_info[$key]['opponent_max_rating'] = $battles_info[$key]['max_rating'];
				$battles_info[$key]['opponent_league_rank'] = $battles_info[$key]['league_rank'];
				$battles_info[$key]['opponent_class_league_name'] = $battles_info[$key]['class_league_name'];
				$battles_info[$key]['opponent_league_short_name'] = $battles_info[$key]['league_short_name'];
			}
		}
		$data = [
			'battles' => array_values($battles_info),
			'total'   => $battles[2],
			'isset_waiting_battles'   => $isset_waiting_battles,
			'isset_redy_battles'   => $isset_redy_battles,
		];
		return $this->result( array(
			'data' => $data,
		));
	}

	function get_email_notifications_settings($raw = null){
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
        $data_out = _class('email_notifications_settings_handler')->_generate_config_form($this->user_id);
		return $this->result( [
			'data' => $data_out,
		]);
	}

	function update_email_notifications_settings($raw = null){
		$options = $this->get($raw,['config_data']);
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
        $result = _class('email_notifications_settings_handler')->_update_user_config($this->user_id, $options['config_data']);
		return $this->result( [
            'data' => [],
		]);
	}

    function verify_email_request($raw = null){
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
        $result = _class('user_handler')->_get_profile_data(['user_id' => $this->user_id]);
		return $this->result( [
            'data' => [
                'is_email_verified' => $result['email'] && $result['email'] == $result['email_validated'],
				'curr_email' => strpos($result['email'], 'eloplay.com') === false ? $result['email'] : "" ,
            ],
		]);
    }

    function verify_email_send($raw = null){
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$is_force = &$raw[ 'is_force' ];
        $user_info = _class('common')->user($this->user_id, 'full', '', null, null, [ 'refresh_cache' => $is_force ]);
        if($user_info['email'] && $user_info['email'] != $user_info['email_validated']) {
            $code = md5(time().'some_salt'.uniqid());
            db()->update('user',  array('verify_code' => $code, 'last_update' => time()), $user_info['id']);
            $email_to = $user_info["email"];
            $name_to = $user_info["name"];
            $replace = array(
                'name'         => $user_info['name'] ?: $user_info['first_name'],
                'login'        => $user_info['login'],
                'password'     => $user_info['password'],
                "confirm_link" => url_user("/register/activation/".$code),
            );
			$send_result = _class('email')->_send_email_safe($email_to, $name_to, 'email_resend_code', $replace);
		}
		return $this->result( [
			'data' => [
				'sending' => $send_result,
            ],
		]);
    }

	function update_email($raw = null){
		$validate = [
			'email'     => 'trim|xss_clean',
 		];

		// input data
		$options = $this->get( $raw, array_keys($validate) );
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// start
		$user_id = (int)$this->user_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$errors = false;
		if(!$_email){
			$errors['error'] = t('Поле email обязательно');
		}
		if(!$errors && !_class('validate')->valid_email($_email)){
			$errors['error'] = t('Неправильный формат email');
		}elseif(!$errors &&!_class( 'user_handler' )->_unique_email(['user_id' => $user_id, 'email' => $_email])){
			$errors['error']  = t('Этот email уже используется');
		}
		if($errors){
			return $this->result( array(
				'data'   => $errors,
				'status' => -1,
			));
		}
		_class('validate')->_input_is_valid(['email' => $_email], $validate);
		db()->update('user', ['email' => $_email], 'id = '.$user_id);
		$_raw = $raw; $_raw[ 'is_force' ] = true;
		$r = $this->verify_email_send( $_raw );
		$sending = &$r[ 'data' ][ 'sending' ];
		// response
		return $this->result([ 'data' => [
			'email'   => $_email,
			'sending' => $sending,
		]]);
	}

	function _get_id($_url){
		if(!isset($_url)) return false;
		preg_match('/profile\/([\d]+).*?$/imsu', $_url, $match);
		return $match[1] ?: false;
	}

	function get_id($raw = null){

		$options = $this->get($raw,['url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$id = $this->_get_id($_url);
		return $this->result( array(
			'data' => $id,
		));
	}


	function set_new_passwd( $raw = null ){
		$validate_rules = [
			'old_passwd'       => 'trim|xss_clean',
			'new_passwd'       => 'trim|xss_clean',
			'new_passwd_again' => 'trim|xss_clean',
 		];

		// input data
		$options = $this->get( $raw, array_keys($validate_rules) );

		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// start
		$user_id = (int)$this->user_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$errors = false;
		if(!$_old_passwd){
			$errors['old_pswd_text'] = t('Старый пароль не указан');
		}
		if(!$errors['old_pswd_text']){
			if(!db()->select('*')->from('user')->whereid($user_id)->where_raw("password = '".$_old_passwd."'")->get()){
				$errors['old_pswd_text'] = t('Старый пароль не совпадает');
			}
		}
		if(!$_new_passwd){
			$errors['error_new_passwd_text'] = t('Новый пароль не указан');
		}
		if($_new_passwd != $_new_passwd_again){
			$errors['error_new_passwd_again_text'] = t('Поля не совпадают');
		}
		if($errors){
			return $this->result( array(
				'data'   => $errors,
				'status' => 1,
			));
		}
		_class('validate')->_input_is_valid($options, $validate_extended);
		$options['user_id'] = $user_id;
		$data = _class( 'user_handler' )->_update_passwd($options);
		$log = array(
			'user_id'          => $user_id,
			'object'           => 'profile',
			'action'           => 'set_new_passwd',
			'object_id'        => $user_id,
			'add_time'         => time(),
			'tpl_name'         => 'set_new_passwd',
			'old_passwd'       => $_old_passwd,
			'new_passwd'       => $_new_passwd,

		);
		common()->_user_action_log($log,[$user_id]);

		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function create_new_passwd( $raw = null ){
		$validate_rules = [
			'new_passwd'       => 'trim|xss_clean',
			'new_passwd_again' => 'trim|xss_clean',
 		];

		// input data
		$options = $this->get( $raw, array_keys($validate_rules) );

		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// start
		$user_id = (int)$this->user_id;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$errors = false;
		if(!$_new_passwd){
			$errors['error_new_passwd_text'] = t('Новый пароль не указан');
		}
		if($_new_passwd != $_new_passwd_again){
			$errors['error_new_passwd_again_text'] = t('Поля не совпадают');
		}
		if($errors){
			return $this->result( array(
				'data'   => $errors,
				'status' => 1,
			));
		}
		_class('validate')->_input_is_valid($options, $validate_extended);
		$options['user_id'] = $user_id;
		$data = _class( 'user_handler' )->_update_passwd($options);

		$log = array(
			'user_id'          => $user_id,
			'object'           => 'profile',
			'action'           => 'create_new_passwd',
			'object_id'        => $user_id,
			'add_time'         => time(),
			'tpl_name'         => 'create_new_passwd',
			'new_passwd'       => $_new_passwd,

		);
		common()->_user_action_log($log,[$user_id]);


		// response
		return $this->result( array(
			'data' => $data,
		));
	}


	function restore_passwd( $raw = null ){
		// input data
		$options = $this->get( $raw, ['email'] );
		$data = _class('user_handler')->_restore_passwd($options);
		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function get_logged_avatar( $raw = null ){
		// input data
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$data = _class('user_avatar')->_show_avatar($user_id, false, true, false, $this->use_base64);
		// response
		return $this->result( array(
			'data' => ['avatar' => $data['img_path']],
		));
	}

	function get_rating_data_by_user_id($raw = null){
		return $this->get_rating_data($raw);
	}

	function get_rating_data($raw = null){
		// auth
		$options = $this->get( $raw, ['game_id','url'] );
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
	//	if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// start

		if( ! $this->is_auth( $raw ) ) {
			$_user_id = 0;
		}else{
			$_user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);

		$user_id = $_get_id ?: $_user_id;
		if(!$user_id){
			return $this->result( array(
				'message' => t('empty user_id'),
				'status' => -1,
			));

			return false;
		}

	// import options
		if((int) $_game_id < 1){
			$_game_id = _class('user_handler')->_get_user_favourite_game($user_id);
		}

		$rating = _class('rating_handler')->_user_stats_by_ids(['user_ids' => [$user_id], 'game_id' => $_game_id]);

		$league_info  =  _class('rating_handler')->_league_info_simple();
		$league_id= _class('rating_handler')->_get_user_league_by_game($user_id, $_game_id);
		$points_conf  = $league_info[$league_id];

		// response
		return $this->result( [
			'data' => [
				'rating' => $rating[$user_id],
				'league_name' =>  strtolower($points_conf['name']),
				'league_rank' =>  $points_conf['rank'],
				'max_rating' => $points_conf['max_elo'] ?: '',
			]]
		);

	}

	function user_stats($raw = null){
		$options = $this->get( $raw,['users_online', 'earned_amount', 'total_players','url', 'game_id']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

		$data = _class('user_handler')->_user_stats($options);
		// response

		$online_user_ids = (array)redis()->smembers('online_by_user');
		$online_ids = [];
		foreach ((array)$online_user_ids as $uid) {
			$online_last_time = (int)(redis()->hget('online_by_user_last', $uid) / 100);
			$online_minutes_ago = floor((time() - $online_last_time) / 60);
			if ($online_last_time && $online_minutes_ago <= 30) {
				$online_ids[$uid] = $uid;
				$online_ago[$uid] = $online_minutes_ago;
			}
		}

		$online_user = count((array)$online_ids);

		$total_players  = $data['total_players'] ;

		$is_debug = strpos($_url, 'debug=57');
		if(self::data_fake && !$is_debug){
			$online_user +=  mt_rand(15,20);
		}

		return $this->result( [
			'data' => [
				'users_online'  =>  $online_user,
				'total_players' =>  $total_players ,
				'already_played'=> $data['already_played'],
				'top_winners'   => $data['top_winners'],
			]]
		);
	}

	function get_total_stats($raw = null){
		$options = $this->get($raw,['game_id', 'url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return $this->result( array(
				'message' => t('empty user_id'),
				'status' => -1,
			));

			return false;
		}
		$data = _class('battle_stats_handler')->_get_total_stats($_user_id, $_game_id);
		return $this->result( array(
			'data'   => $data ,
			'status' => $data ? 0 : -1,
		));

	}

	function get_rating_places($raw = null){

		$options = $this->get($raw,['game_id', 'url']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$_get_id = $this->_get_id($_url);
		$_user_id = $_get_id ?: $user_id;
		if(!$_user_id){
			return $this->result( array(
				'message' => t('empty user_id'),
				'status' => -1,
			));

			return false;
		}
		if(!$_game_id){
			return $this->result( array(
				'message' => t('empty game_id'),
				'status' => -1,
			));

			return false;
		}
		$user_info = _class('user_handler')->_profile_extended_data(['user_id' => $_user_id]);
		$region_place = _class('rating_handler')->_get_user_place(['user_id'    => $_user_id, 'country'   => $user_info['country'], 'game_id' => $_game_id]);
		$world_place = _class('rating_handler')->_get_user_place(['user_id'    => $_user_id, 'game_id' => $_game_id]);

		$data =   [
			'world_place' => (int)$world_place != 0 ? (int)$world_place : t('БЕЗ РАНГА') ,
			'region_place' => (int)$region_place  != 0 ? (int)$region_place : t('БЕЗ РАНГА')
		];


		return $this->result( array(
			'data' => $data,
			'status' => 0,
		));



	}

}
