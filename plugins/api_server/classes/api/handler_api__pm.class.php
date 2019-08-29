<?php

class handler_api__pm extends api_handler__base {

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
        if ($_channel != 'pm.updates.' . ($this->is_admin ? 1 : $this->user_id)) {
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

	function get_contacts($raw = null){
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }

		$num_unread = _class('pm_handler')->_get_num_unread($this->is_admin ? 1 : $this->user_id);
		$contacts = _class('pm_handler')->_get_contacts($this->is_admin ? 1 : $this->user_id);
		return $this->result( [
			'data' => [
                'num_unread' => $num_unread,
                'contacts' => $contacts,
            ],
		]);
	}

    function get_thread($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$options = $this->get( $raw,['thread_id', 'offset']);

        $thread = _class('pm_handler')->_get_thread($this->is_admin ? 1 : $this->user_id, $options['thread_id'], 0, $options['offset']);

        foreach ((array)explode("_", $options['thread_id']) as $user_id) {
            if ($user_id != ($this->is_admin ? 1 : $this->user_id)) {
                $result = $this->__publish([
        			'agent'     => 'pm.message',
                    'channel'   => "pm.updates." . $user_id,
                    'socket_id' => $this->socket_id,
                    'message'   => ['type' => 'read', 'thread_id' => $options['thread_id']],
                ]);
            }
        }
        $this->__publish([
            'agent'     => 'notifications.message',
            'channel'   => "notifications.updates." . ($this->is_admin ? 1 : $this->user_id),
            'socket_id' => $this->socket_id,
            'message'   => [ 'type' => 'update'],
        ]);

		return $this->result( [
			'data' => [
                'thread_id' => $options['thread_id'],
                'thread' => $thread,
            ],
		]);
    }

    function search_users($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$options = $this->get( $raw,['q']);

        $result = _class('pm_handler')->_search_users($this->is_admin ? 1 : $this->user_id, $options['q']);
        if (empty($result)) $result = [];

		return $this->result( [
			'data' => [
                'result' => $result,
            ],
		]);
    }
    
    function get_latest_thread_id($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		return $this->result( [
			'data' => [
                'result' => $result,
            ],
		]);
    }

    function search_messages($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$options = $this->get( $raw,['q']);

        $result = _class('pm_handler')->_search_messages($this->is_admin ? 1 : $this->user_id, $options['q']);
        if (empty($result)) $result = [];

		return $this->result( [
			'data' => [
                'result' => $result,
            ],
		]);
    }

    function answer_form($raw = null) {
        if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		$is_banned = common()->_is_banned($this->user_id);
		if($is_banned){
			return 	 $this->result( array(
				'status' => -3,
				'data' => [
					'error' => 'Вы забаненны',
				],
			));

		}
		$options = $this->get( $raw, array(
			'is_validation',
			'form_data',
            'thread_id',
		));

		// auth
		// if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$rules = [
				'js' => [
					'message' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 1,
						'maxlength' => 1024,
					],
				],
				'option' => [
					'message'   => 'required|length[1,1024]',
				],
				'message' => [
					'message'   => t('Введите сообщение'),
				],
			];
		if( $options['is_validation'] ) {
			unset( $rules[ 'option' ] );
			// response
			return $this->result( array(
				'data' => [
					'is_validation' => $rules,
				],
			));
		}
		$error = $this->validate([
			'rules' => $rules,
			'data'  => $options['form_data'],
		]);
        $message_added = false;

		if (empty( $error )) {
            $message_added = _class('pm_handler')->_send_message($this->is_admin ? 1 : $this->user_id, $options['thread_id'], $options['form_data']['message']);
        }
       
        if ($message_added !== false) {
            foreach ((array)explode("_", $options['thread_id']) as $user_id) {
                $options['publish_result'][] = $this->__publish([
        			'agent'     => 'pm.message',
                    'channel'   => "pm.updates." . $user_id,
                    'socket_id' => $this->socket_id,
                    'message'   => [ 'type' => 'update'],
                    'is_subscribe' => false,
                ]);
                if ($user_id != ($this->is_admin ? 1 : $this->user_id)) {
                    $this->__publish([
                        'agent'     => 'notifications.message',
                        'channel'   => "notifications.updates." . $user_id,
                        'socket_id' => $this->socket_id,
                        'message'   => [ 'type' => 'update'],
                        'is_subscribe' => false,                        
                    ]);
                }
            }
        }

		// response
		$validate_result = $this->result( array(
			'status' => empty( $error ),
			'data' => [
				'error' => $error ?: null,
                'message_added' => $message_added,
                'options' => $options,
			],
		));
        return $validate_result;
    }

}
