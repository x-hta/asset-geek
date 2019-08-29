<?php

class handler_api__chat extends api_handler__base {

    public $channel = null;

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
        
        list ($channel_agent, $channel_name, $chat_key, $chat_obj_name, $chat_obj_id) = explode(".", $_channel);
		// channel
		if( !$_channel ) {
			$message = t( 'Empty channel' );
			return $this->result( array(
				'status'  => self::ERROR_CHANNEL_EMPTY,
				'message' => $message,
			));
		}
        if ($channel_agent != 'chat' || $channel_name != 'updates' || _class('chat_api')->check_key($chat_obj_id, $chat_obj_name, $chat_key) === FALSE) {
			$message = t( 'Access denied' );
			return $this->result( array(
				'status'  => self::ERROR_ACCESS_DENIED,
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

    function get_items($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }        
        
        $options = $this->get( $raw,['key', 'obj_id', 'obj_name', 'offset']);
        $items = _class('chat_api')->get_items($options);
		if(!$items){
			return $this->result( [
				'status' => -1,
			]);
		} 
		return $this->result( array(
			'data' => array(
                'items'   => $items,
			),
			'status' => 0,
		));        
    }

	function send_message($raw = null){ 
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
        
        $options = $this->get( $raw,['key', 'message', 'obj_id', 'obj_name']);
        module('chat')->_api_message($this->user_id, $options);
        
        $this->__publish([
            'agent'     => 'chat.message',
            'channel'   => "chat.updates." . $options['key'] . "." . $options['obj_name'] . ".". $options['obj_id'],
          //  'socket_id' => $this->socket_id,
			'is_subscribe' => false,
            'message'   => [ 'type' => 'update'], 
        ]);
        
		return $this->result( array(
			'data' => array(
                'options' => $options,
            ),
		));        
	}

}
