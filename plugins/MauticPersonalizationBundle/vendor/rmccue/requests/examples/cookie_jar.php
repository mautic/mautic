<?php

// First, include Requests
include('../library/Requests.php');

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

// Say you need to fake a login cookie
$c = new Requests_Cookie_Jar(['login_uid' =>  'something']);

// Now let's make a request!
$request = Requests::get(
	'http://httpbin.org/cookies', // Url
	[],  // No need to set the headers the Jar does this for us
	['cookies' => $c] // Pass in the Jar as an option
);

// Check what we received
var_dump($request);