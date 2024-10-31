<?php

class P2GoWebApiToken {
	private $token;
	
	/*
		@param: URL, this is the URL starting at action=
		@param: Key, the secret key
		@return: string containing the token.
	*/
	public function create( $url, $key ) {
		//both parameters have values.
		if($url && $key)
		{
			//get the part of the url to create the token.
			$tmpurl = substr($url,strpos($url, 'action=') );
			//create the token and assign it to the private variable
			$this->token = strtoupper(hash_hmac('sha256', $tmpurl, $key) );
			
			//return the token
			
			return $this->token;
			
		//one or both parameters are missing
		} else {
			return 'ERROR: Both parameters have to be filled in at P2GoWebApiToken::create';
		}
	}
	
	/*
		@param: URL, this is the URL starting at action=
		@param: Key, the secret key
		@return: string containing the token.
	*/	
	public function update( $url, $key ) {
		//call the create function internally.
		self::create($url,$key);
	}
	
	/*
		return: void
	*/
	public function clear() {
		$this->token = null;
	}
}

class P2GoWebApiQuery {
	private $query, $db;
	public function __construct($query, $db) {
		$this->query = $query;
		$this->db = $db;
	}
	
	public function doesAlreadyExist() {
		$tmpresult = $this->db->query( $this->db->prepare( "SELECT query FROM " . $this->db->prefix . "p2g_queries WHERE `query` = %s", $this->query) );
		return ($tmpresult==0) ? false : true;
	}
	
	public function getQueryId($query) {
		$tmpresult = $this->db->get_var( $this->db->prepare( "SELECT id FROM " . $this->db->prefix . "p2g_queries WHERE `query` = %s", $this->query) );
		return (int) $tmpresult;
	}
	
	public function getQueryById($id) {
		$this->query = $this->db->get_var( $this->db->prepare( "SELECT query FROM " . $this->db->prefix . "p2g_queries WHERE `id` = %d", $id) );
		$result = $this->query;
		return $result;
	}
	
	public function getQuery() {
		return $this->query;
	}
}

class P2GoWebApi {
	private $params = array();
	private $server_url,$ts, $group, $expired, $key, $lang, $size, $page, $action;
	
	public function __construct($server, $group, $expired, $key, $action) {
		$this->server_url = $server . '/p2g/plugins/Catalogue.aspx';
		$this->group = $group;
		$this->expired = $expired;
		$this->key = $key;
		$this->action = $action;
		$this->lang = 'en';
	}
	
	public function addParams( $keys = array(), $values = array() ) {
		$iterations = sizeof($keys);
		try{
			for( $i=0; $i < $iterations; $i++ ) {
				$this->params[ $keys [ $i ] ] = $values[$i];
			}
			return true;
		} catch( Exception $e) {
			return false;
		}
	}
	
	public function getParams($keys = array() ) {
		$tmparray = array();
		$iterations = sizeof($keys);
		try {
			foreach($this->params as $key => $value) {
					$tmparray[ $key ] = $value;
			}
			return $tmparray;
		} catch( Exception $e) {
			return array();
		}
		//return $this->params;
		
	}
	
	public function updateParams( $keys = array(), $values = array() ) {
		$iterations = sizeof($keys);
		try {
			for( $i = 0; $i < $iterations; $i++) {
				$this->params[ $keys[ $i ] ] = $values [ $i ];
			}
			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
	
	public function removeParams( $keys = array() ) {
		try {
			$iterations = sizeof($keys);
			for( $i = 0; $i < $iterations; $i++ ) {
				unset($this->params[ $keys[ $i ] ] );
			}
			return true;
		} catch( Exception $e) {
			return false;
		}
	}
	
	public function makeRequest( P2GoWebApiToken $token, P2GoWebApiQuery $query ) {
		$date = new DateTime('NOW');
		$ts = $date->format('Y-m-d\TH:i:s');
		$interval = 'PT' . $this->expired . 'H';
		$date->add(new DateInterval($interval));
		$expired = $date->format('Y-m-d\TH:i:s');
		$this->server_url.= '?action=' .  $this->action . '&query=' . str_replace(' ', '+', $query->getQuery() ) . '&group=' . $this->group . '&ts=' . $ts. '&expired=' .$expired;
		if($this->action === 'list')
		{
			$this->server_url.= '&page=' . $this->page;
		}
		if( $this->size ) {
			$this->server_url.= '&size=' . $this->size;
		}
		$this->server_url.= '&token=' . $token->create($this->server_url, $this->key) . '&lang=' . $this->lang;
		$response = file_get_contents($this->server_url);
		return $response;
	}
}