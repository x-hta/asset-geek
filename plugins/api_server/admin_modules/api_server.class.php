<?php

// console
if( is_console() ) {
	ini_set( 'display_errors', true );
	ini_set( 'display_startup_errors', true );
	error_reporting( E_ALL & ~E_NOTICE );
	// $html_errors = ini_get( 'html_errors' );
	ini_set( 'html_errors', 0 );
	// ini_set( 'html_errors', $html_errors );
	// timeout
	ini_set( 'max_execution_time', 0 );
	ini_set( 'default_socket_timeout', -1 );
}

class api_server {

	public $is_server      = true;

	// log
	public $is_log         = true;
	public $log_slow       = 1.0;              // 1.0 [sec.msec]
	public $log_ttl        = 7 * 24 * 60 * 60; // 7 day, sec
	public $is_log_exclude = true;
	public $log_exclude    = [
		// 'api:request' => true,
		'api:request' => [
			'admin.get_log_api_server' => true,
		],
	];
	public $log_key       = 'api-server';
	public $log_key_slow  = 'api-server-slow';
	public $log_key_start = 'api-server-start';

	// server live by time
	public $is_ttl      = true;
	public $ttl_rnd     = 5; // +/- 5%
	public $ttl_value   = 24 * 60 * 60; // 1 day, sec
	public $ttl         = 0;
	// server limit query count
	public $is_limit_query = true;
	public $limit_query    = 100000;

	public $ts_start    = 0;
	public $count_query = 0;

	public $sleep = array(
		'iteration' => 100000,  // 100 msec
		'queue'     => 1000,    //   1 msec
		'event'     => 1000000, //   1 sec
	);

	public $NS = array(
		'api'    => [ 'request'  ],
		'system' => [ 'request'  ],
		'logger' => [ 'response' ],
		'socket' => [ 'response' ],
	);

	public $POOLS = [
		'hi'   => true,
		'mid'  => true,
		'user' => true,
	];

	public $pool = [];

	public $events = null;
	public $queues = null;

	// client: api_server, event, queue, redis, log
	public $client = array();

	public $api_handler        = null;
	public $api_handler__share = null;

	public $is_verbose  = true;
	static $_is_verbose = true;

	public $LOG0_PATH = 'php://stdout';
	public $LOG1_PATH = 'php://stderr';
	public $log0_file = null;
	public $log1_file = null;

	static $is_term   = false;
	public static function _signal( $signo = null, $signinfo = null ) {
		$signo = (int)$signo;
		$message = null;
		switch( $signo ) {
			case SIGTERM:
				self::$is_term = true;
				$message = 'Signal SIGTERM:';
				break;
			case SIGINT:
				self::$is_term = true;
				$message = 'Signal SIGINT:';
				break;
			default:
				$message = 'Signal No:'. $signo .' info: '. var_export( $signinfo, true );
		}
		if( self::$_is_verbose && $message ) {
			echo $message . PHP_EOL;
		}
	}

	public function _init() {
		// server live by time or query count
		$ts             = microtime( true );
		$ttl_rnd        = &$this->ttl_rnd;
		$ttl_value      = &$this->ttl_value;
		$ttl            = &$this->ttl;
		$ts_start       = &$this->ts_start;
		$is_ttl         = &$this->is_ttl;
		$is_limit_query = &$this->is_limit_query;
		$limit_query    = &$this->limit_query;
		$count_query    = &$this->count_query;
		$is_server      = &$this->is_server;
		$is_verbose     = &$this->is_verbose;
		self::$_is_verbose = &$is_verbose;
		// ttl
		if( is_console() ) {
			$is_verbose = true;
			$is_server  = true;
			// $this->_log( 'console mode...' );
			$count_query = 0;
			$ts_start    = $ts;
			$d   = $ttl_value * $ttl_rnd / 100;
			$t1  = $ttl_value - $d;
			$t2  = $ttl_value + $d;
			$ttl = mt_rand( $t1, $t2 );
			// log ttl
			$is_ttl         && $this->_dump( 'api server, ttl: '. (int)$ttl );
			$is_limit_query && $this->_dump( 'api server, limit query: '. $limit_query );
			// signal handler
			pcntl_signal( SIGTERM, 'api_server::_signal' );
			pcntl_signal( SIGINT,  'api_server::_signal' );
		} else {
			$is_verbose     = false;
			$is_server      = false;
			$is_ttl         = false;
			$is_limit_query = false;
		}
		// start
		$this->client[ 'api_server' ] = &$this;
		// var
		$ns     = &$this->NS;
		$pools  = &$this->POOLS;
		$pool   = &$this->pool;
		$queues = &$this->queues;
		$events = &$this->events;
		$client_redis = &$this->client[ 'redis' ];
		$client_queue = &$this->client[ 'queue' ];
		$client_event = &$this->client[ 'event' ];
		// pool
		$this->_get_pool();
		// client
		$client_redis = redis();
		$client_queue = queue();
		$client_event = pubsub();
		if( is_console() ) {
			if( ! $client_redis->connect() ) {
				$this->_fatal( 'redis client connect fail' );
			}
			if( ! $client_queue->connect() ) {
				$this->_fatal( 'queue client connect fail' );
			}
			if( ! $client_event->connect() ) {
				$this->_fatal( 'event client connect fail' );
			}
			$client_event->sub_conf([ Redis::OPT_READ_TIMEOUT => $ttl ]);
		}
		// request log
		$this->_init_log();
		// events, queues
		$events = $this->get_events();
		$queues = $this->get_queues();
		// share objects
		$api_handler        = &$this->api_handler;
		$api_handler__share = &$this->api_handler__share;
		// class
		$api_handler        = _class( 'api_handler'        );
		$api_handler__share = _class( 'api_handler__share' );
		// client: event, queue, redis
		$api_handler__share->client = &$this->client;
		// share to handler
		$api_handler->share = $api_handler__share;
	}

	public function _init_log( $force = false ) {
		$is_log     = &$this->is_log;
		$client_log = &$this->client[ 'log'   ];
		if( $is_log && ( is_console() || $force ) ) {
			$client_log = redis()->factory([ 'name' => 'REDIS_LOG' ]);
			$is_host = $client_log->_get_conf( 'HOST' );
			if( !$is_host ) {
				$this->_error( 'log client REDIS_LOG_HOST is empty' );
				$is_log = false;
			} elseif( ! $client_log->connect() ) {
				$this->_fatal( 'log client connect fail' );
			}
		}
	}

	public function show() {
	}

	public function _log_prepare( $options = null, $type = null ) {
		if( $type == 'log' ) {
			$path = &$this->LOG0_PATH;
			$file = &$this->log0_file;
		} else {
			$path = &$this->LOG1_PATH;
			$file = &$this->log1_file;
		}
		if( !$file ) { $file = fopen( $path, 'a' ); }
		return( $file );
	}

	public function _log( $options = null, $type = null, $code = -1 ) {
		$is_verbose = &$this->is_verbose;
		if( !$is_verbose ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// message
		if( is_string( $options ) ) { $_message = $options; }
		if( ! @$_message ) { return; }
		// type
		@$_type && $type = $_type;
		$type = in_array( $type, array( 'log', 'error', 'fatal' ) ) ? $type : 'log';
		// prepare: datetime, message
		list( $msec, $ts ) = explode( ' ', microtime() );
		$datetime = date( 'Y-m-d H-i-s', $ts );
		$message = sprintf( '[%s %3d] %s%s', $datetime, (int)( $msec*1000 ), $_message, PHP_EOL );
		// out
		$is_server = &$this->is_server;
		// server mode
		if( $is_server ) {
			// file
			$file = $this->_log_prepare( $options, $type );
			fwrite( $file, $message );
			if( $type == 'fatal' ) {
				exit( $code );
			}
		// user mode
		} else {
			$title = 'api server ';
			switch( $type ) {
				case 'error':
					$_type = E_USER_ERROR;
					$title .= 'error';
					break;
				case 'fatal':
					$_type = E_USER_ERROR;
					$title .= 'fatal';
					break;
				default:
					$_type = E_USER_NOTICE;
					$title .= 'notice';
					break;
			}
			$title .= ': ';
			if( $type != 'log' ) {
				trigger_error( $title . $message, $_type );
			}
		}
	}

	public function _dump( $message = null, $var = null ) {
		if( !@$message && !@$var ) { return( false ); }
		!@$message && $message = '';
		$log = $var ? ' - '. var_export( $var, true ) : '';
		$this->_log( $message . $log );
		return( true );
	}

	public function _error( $message = null ) {
		if( !@$message ) { return( false ); }
		$title = 'Error: ';
		$this->_log( $title . $message . PHP_EOL, 'error' );
		return( true );
	}

	public function _fatal( $message = null, $code = -1 ) {
		if( !@$message ) { return( false ); }
		$title = 'Fatal: ';
		$this->_log( $title . $message . PHP_EOL, 'fatal', $code );
		return( true );
	}

	public function _get_pool( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$pools = &$this->POOLS;
		$pool  = &$this->pool;
		if( !is_array( $pools ) ) { return( null ); }
		if( !@$_GET[ 'pool' ] ) { return( null ); }
		// get
		$raw = $_GET[ 'pool' ];
		$items = explode( ',', $raw );
		if( !count( $items ) ) { return( null ); }
		foreach( $items as $item ) {
			$item = trim( $item );
			if( !$item ) { continue; }
			if( $item == 'all' ) {
				$pool = array_keys( $pools );
				break;
			}
			if( !$pools[ $item ] ) { continue; }
			$pool[ $item ] = $item;
		}
		if( !count( $pool ) ) { return( null ); }
		$this->_log( 'pool: '. implode( ', ', $pool ) );
	}


	public function _object_sort( $a, $b ) {
		if( $a == $b ) { return 0; }
		return( ( $a < $b ) ? 1 : -1 );
	}

	public function _get_object( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// var
		$pool   = &$this->pool;
		$_NS    = &$this->NS;
		$ns     = @$_NS && is_array( $_NS ) ? $_NS : null;
		$object = @$_object && is_string( $_object ) && strlen( $_object ) > 0 ? $_object : null;
		if( !@$ns || !@$object ) { return( null ); }
		// handler
		$method = '_'. $object . '_handler';
		$is_method = method_exists( $this, $method );
		if( !$is_method ) { return( null ); }
		// objects list
		$pool_count = is_array( $pool ) ? count( $pool ) : false;
		$result = array();
		foreach( $ns as $name => $types ) {
			if( !is_array( $types ) ) {
				$name = $types;
				$types = [ 'request' ];
			}
			foreach( $types as $type ) {
				$key = $name .':'. $type;
				$priority = 1;
				if( $pool_count ) {
					$priority += $pool_count;
					foreach( $pool as $_pool ) {
						$_key = $key .':'. $_pool;
						$result[ $_key ] = $priority--;
					}
				}
				$result[ $key ] = $priority;
			}
		}
		uasort( $result, [ $this, '_object_sort' ] );
		return( $result );
	}

	public function get_queues() {
		return( $result = $this->_get_object(array( 'object' => 'queue' )) );
	}

	public function get_events() {
		return( $result = $this->_get_object(array( 'object' => 'event' )) );
	}

	// queues
	public function handle_queues( $infinite = true ) {
		// var
		$sleep        = &$this->sleep;
		$queues       = &$this->queues;
		$client_queue = &$this->client[ 'queue' ];
		if( ! @$queues ) {
			$this->_fatal( 'queues list is empty' );
		}
		// listen
		$method = '_queue_handler';
		do {
			$this->_queue_limit();
			foreach( $queues as $key => $_ ) {
				// $this->_log( 'handler queue: '. $key );
				while( $data_raw = $client_queue->get( $key ) ) {
					// start queue
					$r = $this->$method( $key, $data_raw );
				}
				usleep( $sleep[ 'queue' ] );
			}
			usleep( $sleep[ 'iteration' ] );
		} while( $infinite );
	}

	public function _queue_handler( $key, $data ) {
		// var
		$result = true;
		$queues       = &$this->queues;
		$api_handler  = &$this->api_handler;
		$is_server    = &$this->is_server;
		$client_event = &$this->client[ 'event' ];
		if( !$queues[ $key ] ) {
			$this->_error( 'queue: '. $key . ' is unknown' );
			return( false );
		}
		// ns
		@list( $ns, $type ) = explode( ':', $key );
		// request
		$data = @json_decode( $data, true );
		!is_array( $data ) && $data = (array)$data;
		// start
		$ts_start = microtime( true );
		$count_query = &$this->count_query;
		$count_query++;
		$this->_dump( 'queue '. $count_query .': '. $key, $data );
		$this->_queue_log([
			'type'     => 'start',
			'internal' => false,
			'ts_start' => $ts_start,
			'ns'       => $key,
			'data'     => $data,
		]);
		$response = $api_handler->request( $ns, $data, $is_server );
		// end
		$ts_end = microtime( true );
		// response
		$error    = '';
		$data_raw = '';
		if( $type == 'request' ) {
			unset( $data[ 'request' ], $data[ 'response' ] );
			$data[ 'response' ] = $response;
			// response
			$event = $ns .':response';
			$data_raw = @json_encode( $data );
			$r = $client_event->pub( $event, $data_raw );
			if( ! $r ) {
				$error = ' is fail';
				$this->_error( 'emit: '. $event . $error );
				$this->_error( 'emit data json: '. $data_raw );
			}
			$this->_dump( 'emit: '. $event . $error, $response );
		} else {
			if( !is_null( $response ) ) {
				$event = $key;
				$response = @json_encode( $response );
				$this->_dump( 'event response: '. $event, $response );
			}
		}
		$this->_queue_monitor([
			'type'     => 'end',
			'internal' => false,
			'ts_start' => $ts_start,
			'ts_end'   => $ts_end,
			'ns'       => $key,
			'data'     => $data,
			'size'     => strlen( $data_raw ),
		]);
		$this->_queue_limit_count( $options );
		return( $result );
	}

	public function _queue_monitor( $options = null ) {
		$this->_queue_log( $options );
		$this->_queue_limit( $options );
	}

	public function _queue_limit_count( $options = null ) {
		$is_limit_query = &$this->is_limit_query;
		$limit_query    = &$this->limit_query;
		$count_query    = &$this->count_query;
		// limit query
		if( $is_limit_query && $count_query >= $limit_query ) {
			$this->_dump( 'limit query: '. $count_query );
			$this->_dump( 'going to restart' );
			exit( 0 );
		}
	}

	public function _queue_limit_ttl( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// ts
		$ts = null;
		@$_ts_end && $ts = $_ts_end;
		@$_ts     && $ts = $_ts;
		!$ts      && $ts = microtime( true );
		// var
		$ttl            = &$this->ttl;
		$ts_start       = &$this->ts_start;
		$is_ttl         = &$this->is_ttl;
		// ttl
		$_ttl = $ts - $ts_start;
		if( $is_ttl && $_ttl >= $ttl ) {
			$this->_dump( 'limit ttl: '. $_ttl );
			$this->_dump( 'going to restart' );
			exit( 0 );
		}
	}

	public function _queue_limit_signal( $options = null ) {
		// signal
		if( is_console() ) {
			pcntl_signal_dispatch();
			if( self::$is_term ) {
				$this->_dump( 'going to terminate' );
				exit( 0 );
			}
		}
	}

	public function _queue_limit( $options = null ) {
		$this->_queue_limit_ttl( $options );
		$this->_queue_limit_signal( $options );
	}

	public function _queue_log( $options = null ) {
		$result = null;
		$is_log = &$this->is_log;
		if( !$is_log ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$ts_start = &$_ts_start;
		$ts_end   = &$_ts_end;
		$ns       = &$_ns;
		$type     = &$_type;
		$internal = &$_internal;
		$data     = &$_data;
		$size     = &$_size;
		$name     = &$data[ 'name' ];
		// size
		$size     = (int)$size;
		// exclude
		$is_exclude = &$this->is_log_exclude;
		$exclude    = &$this->log_exclude;
		if( $is_exclude && $exclude ) {
			if( @$exclude[ $ns ] ) {
				$_ns = &$exclude[ $ns ];
				if( $_ns === true || $_ns[ $name ] === true ) {
					return( null);
				}
			}
		}
		// var
		$log_slow      = &$this->log_slow;
		$log_ttl       = &$this->log_ttl;
		$log_key       = &$this->log_key;
		$log_key_slow  = &$this->log_key_slow;
		$log_key_start = &$this->log_key_start;
		// start
		$log_data = [
			'ns'       => $ns,
			'name'     => $name,
			'internal' => $internal,
			'ts_start' => $ts_start,
		];
		// end - normal
		$_log_key = $log_key;
		if( $type == 'end' ) {
			$duration = round( $ts_end - $ts_start, 6 );
			$log_data[ 'ts_end'   ] = $ts_end;
			$log_data[ 'duration' ] = $duration;
			$log_data[ 'size'     ] = $size;
			// slow
			if( $duration >= $log_slow ) {
				$_log_key = $log_key_slow;
				$log_data[ 'data' ] = $data;
			}
		}
		// log
		$datetime = date( 'Y-m-d_H-i', $ts_start );
		$msec     = $ts_start * 1000000 - floor( $ts_start ) * 1000000;
		$msec_str = sprintf( '%06d', $msec );
		$datetime_msec  = date( 'Y-m-d_H-i-s', $ts_start ) .'.'. $msec_str;
		$redis_key_start = implode( ':', [ $datetime_msec, $ns, $name ] );
		if( $type == 'start' ) {
			$result = $this->api_handler__share->redis_log_hSet( $log_key_start, $redis_key_start, $log_data );
		} else {
			$this->api_handler__share->redis_log_hDel( $log_key_start, $redis_key_start );
			$redis_key = implode( ':', [ $_log_key, $datetime ] );
			$result = $this->api_handler__share->redis_log( $redis_key, $log_data, $log_ttl );
		}
	return( $result );
	}

	// events
	public function handle_events() {
		// var
		$events       = &$this->events;
		$client_event = &$this->client[ 'event' ];
		if( ! @$events ) {
			$this->_fatal( 'events list is empty' );
		}
		$method = '_event_handler';
		$events_ns = array_keys( $events );
		$events_ns_str = implode( ', ', $events_ns );
		// check queues
		$this->_log( 'handle queues before subscribe: '. $events_ns_str );
		$this->handle_queues( false );
		// event subscribe
		$this->_log( 'subscribe: '. $events_ns_str );
		$client_event->sub( $events_ns, array( $this, $method ) );
		$this->_log( 'subscribe: timeout, exit...' );
	}

	public function _event_handler( $redis = null, $channel = null, $message = null ) {
		$this->_queue_limit();
		// $this->_dump( 'event: '. $channel );
		// var
		@list( $ns ) = explode( ':', $channel );
		if( ! $ns ) { return( -1 ); }
		$result = 0;
		$queue = substr( $channel, strpos( $channel, ':' ) + 1 );
		$client_queue = &$this->client[ 'queue' ];
		// data
		$data_raw = $client_queue->get( $queue );
		$method = '_queue_handler';
		// ( $data_raw === false ) && $this->_error( 'queue '. $channel .' is fail' );
		if( $data_raw ) {
			$this->_dump( 'event: '. $channel );
			// start queue
			$r = $this->$method( $queue, $data_raw );
			$result = 1;
		}
		// check queues
		$this->handle_queues( false );
		return( $result );
	}

	public function _listen() {
		while( true ) {
			// signal
			if( is_console() ) {
				pcntl_signal_dispatch();
				if( self::$is_term ) {
					$this->_dump( 'going to terminate' );
					exit( 0 );
				}
			}
			$this->_dump( 'listen...' );
			usleep( 500 );
		}
	}

}
