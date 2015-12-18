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

/**
 * Class AbstractIpLookup
 *
 * @deprecated 1.2.3 - to be removed in 2.0
 *             extend IpLookup instead
 */
abstract class AbstractIpLookup
{
    public $city = '';
    public $region = '';
    public $zipcode = '';
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
        $this->auth      = $auth;
    }

    /**
     * @param $ip
     */
    public function setIpAddress($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        $this->getData();

        return call_user_func('get_object_vars', $this);
    }

    /**
     * @return string
     */
    public function getAttribution()
    {
        return '';
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