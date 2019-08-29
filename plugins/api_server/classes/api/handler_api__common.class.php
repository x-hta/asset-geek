<?php

class handler_api__common extends api_handler__base {

	public $channel = null;

	public $salt = 'common__api_salt';
	const ERROR_ACCESS_DENIED = -3;
	const ERROR_CHANNEL_EMPTY = -2;
	const ERROR_SOCKET_ID     = -1;

	function _validation( $raw = null ) {
		// input data
		$options = $this->get( $raw, array(
			'channel',
		));
		// auth
		$this->is_auth( $raw );
		// if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		// empty socket_id
		if( !$this->socket_id ) {
			$message = t( 'Empty socket_id' );
			return $this->result( array(
				'status'  => self::ERROR_SOCKET_ID,
				'message' => $message,
			));
		}
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
        // channel
		if( !$_channel ) {
			$message = t( 'Empty channel' );
			return $this->result( array(
				'status'  => self::ERROR_CHANNEL_EMPTY,
				'message' => $message,
			));
		}

		$this->channel = $_channel;
		return( true );
	}

	function subscribe( $raw = null ) {
		$result = $this->_validation( $raw );
		if( $result !== true ) { return( $result ); }
		// start
		$status = true;
		$channel = &$this->channel;
		$result  = $this->__subscribe([
			'channel'   => $channel,
			'socket_id' => $this->socket_id
		]);
		$data = array(
			'channel' => $channel,
		);
		// response
		return $this->result( array(
			'status' => (bool)$result,
			'data'   => $data,
		));
	}

	function unsubscribe( $raw = null ) {
		$result = $this->_validation( $raw );
		if( $result !== true ) { return( $result ); }
		// start
		$status = true;
		$channel = &$this->channel;
		$result  = $this->__unsubscribe([
			'channel'   => $channel,
			'socket_id' => $this->socket_id
		]);
		$data = array(
			'channel' => $channel,
		);
		// response
		return $this->result( array(
			'status' => (bool)$result,
			'data'   => $data,
		));
	}


	function get_user_id($raw = null){

		$options = $this->get( $raw);
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$user_id = (int)$this->user_id;
		return $this->result( [
			'data' => ['user_id' => $user_id]
		]);
	}

	function get_subscribe_key($raw = null){

		$options = $this->get( $raw);
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$user_id = (int)$this->user_id;

		$key = ( md5( $this->salt . $user_id) );

		return $this->result( [
			'data' => $key,
		]);
	}

	function get_game_accounts($raw = null){
		// input data
		$options = $this->get( $raw,['game_id', 'region_id']);
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

		if( @$_game_id < 1 ) {
			return $this->result( array(
				'data' => 'empty enter data',
				'status' => -1,
			));

		}
		$user_id = (int)$this->user_id;
		$game_accounts = _class('games_handler')->_get_game_accounts(['game_id' => (int)$_game_id, 'game_region_id' => (int)$_region_id, 'user_id' => $user_id]);
		return $this->result( [
			'data' => $game_accounts,
		]);
	}

	function get_games($raw = null){
		$options = $this->get( $raw);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$games = _class('games_handler')->_get_games();
		return $this->result( [
			'data' => $games,
		]);
	}

	function add_game_account($raw = null){
		$options = $this->get( $raw,['game_id', 'region_id', 'account']);
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$user_id = (int)$this->user_id;
		$data = [
			'user_id'   => $user_id,
			'game_id'   => $_game_id,
			'region_id' => $_region_id,
			'account'   => $_account
		];
		$result = _class('games_handler')->_add_game_account($data);
		return $this->result( [
			//'data' => 4564,
			'data' => $result,
		]);

	}

	function get_history($raw = null){
		$options = $this->get( $raw,['object_id', 'object', 'user_id', 'limit']);
		$lang = $this->language_id;
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! $this->is_auth( $raw ) ) {
			$user_id = 0;
		}else{
			$user_id = (int)$this->user_id;
		}
		$data  = _class( 'common' )->_history([ 'object_id' => $_object_id, 'object' => $_object, 'user_id' => $user_id, 'limit' => $_limit , 'lang' => $lang]);
		return $this->result( [
			'data' => $data,
		]);
	}

	function publish($raw = null){
		$options = $this->get( $raw,['channel', 'agent', 'message', 'data', 'type']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$message = [
			'type' => @$_type ?: 'update',
			'data' => @$_data,
		];
		if(isset($_message) && is_array($_message)){
			$message = array_merge($message, $_message);
		}
		 $result  =	$this->__publish([
            'agent'        => $_agent ?: 'common.message',
			'channel'      => $_channel,
			'is_subscribe' => false,
			'type'         => @$_type ?: 'update',
			'message'      => $message,
		]);
		if( $result !== true ) { return( $result ); }
		$data = array(
			'channel' => $_channel,
		);
		// response
		return $this->result( array(
			'status' => $result,
			'data'   => $data,
		));
	}

	function is_modal_opened($raw = null){
		$options = $this->get( $raw,['game_id']);
		// auth
		$this->is_auth( $raw );
		$user_id = (int)$this->user_id;
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

		$data =  $this->share->redis_hGetAll( 'modal_opened_'.$user_id);
		if($data){
			$time = $this->share->redis_ttl( 'modal_opened_'.$user_id);
			$data['time'] = $time;
			$message = [
				'type' => 'update',
				'data' => $data,

			];

			$result  =	$this->__publish([
				'agent'        => 'common.message',
				'channel'   => "open_modal_channel.".$user_id,
				'is_subscribe' => false,
				'type'         => @$_type ?: 'update',
				'message'      => $message,
			]);

		}
		return $this->result( array(
			'status' => 0,
		));
	}

	function message( $raw = null ) {
		$result = $this->_validation( $raw );
		if( $result !== true ) { return( $result ); }
		// start
		$channel = &$this->channel;
		$status = @$raw[ 'status' ];
		// cleanup channel
		if( !$status ) {
			$result = $this->__unsubscribe([
				'channel'   => $channel,
				'socket_id' => $this->socket_id
			]);
		}
		return( true );
	}

	function get_oauth($raw = null){
		$data = module('login_form')->oauth(array('only_icons' => 1))	;

		return $this->result( [
			'data' => $data,
		]);
	}

	function get_sys_messages($raw = null){
		$this->is_auth( $raw );
		$user_id = (int)$this->user_id;
		$data =  $this->share->redis_Get('sys_messages:'.$user_id);
		if($data){
			$this->__publish([
				'agent'     => 'common.message',
				'message'  => [
					'type'      => 'update',
					'event'     => 'custom_message',
					'data'      => $data,
				],
				'channel'   => "top_notificator_battles_activity." . $user_id,
				'socket_id' => $this->socket_id,
			]);
			$this->share->redis_Del('sys_messages:'.$user_id);
		}
		return $this->result( [
			'data' => $data,
		]);
	}

	function get_alerts( $raw = null ){
		 $options = $this->get( $raw,['alerts_ids']);

		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		 $add_sql = ' ';
		 if(isset($_alerts_ids) && is_array($_alerts_ids)){
			 unset($_alerts_ids[0]);
			$add_sql = ' and id NOT IN  ('.implode(',',$_alerts_ids).') ';
		}
		$sql = "SELECT id, msg, type, url, end_date
			FROM `e_alerts`
			WHERE `active` = '1' AND  `start_date` < '". date('Y-m-d H:i:s')."'  AND  `end_date` > '". date('Y-m-d H:i:s')."' " .$add_sql . "
			ORDER BY `end_date` asc , id desc ";
		$alerts = db()->get_all($sql);

		return $this->result( [
			'data' => $alerts ? $alerts : 'No alerts',
			'status' => $alerts ? 0 : -1,
		]);

	}


}
