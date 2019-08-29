<?php

class api_desktop {

	public $user_id  = null;
	public $admin_id = null;

	public $is_user  = false;
	public $is_admin = false;

	public $user_info = null;

	public $session_key = 'api';

	public function _init() {
	}

	public function auth_store( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_token ) { return( null ); }
		// store
		$_SESSION[ $this->session_key ] = array(
			'token' => $_token,
		);
		return( true );
	}

	public function auth_get( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = @$_SESSION[ $this->session_key ] ?: null;
		return( $result );
	}

	public function auth_clear( $options = null ) {
		$auth = $this->auth_get();
		$api_server = _class( 'api_server', 'admin_modules/' );
		$api_server->api_handler->request( 'api', array(
			'name' => 'auth.logout',
			'request' => [
				'token' => @$auth[ 'token' ],
			]
		));
		unset( $_SESSION[ $this->session_key ] );
		return( true );
	}

	function admin( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = null;
		if( @$_id < 1 ) { return( $result ); }
		// get
		$db = db()->from( 'sys_admin' )->where( 'id', (int)$_id );
		if( @$_is_sql ) {
			$result = $db->sql();
		} else {
			$result = $db->get();
		}
		return( $result );
	}

	public function login( $auth_user ) {
		$this->auth_user_get();
		$user_id   = &$this->user_id;
		$admin_id  = &$this->admin_id;
		$is_user   = &$this->is_user;
		$is_admin  = &$this->is_admin;
		$user_info = &$this->user_info;
		// get user data
		$user = null;
		if( $is_user ) {
			$user_info = user( $user_id );
		} elseif( $is_admin ) {
			$user_info = $this->admin([ 'id' => $admin_id ]);
		}
		if( empty( $user_info ) || !is_array( $user_info ) ) {
			$this->logout( null );
			return( false );
		}
		// token
		$api_server = _class( 'api_server', 'admin_modules/' );
		$response = $api_server->api_handler->request( 'api', array(
			'name' => 'auth.login',
			'request' => array(
				'is_desktop'  => true,
				'api_desktop' => &$this,
			)
		));
		$token = @$response['data']['token'];
		// store
		if( $token ) {
			$this->auth_store(array( 'token' => $token ));
		} else {
			$this->logout( null );
		}
		events()->fire( 'api_desktop.login' );
	}

	public function logout( $auth_user ) {
		$this->auth_clear();
		events()->fire( 'api_desktop.logout' );
	}

	public function auth_user_get() {
		$user_id  = &$this->user_id;
		$admin_id = &$this->admin_id;
		$is_user  = &$this->is_user;
		$is_admin = &$this->is_admin;
		// vars
		$user_id  = main()->USER_ID;
		$admin_id = main()->ADMIN_ID;
		$is_user  = $user_id  > 0;
		$is_admin = $admin_id > 0;
	}

	public function auth_check( $auth_user ) {
		$this->auth_user_get();
		$is_user  = &$this->is_user;
		$is_admin = &$this->is_admin;
		if( !$is_user && !$is_admin ) {
		//	$this->logout( $auth_user );
			$this->auth_clear();
			return( false );
		}
		return( true );
	}

}

