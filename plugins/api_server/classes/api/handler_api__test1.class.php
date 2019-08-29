<?php

class handler_api__test1 extends api_handler__base {

	// *** api pub/sub

	public $channel = null;

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
		$result = $this->_validation( $raw );
		if( $result !== true ) { return( $result ); }
		// input data
		$options = $this->get( $raw, array(
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
		$channel = &$this->channel;
		$result  = $this->__publish([
			'agent'     => 'test1.message',
			'channel'   => $channel,
			'socket_id' => $this->socket_id,
			'message'   => $_message,
		]);
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

}
