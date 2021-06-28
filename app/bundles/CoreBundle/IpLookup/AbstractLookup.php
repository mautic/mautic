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

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class AbstractLookup
{
    public $city         = '';
    public $region       = '';
    public $zipcode      = '';
    public $country      = '';
    public $latitude     = '';
    public $longitude    = '';
    public $isp          = '';
    public $organization = '';
    public $timezone     = '';
    public $extra        = '';

    protected ?Client $client;

    /**
     * @var string IP Address
     */
    protected $ip;

    /**
     * Authorization for lookup service.
     */
    protected $auth;

    protected ?string $cacheDir;
    protected ?LoggerInterface $logger;

    /**
     * @var mixed
     */
    protected $config;

    /**
     * Return attribution HTML displayed in the configuration UI.
     *
     * @return string
     */
    abstract public function getAttribution();

    /**
     * Executes the lookup of the IP address.
     */
    abstract protected function lookup();

    /**
     * AbstractLookup constructor.
     *
     * @param null $auth
     * @param null $ipLookupConfig
     * @param null $cacheDir
     */
    public function __construct($auth = null, $ipLookupConfig = null, $cacheDir = null, ?LoggerInterface $logger = null, ?Client $client = null)
    {
        $this->cacheDir  = $cacheDir;
        $this->logger    = $logger;
        $this->auth      = $auth;
        $this->config    = $ipLookupConfig;
        $this->client    = $client;
    }

    /**
     * @param $ip
     *
     * @return $this
     */
    public function setIpAddress($ip)
    {
        $this->ip = $ip;

        // Fetch details from the service
        $this->lookup();

        return $this;
    }

    /**
     * Return details of the IP address lookup.
     *
     * @return array
     */
    public function getDetails()
    {
        return [
            'city'         => $this->city,
            'region'       => $this->region,
            'zipcode'      => $this->zipcode,
            'country'      => $this->country,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'isp'          => $this->isp,
            'organization' => $this->organization,
            'timezone'     => $this->timezone,
            'extra'        => $this->extra,
        ];
    }
}
