<?php

class api_handler__share {

	// client: api_server, event, queue, redis
	public $client = null;

	public function _init() {
	}

	public function error() {
		$api_server = &$this->client[ 'api_server' ];
		$result = call_user_func_array( [ $api_server, '_error' ], func_get_args() );
		return( $result );
	}

	public function dump() {
		$api_server = &$this->client[ 'api_server' ];
		$result = call_user_func_array( [ $api_server, '_dump' ], func_get_args() );
		return( $result );
	}

	public function _queue_log() {
		$api_server = &$this->client[ 'api_server' ];
		$result = call_user_func_array( [ $api_server, '_queue_log' ], func_get_args() );
		return( $result );
	}

	public function redis_keys( $name ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$name ) { return( null ); }
		$name = (string)$name;
		$result = $redis->keys( $name );
		return( $result );
	}

	public function redis_Event( $name = null, $data = null ) {
		// var
		$event = &$this->client[ 'event' ];
		$redis = &$this->client[ 'redis' ];
		if( !$event || !$redis || !@$name ) { return( null ); }
		if( isset( $data ) ) {
			$data = @json_encode( $data );
		}
		$result = $redis->lPush( (string)$name, $data );
		$result = $event->pub( (string)$name, $data );
		return( $result );
	}

	public function redis_ttl( $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key ) { return( null ); }
		$result = $redis->ttl( (string)$key );
		return( $result );
	}

	public function redis_expire( $key, $ttl = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key || is_null( $ttl ) ) { return( null ); }
		$result = $redis->expire( (string)$key, (int)$ttl );
		return( $result );
	}

	public function redis_Set( $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key ) { return( null ); }
		$result = $redis->set( (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_Get( $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key ) { return( null ); }
		$result = $redis->get( (string)$key );
		if (!is_null($result)) {
			$orig_result = $result;
			$result = @json_decode( $result, true );
			if (is_null($result) && strlen($orig_result)) {
				$result = $orig_result;
			}
		}
		return( $result );
	}

	public function redis_Del( $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key ) { return( null ); }
		$result = $redis->del( $key );
		return( $result );
	}

	public function redis_hSet( $hash, $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hSet( $hash, (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_hGet( $hash, $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hGet( $hash, (string)$key );
		if (!is_null($result)) {
			$orig_result = $result;
			$result = @json_decode( $result, true );
			if (is_null($result) && strlen($orig_result)) {
				$result = $orig_result;
			}
		}
		return( $result );
	}

	public function redis_hGetAll( $hash ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash ) { return( null ); }
		$result = null;
		$items = $redis->hGetAll( $hash );
		if( is_array( $items ) ) {
			foreach( $items as $id => $item ) {
				if( !is_null( $item ) ) {
					$_item = $item;
					$item = @json_decode( $item, true );
					if( is_null( $item ) && strlen( $_item ) ) {
						$item = $_item;
					}
				}
				$result[ $id ] = $item;
			}
		}
		return( $result );
	}

	public function redis_hDel( $hash, $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hDel( $hash, (string)$key );
		if (!is_null($result)) {
			$orig_result = $result;
			$result = @json_decode( $result, true );
			if (is_null($result) && strlen($orig_result)) {
				$result = $orig_result;
			}
		}
		return( $result );
	}

	public function redis_sAdd( $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key || !@$data ) { return( null ); }
		$result = $redis->sAdd( (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_sRem( $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key || !@$data ) { return( null ); }
		$result = $redis->sRem( (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_sIsMember( $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key || !@$data ) { return( null ); }
		$result = $redis->sIsMember( (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_sMembers( $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$key ) { return( null ); }
		$result = $redis->sMembers( (string)$key );
		if( !@is_array( $result ) ) {
			return( [] );
		}
		foreach( $result as $id => $item ) {
			$orig_item = $item;
			if (!is_null($item)) {
				$orig_item = $item;
				$item = @json_decode( $item, true );
				if (is_null($item) && strlen($orig_item)) {
					$item = $orig_item;
				}
				$result[$id] = $item;
			}
		}
		return( $result );
	}

	public function redis_hExists( $hash, $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hExists( $hash, (string)$key );
		return( $result );
	}

	public function redis_hSetNx( $hash, $key, $data = null ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hSetNx( $hash, (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_hKeys( $hash) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash ) { return( null ); }
		$result = $redis->hKeys( $hash);
		return( $result );
	}

	public function redis_hMSet( $hash, $data) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$data ) { return( null ); }
		foreach($data as $key => $value){
			$data [$key] = @json_encode( $value);
		}
		$result = $redis->hMSet( $hash,  $data );
		return( $result );
	}

	// log

	public function redis_log_hDel( $hash, $key ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hDel( $hash, (string)$key );
		return( $result );
	}

	public function redis_log_hSet( $hash, $key, $data = null ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hSet( $hash, (string)$key, @json_encode( $data ) );
		return( $result );
	}

	public function redis_log_hGet( $hash, $key ) {
		// var
		$redis = &$this->client[ 'redis' ];
		if( !$redis || !@$hash || !@$key ) { return( null ); }
		$result = $redis->hGet( $hash, (string)$key );
		if (!is_null($result)) {
			$orig_result = $result;
			$result = @json_decode( $result, true );
			if (is_null($result) && strlen($orig_result)) {
				$result = $orig_result;
			}
		}
		return( $result );
	}

	public function redis_log_hGetAll( $hash ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$hash ) { return( null ); }
		$result = null;
		$items = $redis->hGetAll( $hash );
		if( is_array( $items ) ) {
			foreach( $items as $id => $item ) {
				if( !is_null( $item ) ) {
					$_item = $item;
					$item = @json_decode( $item, true );
					if( is_null( $item ) && strlen( $_item ) ) {
						$item = $_item;
					}
				}
				$result[ $id ] = $item;
			}
		}
		return( $result );
	}

	public function redis_log( $name = null, $data = null, $ttl = null ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$name || !@$data ) { return( null ); }
		$name = (string)$name;
		$data = @json_encode( $data );
		$result = $redis->lPush( $name, $data );
		if( @$ttl ) {
			$result &= $redis->expire( $name, (int)$ttl );
		}
		return( $result );
	}

	public function redis_log_lRange( $name = null, $from = null, $to = null ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$name ) { return( null ); }
		$name = (string)$name;
		is_null( $from ) && $from = 0;
		is_null( $to   ) && $to   = -1;
		$result = $redis->lRange( $name, (int)$from, (int)$to );
		foreach( $result as &$item ) {
			$item = @json_decode( $item, true );
		}
		return( $result );
	}

	public function redis_log_keys( $name ) {
		// var
		$redis = &$this->client[ 'log' ];
		if( !$redis || !@$name ) { return( null ); }
		$name = (string)$name;
		$result = $redis->keys( $name );
		if( !$result || !is_array( $result ) ) { return( null ); }
		$prefix     = &$redis->prefix;
		$prefix_len = strlen( $prefix );
		if( $prefix_len < 1 ) { return( $result ); }
		foreach( $result as &$r ) {
			if( strpos( $r, $prefix ) === false ) { continue; }
			$r = substr( $r, $prefix_len );
		}
		return( $result );
	}

}
