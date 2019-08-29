<?php

class handler_socket__chat extends api_handler__base {

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

	function message( $raw = null ) {
		$result = $this->_validation( $raw );
		if( $result !== true ) { return( $result ); }
		// start
		if( @$raw[ 'status' ] ) { return( true ); }
		$channel = &$this->channel;
		$result  = $this->__unsubscribe([
			'channel'   => $channel,
			'socket_id' => $this->socket_id
		]);
		return( $result );
	}

}
