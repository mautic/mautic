<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;


use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Monolog\Logger;

abstract class AbstractIpLookup
{
    public $city = '';
    public $region = '';
    public $country = '';
    public $latitude = '';
    public $longitude = '';
    public $isp = '';
    public $organization = '';
    public $timezone = '';
    public $extra = '';

    /**
     * @var string IP Address
     */
    protected $ip;

    /**
     * Authorization for lookup service
     *
     * @var
     */
    protected $auth;

    /**
     * Connector to obtain data from IP service
     *
     * @var Http
     */
    protected $connector;

    /**
     * Method to use when communicating with the service
     *
     * @var string
     */
    protected $method = 'get';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Populates properties with obtained data from the service
     *
     * @param mixed $response Response from the service
     *
     * @return void
     */
    abstract protected function parseData($response);

    /**
     * Construct
     *
     * @param $ip
     * @param $auth
     * @param $logger
     */
    public function __construct($ip, $auth = null, Logger $logger = null)
    {
        $this->ip        = $ip;
        $this->connector = HttpFactory::getHttp();
        $this->logger    = $logger;
    }

    /**
     * Fetches the data for the given IP address
     *
     * @return $this
     */
    public function getData()
    {
        $url = $this->getUrl();

        try {
            $response = $this->connector->{$this->method}($url);
            $this->parseData($response->body);
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warning('IP LOOKUP: ' . $exception->getMessage());
            }
        }
    }

    /**
     * Get the URL to communicate with
     *
     * @return mixed
     */
    protected function getUrl()
    {
        return '';
    }

}