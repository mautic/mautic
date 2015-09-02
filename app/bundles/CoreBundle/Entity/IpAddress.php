<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Joomla\Http\HttpFactory;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class IpAddress
 *
 * @package Mautic\CoreBundle\Entity
 */
class IpAddress
{

    /**
     * Set by factory of configured IPs to not track
     *
     * @var array
     */
    private $doNotTrack = array();

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var array
     */
    private $ipDetails;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ip_addresses')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\IpAddressRepository')
            ->addIndex(array('ip_address'), 'ip_search');

        $builder->addId();

        $builder->createField('ipAddress', 'string')
            ->columnName('ip_address')
            ->length(45)
            ->build();

        $builder->createField('ipDetails', 'array')
            ->columnName('ip_details')
            ->nullable()
            ->build();
    }

    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->addProperties(
                array(
                    'id',
                    'ipAddress',
                    'ipDetails'
                )
            )
            ->addGroup('ipAddress')
            ->build();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @param array  $params
     *
     * @return IpAddress
     */
    public function setIpAddress($ipAddress, $params = array())
    {
        $this->ipAddress = $ipAddress;

        $ignoreIps = array('127.0.0.1', '::1');

        if (empty($this->ipDetails) && !in_array($ipAddress, $ignoreIps)) {
            if (!empty($params)) {
                switch ($params['ip_lookup_service']) {
                    case 'telize':
                        $data = $this->getRemoteIpData("http://www.telize.com/geoip/" . $this->getIpAddress());
                        if (is_object($data) && isset($data->city)) {
                            $ipData = array(
                                'city'         => $data->city,
                                'region'       => $data->region,
                                'country'      => $data->country,
                                'latitude'     => $data->latitude,
                                'longitude'    => $data->longitude,
                                'isp'          => (isset($data->isp)) ? $data->isp : '',
                                'organization' => '',
                                'timezone'     => $data->timezone
                            );
                        }

                        break;
                    case 'freegeoip':
                        $data = $this->getRemoteIpData('http://freegeoip.net/json/' . $this->getIpAddress());
                        if (is_object($data)) {
                            $ipData = array(
                                'city'         => $data->city,
                                'region'       => $data->region_name,
                                'country'      => $data->country_name,
                                'latitude'     => $data->latitude,
                                'longitude'    => $data->longitude,
                                'isp'          => '',
                                'organization' => '',
                                'timezone'     => $data->timezone
                            );
                        }
                        break;

                    case 'geobytes':
                        $tags = get_meta_tags(
                            'http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=' . $this->getIpAddress()
                        );

                        if ($tags['city'] != 'Limit Exceeded') {
                            //something is wrong or invalid IP
                            $ipData = array(
                                'city'         => $tags['city'],
                                'region'       => $tags['region'],
                                'country'      => $tags['country'],
                                'latitude'     => $tags['longitude'],
                                'longitude'    => $tags['latitude'],
                                'isp'          => '',
                                'organization' => '',
                                'timezone'     => $tags['timezone']
                            );
                        }
                        break;

                    case 'ipinfodb':
                        $data = $this->getRemoteIpData(
                            "http://api.ipinfodb.com/v3/ip-city/?key={$params['ip_lookup_auth']}&format=json&ip=" . $this->getIpAddress()
                        );
                        if (is_object($data) && $data->statusCode == 'OK') {
                            $ipData = array(
                                'city'         => ucfirst($data->cityName),
                                'region'       => ucfirst($data->regionName),
                                'country'      => ucfirst($data->countryName),
                                'latitude'     => $data->latitude,
                                'longitude'    => $data->longitude,
                                'isp'          => '',
                                'organization' => '',
                                'timezone'     => $data->timezone
                            );
                        }
                        break;

                    case 'geoips':
                        $data = $this->getRemoteIpData(
                            "http://api.geoips.com/ip/{$this->getIpAddress()}/key/{$params['ip_lookup_auth']}/output/json"
                        );
                        if (is_object($data)) {
                            $ipData = array(
                                'city'         => $data->city_name,
                                'region'       => $data->region_name,
                                'country'      => $data->country_name,
                                'latitude'     => $data->latitude,
                                'longitude'    => $data->longitude,
                                'isp'          => '',
                                'organization' => '',
                                'timezone'     => $data->timezone
                            );
                        }
                        break;
                    case 'maxmind_country':
                    case 'maxmind_precision':
                    case 'maxmind_omni':
                        $baseUrl = 'https://'.$params['ip_lookup_auth'].'@geoip.maxmind.com/geoip/v2.0/';
                        if ($params['ip_lookup_service'] == 'maxmind_country') {
                            $url = $baseUrl . 'country/' . $this->getIpAddress();
                        } elseif ($params['ip_lookup_service'] == 'maxmind_precision') {
                            $url = $baseUrl . 'city_isp_org/' . $this->getIpAddress();
                        } elseif ($params['ip_lookup_service'] == 'maxmind_omni') {
                            $url = $baseUrl . 'omni/' . $this->getIpAddress();
                        }

                        $data = $this->getRemoteIpData($url);
                        if (is_object($data)) {
                            $ipData = array(
                                'city'         => $data->city->names->en,
                                'region'       => $data->subdivisions->names->en,
                                'country'      => $data->country->names->en,
                                'latitude'     => $data->location->latitude,
                                'longitude'    => $data->location->longitude,
                                'isp'          => isset($data->traits->isp) ? $data->traits->isp : '',
                                'organization' => isset($data->traits->organization) ? $data->traits->organization : '',
                                'timezone'     => $data->location->time_zone
                            );
                        }
                        break;
                }

                if (empty($ipData)) {
                    $ipData = array(
                        'city'         => '',
                        'region'       => '',
                        'country'      => '',
                        'latitude'     => '',
                        'longitude'    => '',
                        'isp'          => '',
                        'organization' => '',
                        'timezone'     => ''
                    );
                }

                $this->ipDetails = $ipData;
            } else {
                $ipData          = array(
                    'city'         => '',
                    'region'       => '',
                    'country'      => '',
                    'latitude'     => '',
                    'longitude'    => '',
                    'isp'          => '',
                    'organization' => ''
                );
                $this->ipDetails = $ipData;
            }
        }

        return $this;
    }

    /**
     * @param string $url
     * @param bool   $jsondecode
     *
     * @return mixed|string
     */
    private function getRemoteIpData($url, $jsondecode = true)
    {
        static $connector;

        if (empty($connector)) {
            $connector = HttpFactory::getHttp();
        }

        try {
            $response = $connector->get($url);
            $data     = ($jsondecode) ? json_decode($response->body) : $response->body;
        } catch (\Exception $exception) {
            $data = false;
        }

        return $data;
    }

    /**
     * Get ipAddress
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set ipDetails
     *
     * @param string $ipDetails
     *
     * @return IpAddress
     */
    public function setIpDetails($ipDetails)
    {
        $this->ipDetails = $ipDetails;

        return $this;
    }

    /**
     * Get ipDetails
     *
     * @return string
     */
    public function getIpDetails()
    {
        return $this->ipDetails;
    }

    /**
     * Set list of IPs to not track
     *
     * @param array $ips
     */
    public function setDoNotTrackList(array $ips)
    {
        $this->doNotTrack = $ips;
    }

    /**
     * Get list of IPs to not track
     *
     * @return array
     */
    public function getDoNotTrackList()
    {
        return $this->doNotTrack;
    }

    /**
     * Determine if this IP is trackable
     */
    public function isTrackable()
    {
        if (!empty($this->doNotTrack)) {
            foreach ($this->doNotTrack as $ip) {
                if ( strpos( $ip, '/' ) == false ) {
                    if (preg_match('/'.str_replace('.', '\\.', $ip).'/', $this->ipAddress)) {
                        return false;
                    }
                } else {
                    // has a netmask range
                    // https://gist.github.com/tott/7684443
                    list($range, $netmask) = explode('/', $ip, 2);
                    $range_decimal    = ip2long($range);
                    $ip_decimal       = ip2long($ip);
                    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
                    $netmask_decimal  = ~$wildcard_decimal;

                    if ((($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal))) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
