<?php

class handler_logger__event extends api_handler__base {

	public $log = [
	];

	function _init(){
	}

	function _field( $options = null ) {
		$result = null;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !@$_key || !@$_type ) { return( $result ); }
		switch( $_type ) {
			case 'int':
				$result = (int)$_value;
				break;
			case 'string':
				$result = (string)$_value;
				break;
			case 'json':
				$result = is_null( $_value ) ? null : @json_encode( $_value );
				break;
			case 'ts':
				$_ts      = (float)( $_value ?: microtime( true ) );
				$_ts_sec  = (int)$_ts;
				$_ts_msec = $_ts - $_ts_sec;
				$ts_msec  = sprintf( '%06d', $_ts_msec * 1000000 );
				$result   = date( 'Y-m-d H:i:s', (int)$_ts ) .'.'. $ts_msec;
				break;
		}
		return( $result );
	}

	function _logger( $options = null ) {
		$result = null;
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( !@$_name || !@$_data ) { return( $result ); }
		$log = &$this->log[ $_name ];
		if( !$log ) { return( $result ); }
		// prepare
		$data = [];
		foreach( $_data as $key => $value ) {
			$type = &$log[ $key ];
			if( !$type ) { continue; }
			$value = $this->_field([
				'key'   => $key,
				'type'  => $type,
				'value' => $value
			]);
			$data[ $key ] = $value;
		}
		if( !$data ) { return( $result ); }
		// db
		$result = db()->insert_safe( $_name, $data );
		// response
		return( $result );
	}

}
