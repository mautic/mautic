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
use JMS\Serializer\Annotation as Serializer;
use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class IpAddress
 *
 * @ORM\Entity(repositoryClass="Mautic\CoreBundle\Entity\IpAddressRepository")
 * @ORM\Table(name="ip_addresses")
 * @ORM\HasLifecycleCallbacks
 * @Serializer\ExclusionPolicy("all")
 */
class IpAddress
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"ipAddress"})
     */
    private $ipAddress;

    /**
     * @var array
     *
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"ipAddress"})
     */
    private $ipDetails;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ip_addresses')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\IpAddressRepository');

        $builder->addId();

        $builder->createField('ipAddress', 'string')
            ->columnName('ip_address')
            ->length(15)
            ->build();

        $builder->createField('ipDetails', 'array')
            ->columnName('ip_details')
            ->nullable()
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
}
