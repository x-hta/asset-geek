<?php

require_once( __DIR__.DIRECTORY_SEPARATOR. 'api_handler__base.class.php' );

class api_handler {

	public $LOG_ALL_REQUESTS = true;

	public $_class_path = 'classes/';

	public $cache = array();

	public $class_i18n  = null;

	// share objects
	public $share  = null;

	public function _init() {
		$this->class_i18n = _class( 'i18n' );
	}

	public function _exception_handler( $_method_name, $_class, $options, $type, $exception ) {
		$this->share->error( 'handler method '. $_method_name .' with options: '. var_export( $options, true )
			.PHP_EOL. $type .': '. $exception->getMessage()
			.' in file '. $exception->getFile() .':'. $exception->getLine()
			.PHP_EOL .'Stack trace:'. PHP_EOL. $exception->getTraceAsString()
		);
		$result = $_class->result([
			'status'      => -1,
			'status_name' => 'method error',
		]);
		return( $result );
	}

	// simple route: class__sub_class->method
	public function _class( $ns, $class, $method = null, $options = null, $is_server = null ) {
		// log start
		$ts_start = microtime( true );
		!$is_server && $this->share->_queue_log([
			'type'     => 'start',
			'internal' => true,
			'ts_start' => $ts_start,
			'ns'       => $ns,
			'data'     => $options,
		]);
		// var
		$result = null;
		// cache
		$_class_name = $ns .'__'. $class;
		$_class = &$this->cache[ 'class' ][ $_class_name ];
		if( $_class === false ) {
			$this->share->error( 'handler class: '. $_class_name .' is not exists' );
			return( $result );
		}
		if( !is_object( $_class ) ) {
			$_path  = $this->_class_path . $ns .DIRECTORY_SEPARATOR;
			$_class_name_handler = 'handler_'. $_class_name;
			$_class = _class_safe( $_class_name_handler, $_path );
			$status = $_class instanceof $_class_name_handler;
			if( !$status ) {
				$this->cache[ 'class' ][  $_class_name ] = false;
				$this->share->error( 'handler class: '. $_class_name .' is not exists' );
				return( $result );
			}
			$this->cache[ 'class' ][ $_class_name ] = &$_class;
			// inject share objects
			$_class->share = &$this->share;
		}
		$_method_name = $_class_name .'.'. $method;
		if( @$this->cache[ $_method_name ] === false ) {
			$this->share->error( 'handler method: '. $_method_name .' is not exists' );
			return( $result );
		}
		if( !@$this->cache[ $_method_name ] ) {
			$status = method_exists( $_class, $method ) && is_callable( [ $_class, $method ] );
			if( !$status ) {
				$this->cache[ $_method_name ] = false;
				$this->share->error( 'handler method: '. $_method_name .' is not exists' );
				return( $result );
			}
			$this->cache[ $_method_name ] = true;
		}
		// start
		try {
			// set language
			$language_id = @$options[ 'language_id' ];
			if( $language_id ) {
				$_class->language_id = $language_id;
				$this->class_i18n->_set_current_lang( $language_id );
			}
			// run
			$result = $_class->{ $method }( $options );
			// log end
			$ts_end = microtime( true );
			!$is_server && $this->share->_queue_log([
				'type'     => 'end',
				'internal' => true,
				'ts_start' => $ts_start,
				'ts_end'   => $ts_end,
				'ns'       => $ns,
				'data'     => $options,
				'size'     => strlen( @json_encode( $options ) ),
			]);
		} catch ( Throwable $t ) {
			$result = $this->_exception_handler( $_method_name, $_class, $options, 'error', $t );
		} catch ( Exception $e ) {
			$result = $this->_exception_handler( $_method_name, $_class, $options, 'exception', $e );
		}
		return( $result );
	}

	public function request( $ns = 'api', $options = null, $is_server = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// object, action
		if( !@$ns ) { return( null ); }
		@$_name && @list( $class, $method ) = explode( '.', $_name );
		!@$method && $method = 'request';
		// log start
		$ts_start = microtime( true );
		// start
		$response = $this->_class( $ns, $class, $method, $options, $is_server );
		// log end
		$ts_end = microtime( true );
		// log to db
		$this->LOG_ALL_REQUESTS && $this->_log_request([
			'ns'       => $ns,
			'class'    => $class,
			'method'   => $method,
			'request'  => $options,
			'response' => $response,
			'duration' => round($ts_end - $ts_start, 4),
		]);
		return( $response );
	}

	public function _log_request($data = []) {
		if (!$this->LOG_ALL_REQUESTS) {
			return false;
		}
		if (!isset($this->_worker_meta)) {
			$this->_worker_meta = [
				'host' => gethostname(),
				'ip' => current(explode(' ', exec('hostname --all-ip-addresses'))),
				'id' => getmypid(),
			];
		}
		$data += [
			'log_type' => 'log',
			'date' => date('Y-m-d H:i:s'),
			'worker_host' => $this->_worker_meta['host'],
			'worker_ip' => $this->_worker_meta['ip'],
			'worker_id' => $this->_worker_meta['id'],
		];
		if (is_array($data['request'])) {
			$data['websocket_id'] = $data['request']['socket_id'];
			$data['token'] = $data['request']['token'];
			$data['user_id'] = $this->share->redis_hGet('user_by_token', $data['token']);
			$data['user_ip'] = $this->share->redis_hGet('socket_ips', $data['websocket_id']);
			$data['socket_lifetime'] = microtime(true) - ($this->share->redis_hGet('socket_start_times', $data['websocket_id']) / 1000);
		}
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$data[$k] = $v = json_encode($v);
			}
		}
		return db()->insert_safe('sys_log_api_server', $data);
	}
}
