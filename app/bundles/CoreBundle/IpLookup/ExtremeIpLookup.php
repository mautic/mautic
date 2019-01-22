<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

class ExtremeIpLookup extends AbstractRemoteDataLookup
{
    /**
     * Return attribution HTML displayed in the configuration UI.
     *
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://extreme-ip-lookup.com/" target="_blank">extreme-ip-lookup.com</a> is a free lookup service that does not require an api key.';
    }

    /**
     * Get the URL to fetch data from.
     *
     * @return string
     */
    protected function getUrl()
    {
        $auth = !empty($this->auth) ? '?key='.$this->auth : '';

        return 'https://extreme-ip-lookup.com/json/'.$this->ip.$auth;
    }

    /**
     * @param $response
     */
    protected function parseResponse($response)
    {
        $data = json_decode($response, true);

        if ($data) {
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'region':
                        $key = 'region';
                        break;
                    case 'country':
                        $key = 'country';
                        break;
                    case 'city':
                        $key = 'city';
                        break;
                    case 'businessName':
                        $key = 'organization';
                        break;
                }

                $this->$key = $value;
            }
        }
    }
}
