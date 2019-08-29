<?php

class handler_api__rating extends api_handler__base {

	function get_league_data($raw = null){
		// auth
		if( ! $this->is_auth( $raw ) ) { return( $this->auth_result ); }
//		is_array( $raw ) && extract( $raw, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$user_id = (int)$this->user_id;
		$data = _class('rating_handler')->_up_next_league(['user_id' => $user_id, 'only_data' => 1]);
		return $this->result( array(
			'data' => $data,
		));
	}

	function fame_hall($raw = null){
		$data = _class('rating_handler')->_fame_hall();
		return $this->result( array(
			'data' => $data,
		));
	}

	function update_league_data($raw = null){
		return $this->result( array(
			'data' => __FUNCTION__,
		));
	}
}
