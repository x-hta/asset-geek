<?php

class handler_system__socket extends api_handler__base {

	function startup( $raw = null ) {
		// clean
		$result = $this->__startup();
		$this->share->dump( 'clean: '. ( $result > 0 ) );
		// result
		$result = $this->result( array(
			'status'  => true,
			'message' => 'ok',
		));
		return( $result );
	}

	function connection( $raw = null ) {
		// auth
		$this->is_auth( $raw );
		// input data
		$options = $this->get( $raw, array(
			'ts',
			'socket_id',
		));
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( @$_ts && @$_socket_id && $_socket_id == $this->socket_id ) {
			// online
			$this->share->redis_sAdd( 'online_by_socket_id', $_socket_id );
			$result = $this->result( array(
				'status'  => true,
				'message' => 'ok',
			));
		} else {
			$result = $this->result( array(
				'status'  => false,
				'message' => 'fail',
			));
		}
		return( $result );
	}

	function disconnect( $raw = null ) {
		// auth
		$this->is_auth( $raw );
		// input data
		$options = $this->get( $raw, array(
			'ts',
			'socket_id',
		));
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( @$_ts && @$_socket_id && $_socket_id == $this->socket_id ) {
			$this->__socket_clean([ 'socket_id' => $_socket_id, 'ts' => $_ts ]);
			$this->share->redis_sRem( 'online_by_socket_id', $_socket_id );
			$result = $this->result( array(
				'status'  => true,
				'message' => 'ok',
			));
		} else {
			$result = $this->result( array(
				'status'  => false,
				'message' => 'fail',
			));
		}
		return( $result );
	}

}
