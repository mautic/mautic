<?php
if ( !function_exists('json_decode') ) {

	function json_decode($content, $assoc=false){
		require_once 'JSON.php';
		if ( $assoc ){
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON;
		}
		return $json->decode($content);
	}

}

if ( !function_exists('json_encode') ) {

	function json_encode($content) {
		require_once 'JSON.php';
		$json = new Services_JSON;
		return $json->encode($content);
	}

}

function getUserIP()
{
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];

	if(filter_var($client, FILTER_VALIDATE_IP))
	{
		$ip = $client;
	}
	elseif(filter_var($forward, FILTER_VALIDATE_IP))
	{
		$ip = $forward;
	}
	else
	{
		$ip = $remote;
	}

	return $ip;
}

?>