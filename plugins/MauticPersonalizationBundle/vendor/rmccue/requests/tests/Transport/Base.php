<?php

abstract class RequestsTest_Transport_Base extends PHPUnit_Framework_TestCase {
	public function setUp() {
		$callback = array($this->transport, 'test');
		$supported = call_user_func($callback);

		if (!$supported) {
			$this->markTestSkipped($this->transport . ' is not available');
			return;
		}

		$ssl_supported = call_user_func($callback, array('ssl' => true));
		if (!$ssl_supported) {
			$this->skip_https = true;
		}
	}
	protected $skip_https = false;

	protected function getOptions($other = array()) {
		$options = array(
			'transport' => $this->transport
		);
		$options = array_merge($options, $other);
		return $options;
	}

	public function testResponseByteLimit() {
		$limit = 104;
		$options = array(
			'max_bytes' => $limit,
		);
		$response = Requests::get(httpbin('/bytes/325'), array(), $this->getOptions($options));
		$this->assertEquals($limit, strlen($response->body));
	}

	public function testResponseByteLimitWithFile() {
		$limit = 300;
		$options = array(
			'max_bytes' => $limit,
			'filename' => tempnam(sys_get_temp_dir(), 'RLT') // RequestsLibraryTest
		);
		$response = Requests::get(httpbin('/bytes/482'), array(), $this->getOptions($options));
		$this->assertEmpty($response->body);
		$this->assertEquals($limit, filesize($options['filename']));
		unlink($options['filename']);
	}

	public function testSimpleGET() {
		$request = Requests::get(httpbin('/get'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/get'), $result['url']);
		$this->assertEmpty($result['args']);
	}

	public function testGETWithArgs() {
		$request = Requests::get(httpbin('/get?test=true&test2=test'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/get?test=true&test2=test'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['args']);
	}

	public function testGETWithData() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::request(httpbin('/get'), array(), $data, Requests::GET, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/get?test=true&test2=test'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['args']);
	}

	public function testGETWithNestedData() {
		$data = array(
			'test' => 'true',
			'test2' => array(
				'test3' => 'test',
				'test4' => 'test-too',
			),
		);
		$request = Requests::request(httpbin('/get'), array(), $data, Requests::GET, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/get?test=true&test2%5Btest3%5D=test&test2%5Btest4%5D=test-too'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2[test3]' => 'test', 'test2[test4]' => 'test-too'), $result['args']);
	}

	public function testGETWithDataAndQuery() {
		$data = array(
			'test2' => 'test',
		);
		$request = Requests::request(httpbin('/get?test=true'), array(), $data, Requests::GET, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/get?test=true&test2=test'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['args']);
	}

	public function testGETWithHeaders() {
		$headers = array(
			'Requested-At' => time(),
		);
		$request = Requests::get(httpbin('/get'), $headers, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals($headers['Requested-At'], $result['headers']['Requested-At']);
	}

	public function testChunked() {
		$request = Requests::get(httpbin('/stream/1'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/stream/1'), $result['url']);
		$this->assertEmpty($result['args']);
	}

	public function testHEAD() {
		$request = Requests::head(httpbin('/get'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
		$this->assertEquals('', $request->body);
	}

	public function testTRACE() {
		$request = Requests::trace(httpbin('/trace'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
	}

	public function testRawPOST() {
		$data = 'test';
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals('test', $result['data']);
	}

	public function testFormPost() {
		$data = 'test=true&test2=test';
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testPOSTWithArray() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testPOSTWithNestedData() {
		$data = array(
			'test' => 'true',
			'test2' => array(
				'test3' => 'test',
				'test4' => 'test-too',
			),
		);
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2[test3]' => 'test', 'test2[test4]' => 'test-too'), $result['form']);
	}

	public function testRawPUT() {
		$data = 'test';
		$request = Requests::put(httpbin('/put'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals('test', $result['data']);
	}

	public function testFormPUT() {
		$data = 'test=true&test2=test';
		$request = Requests::put(httpbin('/put'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testPUTWithArray() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::put(httpbin('/put'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testRawPATCH() {
		$data = 'test';
		$request = Requests::patch(httpbin('/patch'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals('test', $result['data']);
	}

	public function testFormPATCH() {
		$data = 'test=true&test2=test';
		$request = Requests::patch(httpbin('/patch'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code, $request->body);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testPATCHWithArray() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::patch(httpbin('/patch'), array(), $data, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testOPTIONS() {
		$request = Requests::options(httpbin('/options'), array(), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
	}

	public function testDELETE() {
		$request = Requests::delete(httpbin('/delete'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/delete'), $result['url']);
		$this->assertEmpty($result['args']);
	}

	public function testDELETEWithData() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::request(httpbin('/delete'), array(), $data, Requests::DELETE, $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/delete?test=true&test2=test'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['args']);
	}

	public function testLOCK() {
		$request = Requests::request(httpbin('/lock'), array(), array(), 'LOCK', $this->getOptions());
		$this->assertEquals(200, $request->status_code);
	}

	public function testLOCKWithData() {
		$data = array(
			'test' => 'true',
			'test2' => 'test',
		);
		$request = Requests::request(httpbin('/lock'), array(), $data, 'LOCK', $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}

	public function testRedirects() {
		$request = Requests::get(httpbin('/redirect/6'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$this->assertEquals(6, $request->redirects);
	}

	public function testRelativeRedirects() {
		$request = Requests::get(httpbin('/relative-redirect/6'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$this->assertEquals(6, $request->redirects);
	}

	/**
	 * @expectedException Requests_Exception
	 * @todo This should also check that the type is "toomanyredirects"
	 */
	public function testTooManyRedirects() {
		$options = array(
			'redirects' => 10, // default, but force just in case
		);
		$request = Requests::get(httpbin('/redirect/11'), array(), $this->getOptions($options));
	}

	public static function statusCodeSuccessProvider() {
		return array(
			array(200, true),
			array(201, true),
			array(202, true),
			array(203, true),
			array(204, true),
			array(205, true),
			array(206, true),
			array(300, false),
			array(301, false),
			array(302, false),
			array(303, false),
			array(304, false),
			array(305, false),
			array(306, false),
			array(307, false),
			array(400, false),
			array(401, false),
			array(402, false),
			array(403, false),
			array(404, false),
			array(405, false),
			array(406, false),
			array(407, false),
			array(408, false),
			array(409, false),
			array(410, false),
			array(411, false),
			array(412, false),
			array(413, false),
			array(414, false),
			array(415, false),
			array(416, false),
			array(417, false),
			array(418, false), // RFC 2324
			array(428, false), // RFC 6585
			array(429, false), // RFC 6585
			array(431, false), // RFC 6585
			array(500, false),
			array(501, false),
			array(502, false),
			array(503, false),
			array(504, false),
			array(505, false),
			array(511, false), // RFC 6585
		);
	}

	/**
	 * @dataProvider statusCodeSuccessProvider
	 */
	public function testStatusCode($code, $success) {
		$transport = new MockTransport();
		$transport->code = $code;

		$url = sprintf(httpbin('/status/%d'), $code);

		$options = array(
			'follow_redirects' => false,
			'transport' => $transport,
		);
		$request = Requests::get($url, array(), $options);
		$this->assertEquals($code, $request->status_code);
		$this->assertEquals($success, $request->success);
	}

	/**
	 * @dataProvider statusCodeSuccessProvider
	 */
	public function testStatusCodeThrow($code, $success) {
		$transport = new MockTransport();
		$transport->code = $code;

		$url = sprintf(httpbin('/status/%d'), $code);
		$options = array(
			'follow_redirects' => false,
			'transport' => $transport,
		);

		if (!$success) {
			if ($code >= 400) {
				$this->setExpectedException('Requests_Exception_HTTP_' . $code, '', $code);
			}
			elseif ($code >= 300 && $code < 400) {
				$this->setExpectedException('Requests_Exception');
			}
		}
		$request = Requests::get($url, array(), $options);
		$request->throw_for_status(false);
	}

	/**
	 * @dataProvider statusCodeSuccessProvider
	 */
	public function testStatusCodeThrowAllowRedirects($code, $success) {
		$transport = new MockTransport();
		$transport->code = $code;

		$url = sprintf(httpbin('/status/%d'), $code);
		$options = array(
			'follow_redirects' => false,
			'transport' => $transport,
		);

		if (!$success) {
			if ($code >= 400 || $code === 304 || $code === 305 || $code === 306) {
				$this->setExpectedException('Requests_Exception_HTTP_' . $code, '', $code);
			}
		}
		$request = Requests::get($url, array(), $options);
		$request->throw_for_status(true);
	}

	public function testStatusCodeUnknown(){
		$transport = new MockTransport();
		$transport->code = 599;

		$options = array(
			'transport' => $transport,
		);

		$request = Requests::get(httpbin('/status/599'), array(), $options);
		$this->assertEquals(599, $request->status_code);
		$this->assertEquals(false, $request->success);
	}

	/**
	 * @expectedException Requests_Exception_HTTP_Unknown
	 */
	public function testStatusCodeThrowUnknown(){
		$transport = new MockTransport();
		$transport->code = 599;

		$options = array(
			'transport' => $transport,
		);

		$request = Requests::get(httpbin('/status/599'), array(), $options);
		$request->throw_for_status(true);
	}

	public function testGzipped() {
		$request = Requests::get(httpbin('/gzip'), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body);
		$this->assertEquals(true, $result->gzipped);
	}

	public function testStreamToFile() {
		$options = array(
			'filename' => tempnam(sys_get_temp_dir(), 'RLT') // RequestsLibraryTest
		);
		$request = Requests::get(httpbin('/get'), array(), $this->getOptions($options));
		$this->assertEquals(200, $request->status_code);
		$this->assertEmpty($request->body);

		$contents = file_get_contents($options['filename']);
		$result = json_decode($contents, true);
		$this->assertEquals(httpbin('/get'), $result['url']);
		$this->assertEmpty($result['args']);

		unlink($options['filename']);
	}

	public function testNonblocking() {
		$options = array(
			'blocking' => false
		);
		$request = Requests::get(httpbin('/get'), array(), $this->getOptions($options));
		$empty = new Requests_Response();
		$this->assertEquals($empty, $request);
	}

	/**
	 * @expectedException Requests_Exception
	 */
	public function testBadIP() {
		$request = Requests::get('http://256.256.256.0/', array(), $this->getOptions());
	}

	public function testHTTPS() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::get(httpbin('/get', true), array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		// Disable, since httpbin always returns http
		// $this->assertEquals(httpbin('/get', true), $result['url']);
		$this->assertEmpty($result['args']);
	}

	/**
	 * @expectedException Requests_Exception
	 */
	public function testExpiredHTTPS() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::get('https://testssl-expire.disig.sk/index.en.html', array(), $this->getOptions());
	}

	/**
	 * @expectedException Requests_Exception
	 */
	public function testRevokedHTTPS() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::get('https://testssl-revoked.disig.sk/index.en.html', array(), $this->getOptions());
	}

	/**
	 * Test that SSL fails with a bad certificate
	 *
	 * @expectedException Requests_Exception
	 */
	public function testBadDomain() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::head('https://wrong.host.badssl.com/', array(), $this->getOptions());
	}

	/**
	 * Test that the transport supports Server Name Indication with HTTPS
	 *
	 * badssl.com is used for SSL testing, and the common name is set to
	 * `*.badssl.com` as such. Without alternate name support, this will fail
	 * as `badssl.com` is only in the alternate name
	 */
	public function testAlternateNameSupport() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::head('https://badssl.com/', array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
	}

	/**
	 * Test that the transport supports Server Name Indication with HTTPS
	 *
	 * feelingrestful.com (owned by hmn.md and used with permission) points to
	 * CloudFlare, and will fail if SNI isn't sent.
	 */
	public function testSNISupport() {
		if ($this->skip_https) {
			$this->markTestSkipped('SSL support is not available.');
			return;
		}

		$request = Requests::head('https://feelingrestful.com/', array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
	}

	/**
	 * @expectedException Requests_Exception
	 */
	public function testTimeout() {
		$options = array(
			'timeout' => 1,
		);
		$request = Requests::get(httpbin('/delay/10'), array(), $this->getOptions($options));
		var_dump($request);
	}

	public function testMultiple() {
		$requests = array(
			'test1' => array(
				'url' => httpbin('/get')
			),
			'test2' => array(
				'url' => httpbin('/get')
			),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions());

		// test1
		$this->assertNotEmpty($responses['test1']);
		$this->assertInstanceOf('Requests_Response', $responses['test1']);
		$this->assertEquals(200, $responses['test1']->status_code);

		$result = json_decode($responses['test1']->body, true);
		$this->assertEquals(httpbin('/get'), $result['url']);
		$this->assertEmpty($result['args']);

		// test2
		$this->assertNotEmpty($responses['test2']);
		$this->assertInstanceOf('Requests_Response', $responses['test2']);
		$this->assertEquals(200, $responses['test2']->status_code);

		$result = json_decode($responses['test2']->body, true);
		$this->assertEquals(httpbin('/get'), $result['url']);
		$this->assertEmpty($result['args']);
	}

	public function testMultipleWithDifferingMethods() {
		$requests = array(
			'get' => array(
				'url' => httpbin('/get'),
			),
			'post' => array(
				'url' => httpbin('/post'),
				'type' => Requests::POST,
				'data' => 'test',
			),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions());

		// get
		$this->assertEquals(200, $responses['get']->status_code);

		// post
		$this->assertEquals(200, $responses['post']->status_code);
		$result = json_decode($responses['post']->body, true);
		$this->assertEquals('test', $result['data']);
	}

	/**
	 * @depends testTimeout
	 */
	public function testMultipleWithFailure() {
		$requests = array(
			'success' => array(
				'url' => httpbin('/get'),
			),
			'timeout' => array(
				'url' => httpbin('/delay/10'),
				'options' => array(
					'timeout' => 1,
				),
			),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions());
		$this->assertEquals(200, $responses['success']->status_code);
		$this->assertInstanceOf('Requests_Exception', $responses['timeout']);
	}

	public function testMultipleUsingCallback() {
		$requests = array(
			'get' => array(
				'url' => httpbin('/get'),
			),
			'post' => array(
				'url' => httpbin('/post'),
				'type' => Requests::POST,
				'data' => 'test',
			),
		);
		$this->completed = array();
		$options = array(
			'complete' => array($this, 'completeCallback'),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions($options));

		$this->assertEquals($this->completed, $responses);
		$this->completed = array();
	}

	public function testMultipleUsingCallbackAndFailure() {
		$requests = array(
			'success' => array(
				'url' => httpbin('/get'),
			),
			'timeout' => array(
				'url' => httpbin('/delay/10'),
				'options' => array(
					'timeout' => 1,
				),
			),
		);
		$this->completed = array();
		$options = array(
			'complete' => array($this, 'completeCallback'),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions($options));

		$this->assertEquals($this->completed, $responses);
		$this->completed = array();
	}

	public function completeCallback($response, $key) {
		$this->completed[$key] = $response;
	}

	public function testMultipleToFile() {
		$requests = array(
			'get' => array(
				'url' => httpbin('/get'),
				'options' => array(
					'filename' => tempnam(sys_get_temp_dir(), 'RLT') // RequestsLibraryTest
				),
			),
			'post' => array(
				'url' => httpbin('/post'),
				'type' => Requests::POST,
				'data' => 'test',
				'options' => array(
					'filename' => tempnam(sys_get_temp_dir(), 'RLT') // RequestsLibraryTest
				),
			),
		);
		$responses = Requests::request_multiple($requests, $this->getOptions());

		// GET request
		$contents = file_get_contents($requests['get']['options']['filename']);
		$result = json_decode($contents, true);
		$this->assertEquals(httpbin('/get'), $result['url']);
		$this->assertEmpty($result['args']);
		unlink($requests['get']['options']['filename']);

		// POST request
		$contents = file_get_contents($requests['post']['options']['filename']);
		$result = json_decode($contents, true);
		$this->assertEquals(httpbin('/post'), $result['url']);
		$this->assertEquals('test', $result['data']);
		unlink($requests['post']['options']['filename']);
	}

	public function testAlternatePort() {
		$request = Requests::get('http://portquiz.net:8080/', array(), $this->getOptions());
		$this->assertEquals(200, $request->status_code);
		$num = preg_match('#You have reached this page on port <b>(\d+)</b>#i', $request->body, $matches);
		$this->assertEquals(1, $num, 'Response should contain the port number');
		$this->assertEquals(8080, $matches[1]);
	}

	public function testProgressCallback() {
		$mock = $this->getMockBuilder('stdClass')->setMethods(array('progress'))->getMock();
		$mock->expects($this->atLeastOnce())->method('progress');
		$hooks = new Requests_Hooks();
		$hooks->register('request.progress', array($mock, 'progress'));
		$options = array(
			'hooks' => $hooks,
		);
		$options = $this->getOptions($options);

		$response = Requests::get(httpbin('/get'), array(), $options);
	}

	public function testAfterRequestCallback() {
		$mock = $this->getMockBuilder('stdClass')
			->setMethods(array('after_request'))
			->getMock();

		$mock->expects($this->atLeastOnce())
			->method('after_request')
			->with(
				$this->isType('string'),
				$this->logicalAnd($this->isType('array'), $this->logicalNot($this->isEmpty()))
			);
		$hooks = new Requests_Hooks();
		$hooks->register('curl.after_request', array($mock, 'after_request'));
		$hooks->register('fsockopen.after_request', array($mock, 'after_request'));
		$options = array(
			'hooks' => $hooks,
		);
		$options = $this->getOptions($options);

		$response = Requests::get(httpbin('/get'), array(), $options);
	}

	public function testReusableTransport() {
		$options = $this->getOptions(array('transport' => new $this->transport()));

		$request1 = Requests::get(httpbin('/get'), array(), $options);
		$request2 = Requests::get(httpbin('/get'), array(), $options);

		$this->assertEquals(200, $request1->status_code);
		$this->assertEquals(200, $request2->status_code);

		$result1 = json_decode($request1->body, true);
		$result2 = json_decode($request2->body, true);

		$this->assertEquals(httpbin('/get'), $result1['url']);
		$this->assertEquals(httpbin('/get'), $result2['url']);

		$this->assertEmpty($result1['args']);
		$this->assertEmpty($result2['args']);
	}

	public function testQueryDataFormat() {
		$data = array('test' => 'true', 'test2' => 'test');
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions(array('data_format' => 'query')));
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/post').'?test=true&test2=test', $result['url']);
		$this->assertEquals('', $result['data']);
	}

	public function testBodyDataFormat() {
		$data = array('test' => 'true', 'test2' => 'test');
		$request = Requests::post(httpbin('/post'), array(), $data, $this->getOptions(array('data_format' => 'body')));
		$this->assertEquals(200, $request->status_code);

		$result = json_decode($request->body, true);
		$this->assertEquals(httpbin('/post'), $result['url']);
		$this->assertEquals(array('test' => 'true', 'test2' => 'test'), $result['form']);
	}
}
