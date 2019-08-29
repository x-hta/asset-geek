<?php

class api_handler__base {

	public $share       = null;

	public $token       = null;
	public $socket_id   = null;

	public $language_id = null;

	public $is_desktop  = null;
	public $is_user     = null;
	public $is_admin    = null;
	public $user_id     = null;

	public $auth        = null;
	public $auth_result = null;

	public function request( $options = null ){
		return( null );
	}

	public function get( $raw = null, $value = null ) {
		// get data
		$result = array();
		if( !is_array( $raw ) || !is_array( $value ) ) { return( $result ); }
		switch( true ) {
			case is_array( $raw[ 'data'     ] ): $key = 'data';     break;
			case is_array( $raw[ 'request'  ] ): $key = 'request';  break;
			case is_array( $raw[ 'response' ] ): $key = 'response'; break;
			default: return( $result ); break;
		}
		$data = &$raw[ $key ];
		foreach( $value as $v ) {
			if( isset( $data[ $v ] ) ) {
				$result[ $v ] = $data[ $v ];
			}
		}
		return( $result );
	}

	public function result( $options = null ){
		// status  - option code
		//           0 - success
		//          -N - internal error
		//          +N - api error
		// message - user message
		// data    - user data
		//
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// default
		$status = 0;
		$result = array(
			'status'  => &$status,
		);
		// prepare result
		isset( $_status  ) && $status = $_status;
		isset( $_status_name ) && $result[ 'status_name' ] = &$_status_name;
		isset( $_message     ) && $result[ 'message'     ] = &$_message;
		isset( $_data        ) && $result[ 'data'        ] = &$_data;
		return( $result );
	}

	// error
	const AUTH_FAIL    = -1;
	const AUTH_REQUIRE = 1;
	public function is_auth( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$token        = &$this->token;
		$socket_id    = &$this->socket_id;
		$is_desktop   = &$this->is_desktop;
		$is_user      = &$this->is_user;
		$is_admin     = &$this->is_admin;
		$user_id      = &$this->user_id;
		$auth         = &$this->auth;
		$auth_result  = &$this->auth_result;
		// start
		$socket_id    = $_socket_id;
		$user_id      = null;
		if( !@$_token ) {
			$result = $this->__auth_error( array(
				'status'  => self::AUTH_REQUIRE,
			));
			return( $result );
		}
		$token = (string)$_token;
		$result = $this->share->redis_hGet( 'token', $token );
		// fail
		if( ! $result ) {
			$result = $this->__auth_error( array(
				'status'  => self::AUTH_REQUIRE,
			));
			return( $result );
		}
		// ok
		$token       = $token;
		$is_desktop  = (bool)@$result[ 'is_desktop' ];
		$is_user     = (bool)@$result[ 'is_user' ];
		$is_admin    = (bool)@$result[ 'is_admin' ];
		$user_id     = (int)$result[ 'user_id' ];
		$auth        = $result;
		$auth_result = null;
		// update
		if( ! $this->__auth_update() ) { return( null ); }
		return( true );
	}

	protected function __auth_error( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$auth        = &$this->auth;
		$auth_result = &$this->auth_result;
		switch( @$_status ) {
			case self::AUTH_REQUIRE:
				$status_name = 'Auth.require';
				$message     = t( 'Auth require' );
				break;
			case self::AUTH_FAIL:
			default:
				$_status     = self::AUTH_FAIL;
				$status_name = 'Auth.fail';
				$message     = t( 'Auth fail' );
				break;
		}
		$auth = null;
		$auth_result = $this->result( array(
			'status'      => $_status,
			'status_name' => $status_name,
			'message'     => $message,
		));
		return( false );
	}

	protected function __ts( $options = null ) {
		$result = (int)( microtime( true ) * 100 );
		return( $result );
	}

	protected function __auth_update( $options = null ) {
		// var
		$token        = &$this->token;
		$socket_id    = &$this->socket_id;
		$is_desktop   = &$this->is_desktop;
		$is_user      = &$this->is_user;
		$is_admin     = &$this->is_admin;
		$user_id      = &$this->user_id;
		$auth         = &$this->auth;
		if( $user_id < 1 ) {
			return( null );
		}
		$ts = $this->__ts();
		$auth[ 'ts'        ] = $ts;
		$auth[ 'socket_id' ] = $socket_id;
		$is_online = $this->share->redis_sIsMember( 'online_by_user', $user_id );
		if( !$is_online && !$is_admin ) {
			events()->fire( 'api.user_online', [ $user_id ] );
		}
		$this->share->redis_hSet( 'token', $token, $auth );
		$this->share->redis_hSet( 'token_by_socket', $socket_id, $token );
		$type = $is_admin ? 'admin' : 'user';
		$this->share->redis_hSet( $type .'_by_token',   $token,     $user_id   );
		$this->share->redis_hSet( 'token_by_'. $type,   $user_id,   $token     );
		$this->share->redis_hSet( $type .'_by_socket',  $socket_id, $user_id   );
		$this->share->redis_sAdd( 'socket_by_'. $type .':'. $user_id,   $socket_id );
		// online
		$this->share->redis_sAdd( 'online_by_'. $type,      $user_id );
		$this->share->redis_hSet( 'online_by_'. $type .'_last', $user_id, $ts );
		return( true );
	}

	protected function __auth_clean( $options = null ) {
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$token        = &$this->token;
		$socket_id    = &$this->socket_id;
		$is_desktop   = &$this->is_desktop;
		$is_user      = &$this->is_user;
		$is_admin     = &$this->is_admin;
		$user_id      = &$this->user_id;
		$auth         = &$this->auth;
		// override
		@$_token && $token = $_token;
		if( !$token && @$_is_user && @$_user_id ) {
			$token = $this->share->redis_hGet( 'token_by_user', $user_id );
		}
		if( !$token && @$_is_admin && @$_user_id ) {
			$token = $this->share->redis_hGet( 'token_by_admin', $user_id );
		}
		if( !$auth ) {
			$auth = $this->share->redis_hGet( 'token', $token );
			if( $auth ) {
				$is_desktop = @$auth[ 'is_desktop' ];
				$is_user    = @$auth[ 'is_user'    ];
				$is_admin   = @$auth[ 'is_admin'   ];
				$user_id    = @$auth[ 'user_id'   ];
			}
		}
		$type = $is_admin ? 'admin' : 'user';
		!$user_id && $user_id = $this->share->redis_hGet( $type .'_by_token',  $token );
		// start
		$ts = $this->__ts();
		if( $token ) {
			$this->share->redis_hDel( 'token',         $token );
			$this->share->redis_hDel( $type .'_by_token', $token );
		}
		if( $user_id ) {
			$this->share->redis_hDel( 'token_by_'. $type,  $user_id );
			$this->share->redis_Del( 'socket_by_'. $type .':'. $user_id );
			// online
			$this->share->redis_sRem( 'online_by_'. $type, $user_id );
			$this->share->redis_hSet( 'online_by_'. $type .'_last', $user_id, $ts );
			if( !$is_admin ) {
				events()->fire( 'api.user_offline', [ $user_id ] );
			}
		}
		if( $socket_id ) {
			$this->share->redis_hDel( $type .'_by_socket', $socket_id );
			$this->share->redis_hDel( 'token_by_socket', $socket_id );
		}
		// clean socket_by_user
		$socket_ids = $this->share->redis_sMembers( 'socket_by_'. $type .':'. $user_id );
		if( is_array( $socket_ids ) ) {
			foreach( $socket_ids as $idx => $_socket_id ) {
				$this->share->redis_hDel( $type .'_by_socket', $_socket_id );
				$this->share->redis_hDel( 'token_by_socket', $socket_id );
			}
		}
		return( true );
	}

	protected function __socket_clean_subscriber( $options = null ) {
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( @!$_socket_id ) { return( null ); }
		// var
		$socket_id = $_socket_id;
		$channels = $this->share->redis_sMembers( 'channels_by_socket:'. $socket_id );
		if( empty( $channels ) ) { return( null ); }
		foreach( $channels as $channel ) {
			$this->__unsubscribe([ 'channel' => $channel, 'socket_id' => $socket_id ]);
		}
	}

	protected function __socket_clean_by_user( $options = null ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !@$_socket_id || !@$_user_id || !( @$_is_user || @$_is_admin ) ) { return( false ); }
		$socket_id = $_socket_id;
		$user_id   = $_user_id;
		$is_user   = @$_is_user;
		$is_admin  = @$_is_admin;
		$type = $is_admin ? 'admin' : 'user';
		$this->share->redis_hDel( 'token_by_socket', $socket_id );
		$this->share->redis_hDel( $type .'_by_socket', $socket_id );
		$this->share->redis_sRem( 'socket_by_'. $type .':'. $user_id, $socket_id );
		// clean socket_by_user
		$socket_ids = $this->share->redis_sMembers( 'socket_by_'. $type .':'. $user_id );
		if( !$socket_ids ) {
			$ts = @$_ts;
			!$ts && $ts = $this->__ts();
			$this->share->redis_sRem( 'online_by_'. $type, $user_id );
			$this->share->redis_hSet( 'online_by_'. $type .'_last', $user_id, $ts );
			if( !$is_admin ) {
				events()->fire( 'api.user_offline', [ $user_id ] );
			}
		}
		return( true );
	}

	protected function __socket_clean( $options = null ) {
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$socket_id = $_socket_id;
		// clean subscriber
		$this->__socket_clean_subscriber([ 'socket_id' => $socket_id ]);
		// clean user
		$user_id = $this->share->redis_hGet( 'user_by_socket',  $socket_id );
		$auth = $this->share->redis_hGet( 'token_by_socket', $token );
		if( $auth ) {
			$is_desktop = @$auth[ 'is_desktop' ];
			$is_user    = @$auth[ 'is_user'    ];
			$is_admin   = @$auth[ 'is_admin'   ];
			$user_id    = @$auth[ 'user_id'   ];
		}
		if( !$user_id ) { return( false ); }
		if( $is_admin ) {
			$result = $this->__socket_clean_by_user([ 'socket_id' => $socket_id, 'user_id' => $user_id, 'is_admin' => true ]);
		} else {
			$result = $this->__socket_clean_by_user([ 'socket_id' => $socket_id, 'user_id' => $user_id, 'is_user' => true ]);
		}
		return( $result );
	}

	protected function __startup( $raw = null ) {
		$result = $this->share->redis_Del([
			// 'token', 'user_by_token', 'token_by_user',
			// 'online_by_user', 'online_last',
			'socket_by_admin',
			'socket_by_user',
			'user_by_socket',
			'admin_by_socket',
			'online_by_socket_id'
		]);
		return( $result );
	}

	/**
	 * in:
	 *     rules  - validation rules
	 *     data   - validation data
	 * out:
	 *     errors by fields
	 *     or empty array on success validation
	 * exapmle:
	 *     $error = $this->_validate([
	 *       'rules' => $rules,
	 *       'data'  => $profile_form,
	 *     ]);
	 */
	protected function validate( $options ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = [];
		if( !@$_rules[ 'option' ] ) { return( $result ); }
		// validate
		$validate = _class( 'validate' );
		// start
		foreach( $_rules[ 'option' ] as $field => $rule ) {
			// skip: empty validator
			if( empty( $rule ) ) { continue; }
			// processor
			$value = @$_data[ $field ];
			$r = $validate->_input_is_valid( $value, $rule );
			if( !$r ) {
				$message = @$_rules[ 'message' ][ $field ] ?: t( 'Неверное поле' );
				$result[ $field ] = t( $message );
			}
		}
		return( $result );
	}

	protected function __subscribe( $options ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// validate
		if( !( @$_channel && @$_socket_id ) ) { return( null ); }
		// start
		$ts = $this->__ts();
		$channel     = 'channel-subscribe:'.     $_channel;
		$channel_ts  = 'channel-subscribe-ts:'.  $_channel;
		$channel_ttl = 'channel-subscribe-ttl:'. $_channel;
		$this->share->redis_sAdd( 'channels_by_socket:'. $_socket_id, $_channel );
		$this->share->redis_sAdd( $channel, $_socket_id );
		$this->share->redis_Set( $channel_ts, $ts );
		if( !is_null( $_ttl ) ) {
			$ttl = (int)$_ttl;
			// save ttl
			$this->share->redis_Set( $channel_ttl, $ttl );
			$this->share->redis_expire( $channel_ttl, $ttl );
			$this->share->redis_expire( $channel_ts,  $ttl );
			$this->share->redis_expire( $channel,     $ttl );
		}
		$result = true;
		return( $result );
	}

	protected function __unsubscribe( $options ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// validate
		if( !( @$_channel && @$_socket_id ) ) { return( null ); }
		// start
		$channel = 'channel-subscribe:'. $_channel;
		$this->share->redis_sRem( $channel, $_socket_id );
		$this->share->redis_sRem( 'channels_by_socket:'. $_socket_id, $_channel );
		// cleanup
		$subscribers = $this->share->redis_sMembers( $channel );
		if( empty( $subscribers ) ) {
			$this->__del_channel([ 'channel' => $_channel ]);
		}
		$result = true;
		return( $result );
	}

	protected function __del_channel( $options ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// validate
		if( !( @$_channel ) ) { return( null ); }
		// start
		$channel     = 'channel-subscribe:'.     $_channel;
		$channel_ts  = 'channel-subscribe-ts:'.  $_channel;
		$channel_ttl = 'channel-subscribe-ttl:'. $_channel;
		$result = $this->share->redis_Del([ $channel, $channel_ts, $channel_ttl ]);
		return( $result );
	}

	const ERROR_PUBLISH_CHANNEL_NOT_EXISTS = 'p1';
	const ERROR_PUBLISH_NO_SUBSCRIBED      = 'p2';
	const ERROR_SUBSCRIBERS_EMPTY          = 'p3';
	protected function __publish( $options ) {
		// import
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// validate
		$is_subscribe = isset( $_is_subscribe ) ? $_is_subscribe : true;
		$is_all       = isset( $_is_all       ) ? $_is_all       : false;
		$is_all && $is_subscribe = false;
		!@$is_subscribe && $_socket_id = true;
		if( !( @$_channel && @$_socket_id && @$_message ) ) { return( null ); }
		// start
		$channel     = 'channel-subscribe:'.     $_channel;
		$channel_ts  = 'channel-subscribe-ts:'.  $_channel;
		$channel_ttl = 'channel-subscribe-ttl:'. $_channel;
		// validation
		$ts = $this->share->redis_Get( $channel_ts );
		// no channel
		if( $ts < 1 ) {
			$message = t( 'Channel is not exists' );
			return $this->result( array(
				'status'  => self::ERROR_PUBLISH_CHANNEL_NOT_EXISTS,
				'message' => $message,
			));
		}
		if( @$is_subscribe ) {
			// no subscribe
			$status = $this->share->redis_sIsMember( $channel, $_socket_id );
			if( !$status ) {
				$message = t( 'No subscribed to channel' );
				return $this->result( array(
					'status'  => self::ERROR_PUBLISH_NO_SUBSCRIBED,
					'message' => $message,
				));
			}
		}
		// *** emit message to channel
		// get subscribers
		$subscribers = $this->share->redis_sMembers( $channel );
		if( empty( $subscribers ) ) {
			$message = t( 'Subscribers list is empty' );
			return $this->result( array(
				'status'  => self::ERROR_SUBSCRIBERS_EMPTY,
				'message' => $message,
			));
		}
		// by users
		$user_id = null;
		@$_user_id && $user_id = $_user_id;
		// only to admin(s)
		if( @$_is_admin ) {
			$admins = $this->share->redis_sMembers( 'online_by_admin' );
			// get sockets by users
			if( !$user_id ) {
				$user_id = array_intersect( $admins, (array)$user_id );
			} else {
				$user_id = $admins;
			}
		}
		// only to user(s)
		if( $user_id ) {
			$users = (array)$user_id;
			$sockets = [];
			// get sockets by users
			foreach( $users as $id ) {
				$id = (int)$id;
				if( $id < 1 ) { continue; }
				$socket_by_user = $this->share->redis_sMembers( 'socket_by_user:'. $id );
				if( empty( $socket_by_user ) ) { continue; }
				$sockets += $socket_by_user;
			}
			// get sockets by users
			if( !empty( $sockets ) ) {
				$subscribers = array_intersect( $subscribers, $sockets );
			}
		}
		if( empty( $subscribers ) ) {
			$message = t( 'Subscribers list by user_id is empty' );
			return $this->result( array(
				'status'  => self::ERROR_SUBSCRIBERS_EMPTY,
				'message' => $message,
			));
		}
		// update ttl
		$is_ttl = (int)$this->share->redis_ttl( $channel_ttl );
		if( $ttl > 0 ) {
			$ts = $this->__ts();
			$this->share->redis_Set( $channel_ts, $ts );
			$this->share->redis_expire( $channel_ttl, $ttl );
			$this->share->redis_expire( $channel_ts,  $ttl );
			$this->share->redis_expire( $channel,     $ttl );
		}
		// no channel
		$event = 'socket:request';
		$agent = @$_agent ?: 'publish.message';
		foreach( $subscribers as $key => $socket_id ) {
			$data = [
				'name'   => $agent,
				'status' => 0,
				'response' => [
					'channel' => $_channel,
					'message' => $_message,
				],
				'socket_id' => $socket_id,
			];
			$this->share->redis_Event( $event, $data );
		}
		return( true );
	}

}
