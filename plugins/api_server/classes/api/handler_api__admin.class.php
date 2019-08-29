<?php

class handler_api__admin extends api_handler__base {

	// *** api pub/sub

	public $channel = null;

	const ACCESS_DENIED = -403;

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

	const ERROR_MESSAGE_EMPTY     = 1;
	function publish( $raw = null ) {
		// $result = $this->_validation( $raw );
		// if( $result !== true ) { return( $result ); }
		// input data
		$options = $this->get( $raw, array(
			'socket_id',
			'channel',
			'all',
			'user_id',
			'message',
		));
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// channel
		if( !$_message ) {
			$message = t( 'Empty message' );
			return $this->result( array(
				'status'  => self::ERROR_MESSAGE_EMPTY,
				'message' => $message,
			));
		}
		// start
		$publish_options = [
			'agent'     => 'admin.message',
			'is_admin'  => true,
			'channel'   => @$_channel,
			'socket_id' => @$_socket_id,
			'message'   => $_message,
		];
		if( @$_all ) {
			$publish_options[ 'is_subscribe' ] = false;
		}
		if( @$_user_id > 0 ) {
			$publish_options[ 'user_id' ] = $_user_id;
		}
		if( isset( $_is_admin ) ) {
			$publish_options[ 'is_admin' ] = (bool)$_is_admin;
		}
		$result = $this->__publish( $publish_options );
		if( $result !== true ) { return( $result ); }
		$data = array(
			'channel' => $channel,
		);
		// response
		return $this->result( array(
			'status' => $result,
			'data'   => $data,
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

	function is_access( $raw ) {
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$is_admin = &$this->is_admin;
		if( ! $is_admin ) {
			$message = t( 'Access denied' );
			return $this->result( array(
				'status'  => self::ACCESS_DENIED,
				'message' => $message,
			));
		}
		return( true );
	}

	// *** user

	function get_user_online( $raw = null ) {
		// auth
		$result = $this->is_access( $raw );
		if( $result !== true ) { return( $result ); }
		// redis
		$users  = $this->share->redis_sMembers( 'online_by_user'  );
		$admins = $this->share->redis_sMembers( 'online_by_admin' );
		// data
		$handler_user =_class( 'user_handler'  );
		$users  = @array_values( $handler_user->get_user_info([ 'items' => $users ]) );
		$admins = @array_values( $handler_user->get_user_info([ 'items' => $admins, 'is_admin' => true ]) );
		$result = [
			'users'  => $users,
			'admins' => $admins,
		];
		return( $result );
	}

	// *** log

	function get_log_api_server( $raw = null ) {
		// auth
		$result = $this->is_access( $raw );
		if( $result !== true ) { return( $result ); }
		// options
		$options = $this->get( $raw, array(
			'date',
			'time',
			'period',
			'speed',
		));
		// data
		$handler =_class( 'log_handler', null, [ 'share' => &$this->share ] );
		$log_api_server = $handler->get_log_api_server( $options );
		$result = [
			'log_api_server' => $log_api_server,
		];
		return( $result );
	}

}
