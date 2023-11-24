<?php

namespace Mautic\CoreBundle\IpLookup;

class IpapiLookup extends AbstractRemoteDataLookup
{
	/**
	 * @return string
	 */
	public function getAttribution()
	{
		return '<a href="https://ipapi.com/" target="_blank">ipapi.com</a> real-time geolocation &
		reverse IP Lookup REST API.';
	}

	/**
	 * @return string
	 */
	protected function getUrl()
	{
		return 'http://api.ipapi.com/'.$this->ip.'?access_key='.$this->auth.'&output=json';
	}

	/**
	 * @param $response
	 */
	protected function parseResponse($response)
	{
		$data = json_decode($response);

		if ($data) {
			foreach ($data as $key => $value) {
				switch ($key) {
					case 'city':
						$key = 'city';
						break;
					case 'region_name':
						$key = 'region';
						break;
					case 'country_name':
						$key = 'country';
						break;
					case 'zip':
						$key = 'zipcode';
						break;
				}

				$this->$key = $value;
			}
		}
	}
}
