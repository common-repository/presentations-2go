<?php
if($_SERVER['REQUEST_METHOD'] !== "POST")
{
	echo "There went something wrong when requesting this page.";
	die();
}
$RequestData = json_decode($_POST['data']);
require('class-p2go-web-api.php');
require("../../../wp-load.php");
global $wpdb;
	if($RequestData->type == 'query' && $RequestData->method == 'save') {
	
	// check if table was created
	$table_name = $wpdb->prefix."p2g_queries";
       
	if( $wpdb->get_var("show tables like '$table_name'") != $table_name ) {
				$query = "CREATE TABLE " . $table_name . "(
				id integer(10) AUTO_INCREMENT  PRIMARY KEY,
				query varchar(500) NOT NULL
				){$charset_collate};";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta($query);}
	//save query to table	
		$response = $wpdb->query( $wpdb->prepare("INSERT IGNORE INTO ". $wpdb->prefix . "p2g_queries (`query`) VALUES(%s)", $RequestData->query) );
		
		$json_response = new stdclass;
		$json_response->succes = ($response === false) ? false : true;
	}
	elseif($RequestData->type == 'query' && $RequestData->method == 'getIdByQuery') {
		$response_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM " .$wpdb->prefix. "p2g_queries WHERE `query` = %s", $RequestData->query) );
		$json_response = new stdclass;
		$json_response->id = $response_id;
	} elseif($RequestData->type == 'requestlist') {
		date_default_timezone_set('Europe/Berlin');
		$options = (array) get_option('p2go_rich_media_settings');
		$group = $options['group'];
		$token = new P2GoWebApiToken();
		$query = new P2GoWebApiQuery('search', $wpdb);
		$api = new P2GoWebApi($RequestData->server,$group,0, $RequestData->key, 'info');
		$response = json_decode($api->makeRequest($token, $query));
		$json_response = new stdClass;
		$response_filters = array();
		if($response->success) {
			$response_filters = $response->response->filters;
			$json_response->success = true;
		} else {
			$json_response->success = false;
			$json_response->error = true;
		}
		$json_response->response = $response_filters;
		$json_response->key = $RequestData->key;
	}
	echo json_encode($json_response);
?>