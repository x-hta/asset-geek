<?php

class handler_api__notifications extends api_handler__base {

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
		// channel
		if( !$_channel ) {
			$message = t( 'Empty channel' );
			return $this->result( array(
				'status'  => self::ERROR_CHANNEL_EMPTY,
				'message' => $message,
			));
		}
        if ($_channel != 'notifications.updates.' . $this->user_id) {
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

	function check($raw = null){
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }

		return $this->result( [
			'data' => [
                'pm_count'              => _class('pm_handler')->_get_num_unread($this->user_id),
                'season_points'         => _class('points_handler')->_get($this->user_id,0),
                'current_season'        => _class('seasons_handler')->_get_current(),
                'notifications_count'   => _class('notifications_handler')->_get_num_unread($this->user_id),
            ],
		]);
	}

    function read_all($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
        db()->update(db('user_relation_log'), [
            'read' => 1,
        ], "`user_id`=".$this->user_id);
        $this->__publish([
            'agent'     => 'notifications.message',
            'channel'   => "notifications.updates." . $this->user_id,
            'socket_id' => $this->socket_id,
            'message'   => [ 'type' => 'update'],
        ]);
        return $this->result([
            'data' => [],
        ]);
    }

    function get_items($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$options = $this->get( $raw,['offset']);
		conf( 'language', $this->language_id );
        list($items, $has_more_items) = _class('notifications_handler')->_get_items($this->user_id, $options['offset']);

        $this->__publish([
            'agent'     => 'notifications.message',
            'channel'   => "notifications.updates." . $this->user_id,
            'socket_id' => $this->socket_id,
            'message'   => [ 'type' => 'update'],
        ]);

		return $this->result( [
			'data' => [
                'has_more_items' => $has_more_items,
                'items' => $items,
                'offset' => $options['offset'],
            ],
		]);
	}

}
