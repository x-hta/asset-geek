<?php

class handler_api__auth extends api_handler__base {

	public $SECURITY_CODE = 'cSiMevndrko0ODuDQkt2';

	// error
	const LOGIN_ERROR_EMPTY   = 1;
	const LOGIN_ERROR_FAIL    = 2;
	const LOGIN_ERROR_BLOCKED = 3;
	const LOGIN_ERROR_TOKEN   = 4;
	const LOGIN_ERROR_TYPE    = 5;

	/** @var bool Block failed logins after several attempts (To prevent password bruteforcing, hacking, etc) @security */
	public $BLOCK_FAILED_LOGINS		= false;
	/** @var bool Track failed logins TTL @security */
	public $BLOCK_FAILED_TTL		= 3600;
	/** @var bool @security */
	public $BLOCK_FAILS_BY_LOGIN_COUNT	= 5;
	/** @var string */
	public $USER_SECURITY_CHECKS	= false;
	/** @var string Login field name to use @conf_skip */
	public $LOGIN_FIELD             = 'email';
	/** @var bool Save failed logins @security */
	public $LOG_FAILED_LOGINS		= true;

	private $USER_PASSWORD_SALT = '';

	function _login_user( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$user     = @trim( $_user    );
		$password = @trim( $_password );
		// empty data
		if( !$user || !$password ) {
			$message = t( 'Empty login data' );
			return $this->result( array(
				'status'  => self::LOGIN_ERROR_EMPTY,
				'message' => $message,
			));
		}
		$user_info = &$_user_info;
		// block user
		$fail_reason = false;
		if( false && $this->BLOCK_FAILED_LOGINS ) {
			$min_time = time() - $this->BLOCK_FAILED_TTL;
			$fails_by_login = (int)db()->get_one('SELECT COUNT(*) FROM '.db('log_auth_fails').' WHERE time > '.$min_time.' AND login="'._es($user).'"');
			if( $fails_by_login >= $this->BLOCK_FAILS_BY_LOGIN_COUNT ) {
				$fail_reason = 'blocked_fails_by_login';
				$message = t( 'Attempt to login as %LOGIN blocked', array(
					'%LOGIN' => $user
				));
				return $this->result( array(
					'status'  => self::LOGIN_ERROR_BLOCKED,
					'message' => $message,
				));
			}
		}
		if( !$fail_reason ) {
			$PSWD_OK = false;
			$user_info = $this->_get_user_info( $user );
			// check password
			if(
				strlen($user_info['password']) == 32
				&& md5($password. $this->USER_PASSWORD_SALT) == $user_info['password']
			) {
				$PSWD_OK = true;
			} elseif( $user_info['password'] == $password ) {
				$PSWD_OK = true;
			}
			if (!$PSWD_OK) {
				$fail_reason = 'wrong_login';
			}
		}
		if( !$fail_reason && $this->USER_SECURITY_CHECKS ) {
			// TODO check for api calls
//			$fail_reason = $auth_user->_user_security_checks($user_info['id']);
		}
		if( $fail_reason ) {
			unset($user_info);
			$this->_log_fail(array(
				'login'		=> $user,
				'pswd'		=> $password,
				'reason'	=> $fail_reason,
			));
			$message = t( 'Attempt to login as %LOGIN failed', array(
				'%LOGIN' => $user
			));
			return $this->result( array(
				'status'  => self::LOGIN_ERROR_FAIL,
				'message' => $message,
			));
		}
		return( true );
	}

	function login( $raw = null ) {
		// input data
		$options = $this->get( $raw, array(
			'is_desktop',
			'api_desktop',
			'user',
			'password',
		));
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$user_info = null;
		if( @$_is_desktop ) {
			$api_desktop_class = _class( 'api_desktop' );
			$api_desktop = @$_api_desktop;
			if( $api_desktop instanceof $api_desktop_class ) {
				$user_info = $api_desktop->user_info;
				$user_info[ 'is_desktop' ] = true;
				$user_info[ 'is_user'    ] = $api_desktop->is_user;
				$user_info[ 'is_admin'   ] = $api_desktop->is_admin;
			}
		} else {
			$result = $this->_login_user([ 'user' => $user, 'password' => $password, 'user_info' => $user_info ]);
			if( $result !== true ) { return( $result ); }
		}
		if( !$user_info ) {
			$message = t( 'Fail user info' );
			return $this->result( array(
				'status'  => self::LOGIN_ERROR_FAIL,
				'message' => $message,
			));
		}
		// token
		$user_info[ 'socket_id' ] = @$raw[ 'socket_id' ];
		$token = $this->token_set( $user_info );
		if( !$token ) {
			return $this->result( array(
				'status'  => self::LOGIN_ERROR_TOKEN,
				'message' => t( 'Token generation is fail' ),
			));
		}
		// response
		$data = array(
			'token' => $token,
		);
		return $this->result( array(
			'data' => $data,
		));
	}

	function logout( $raw = null ) {
		// input data
		$options = $this->get( $raw, array(
			'token',
		));
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$result = $this->__auth_clean([ 'token' => @$_token  ]);
		return $this->result( array(
			'data' => $result ? 'ok': 'fail',
		));
	}

	function token( $options = null ) {
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if( ! @$_id ) { return( null ); }
		$data = $this->SECURITY_CODE . $_id . time();
		$result = hash( 'sha256', $data );
		return( $result );
	}

	function token_set( $options = null ) {
		// var
		$token        = &$this->token;
		$socket_id    = &$this->socket_id;
		$is_desktop   = &$this->is_desktop;
		$is_user      = &$this->is_user;
		$is_admin     = &$this->is_admin;
		$user_id      = &$this->user_id;
		$auth         = &$this->auth;
		// token
		$token = $this->token( $options );
		if( !@$token ) { return( null ); }
		// import options
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// store
		$socket_id  = @$_socket_id;
		$is_desktop = (bool)@$_is_desktop;
		$is_user    = (bool)@$_is_user;
		$is_admin   = (bool)@$_is_admin;
		$user_id    = (int)@$_id;
		$auth = array(
			'ts'          => time(),
			'socket_id'   => $socket_id,
			'is_desktop'  => $is_desktop,
			'is_user'     => $is_user,
			'is_admin'    => $is_admin,
			'user_id'     => $user_id,
			'login'       => @$_login,
			'email'       => @$_email,
			'is_vendor'   => @$_is_vendor,
			'is_customer' => @$_is_customer,
		);
		// update
		if( ! $this->__auth_update() ) { return( null ); }
		return( $token );
	}

	/**
	*/
	function _get_user_info ($login = '') {
		if (empty($login)) {
			return false;
		}
		$db = from('user')
			->where( 'email',_es( $login ))
			->where_or( 'login',_es( $login ))
			// ->sql()
		;
		$result = $db->get();
		return( $result );
	}

	/**
	*/
	function _log_fail($data = array()) {
		if (!$this->LOG_FAILED_LOGINS) {
			return false;
		}
		return db()->insert_safe('log_auth_fails', array(
			'time'		=> str_replace(',', '.', microtime(true)),
			'user_id'	=> $data['user_id'],
			'login'		=> $data['login'],
			'pswd'		=> $data['pswd'],
			'reason'	=> $data['reason'],
			'site_id'	=> (int)conf('SITE_ID'),
			'server_id'	=> (int)conf('SERVER_ID'),
		));
	}

}
