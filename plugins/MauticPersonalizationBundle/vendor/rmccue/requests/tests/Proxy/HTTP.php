<?php

class RequestsTest_Proxy_HTTP extends PHPUnit_Framework_TestCase {
	protected function checkProxyAvailable($type = '') {
		switch ($type) {
			case 'auth':
				$has_proxy = defined('REQUESTS_HTTP_PROXY_AUTH') && REQUESTS_HTTP_PROXY_AUTH;
				break;

			default:
				$has_proxy = defined('REQUESTS_HTTP_PROXY') && REQUESTS_HTTP_PROXY;
				break;
		}

		if (!$has_proxy) {
			$this->markTestSkipped('Proxy not available');
		}
	}

	public function transportProvider() {
		return array(
			array('Requests_Transport_cURL'),
			array('Requests_Transport_fsockopen'),
		);
	}

	/**
	 * @dataProvider transportProvider
	 */
	public function testConnectWithString($transport) {
		$this->checkProxyAvailable();

		$options = array(
			'proxy' => REQUESTS_HTTP_PROXY,
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
		$this->assertEquals('http', $response->headers['x-requests-proxied']);

		$data = json_decode($response->body, true);
		$this->assertEquals('http', $data['headers']['x-requests-proxy']);
	}

	/**
	 * @dataProvider transportProvider
	 */
	public function testConnectWithArray($transport) {
		$this->checkProxyAvailable();

		$options = array(
			'proxy' => array(REQUESTS_HTTP_PROXY),
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
		$this->assertEquals('http', $response->headers['x-requests-proxied']);

		$data = json_decode($response->body, true);
		$this->assertEquals('http', $data['headers']['x-requests-proxy']);
	}

	/**
	 * @dataProvider transportProvider
	 * @expectedException Requests_Exception
	 */
	public function testConnectInvalidParameters($transport) {
		$this->checkProxyAvailable();

		$options = array(
			'proxy' => array(REQUESTS_HTTP_PROXY, 'testuser', 'password', 'something'),
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
	}

	/**
	 * @dataProvider transportProvider
	 */
	public function testConnectWithInstance($transport) {
		$this->checkProxyAvailable();

		$options = array(
			'proxy' => new Requests_Proxy_HTTP(REQUESTS_HTTP_PROXY),
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
		$this->assertEquals('http', $response->headers['x-requests-proxied']);

		$data = json_decode($response->body, true);
		$this->assertEquals('http', $data['headers']['x-requests-proxy']);
	}

	/**
	 * @dataProvider transportProvider
	 */
	public function testConnectWithAuth($transport) {
		$this->checkProxyAvailable('auth');

		$options = array(
			'proxy' => array(
				REQUESTS_HTTP_PROXY_AUTH,
				REQUESTS_HTTP_PROXY_AUTH_USER,
				REQUESTS_HTTP_PROXY_AUTH_PASS
			),
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
		$this->assertEquals(200, $response->status_code);
		$this->assertEquals('http', $response->headers['x-requests-proxied']);

		$data = json_decode($response->body, true);
		$this->assertEquals('http', $data['headers']['x-requests-proxy']);
	}

	/**
	 * @dataProvider transportProvider
	 */
	public function testConnectWithInvalidAuth($transport) {
		$this->checkProxyAvailable('auth');

		$options = array(
			'proxy' => array(
				REQUESTS_HTTP_PROXY_AUTH,
				REQUESTS_HTTP_PROXY_AUTH_USER . '!',
				REQUESTS_HTTP_PROXY_AUTH_PASS . '!'
			),
			'transport' => $transport,
		);
		$response = Requests::get(httpbin('/get'), array(), $options);
		$this->assertEquals(407, $response->status_code);
	}
}
