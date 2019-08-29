<?php

class handler_api__test extends api_handler__base {

	function rnd( $raw = null ) {
		// input data
		$options = $this->get( $raw, array(
			'value',
		));
		// auth
		$this->is_auth( $raw );
		// if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$_value = @$_value > 0 ? $_value : 100;
		$rnd = mt_rand( 0, $_value );
		// add lang
		_class('i18n')->TR_VARS['en']['user'] = 'User';
		_class('i18n')->TR_VARS['ru']['user'] = 'Пользователь';
		$data = array(
			'rnd'         => $rnd,
			'language_id' => $this->language_id,
			'user'        => t( 'user' ),
			'user_id'     => $this->user_id ?: 'no user',
		);
		// response
		return $this->result( array(
			'data' => $data,
		));
	}

	function rnd_hi( $raw = null ) {
		$result = $this->rnd( $raw );
		return( $result );
	}

	function rnd_mid( $raw = null ) {
		$result = $this->rnd( $raw );
		return( $result );
	}

	function rnd_user( $raw = null ) {
		$result = $this->rnd( $raw );
		return( $result );
	}

	function form( $raw = null ) {
		// input data
		$options = $this->get( $raw, array(
			'is_validation',
			'user',
		));
		// auth
		// if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		// start
		$rules = [
				'js' => [
					'name' => [
						'type'      => 'text',
						'required'  => true,
						'minlength' => 4,
						'maxlength' => 256,
						'pattern'   => '^[a-zA-Zа-яА-Я\s\.\-]+$',
					],
					'tel' => [
						'type'      => 'text',
						'minlength' => 11,
						'maxlength' => 15,
						'pattern'   => '^\d+$',
					],
					'email' => [
						'type'      => 'email',
						'required'  => true,
						'minlength' => 4,
						'maxlength' => 256,
						'pattern'   => '^[a-zA-Z\.\-\_]+@[a-zA-Z\.\-\_]+\.[a-zA-Z\.\-\_]+$',
					],
					'gender' => [
						'pattern'   => '^(male)|(female)$',
					],
				],
				'option' => [
					'name'   => 'required|regex:~^[\pL\pM\s\-\.]+$~u|length[4,256]',
					'tel'    => 'is_natural|length[11,15]',
					'email'  => 'required|email',
					'gender' => 'regex:~^(male)\|(female)$~',
				],
				'message' => [
					'name'   => 'обязательное поле от 4 символов',
					'tel'    => 'поле от 11 цифр, без "+"',
					'email'  => 'обязательное поле, в формате email: name@domain.com',
					'gender' => 'поле, пол: мужской или женский',
				],
			];
		if( @$_is_validation ) {
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
			'data'  => $_user,
		]);
		// response
		return $this->result( array(
			'status' => empty( $error ),
			'data' => [
				'error' => $error ?: null
			],
		));
	}

}
