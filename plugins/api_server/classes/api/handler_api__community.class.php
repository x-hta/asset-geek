<?php

class handler_api__community extends api_handler__base {

	public $use_base64 = false;

	function users_info($raw = null){
		// input data
		$options = $this->get( $raw , ['user_name']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$options['no_limit'] = true;
		return $this->result( array(
			'data' => $user_info ?: [],
		));
	}

	function get_clan_info($raw = null){
		// input data
		$options = $this->get( $raw , ['clan_name']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		$options['no_limit'] = true;
		return $this->result( array(
			'data' => $clan_info ?: [],
		));
	}

	function get_current_leagues($raw = null){

		$options = $this->get($raw,[]);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );

		$data = _class('community_handler')->_get_current_leagues($options);
		return $this->result( array(
			'data' => $data ?: [],
		));


	}

	function _get_data($raw = null){
		$options = $this->get($raw,['type', 'game_id', 'filter', 'total', 'curr_page','league_id','name_to_search']);
		is_array( $options ) && extract( $options, EXTR_PREFIX_ALL | EXTR_REFS, '' );
		if((!@$_type || !in_array($_type,['l','c','u'])) || (int)@$_game_id == 0){
			return $this->result( array(
				'message'   => t('wrong data'),
				'status' => -1,
			));


		}
		$options['filter_data'] = $_filter;
		$options['per_page'] = 9;

		$info = _class('community_handler')->_get_list_ranks($options);

		if(!$info[0]){
			return $this->result( array(
				'message'   => t('no data'),
				'status' => -1,
			));

		}

		$data = [
			'ranks' => array_values($info[0]),
		//	'ranks' => $info[0],
			'total'   => $info[2],
		];

		return $this->result( array(
			'data' => $data ?: [],
		));


	}

	function get_league_ranks($raw = null){
	//	$raw['total'] = true;
		return $this->_get_data($raw);
	}

	function get_user_ranks($raw = null){
		return $this->_get_data($raw);
	}

	function get_clan_ranks($raw = null){
		return $this->_get_data($raw);
	}


}
