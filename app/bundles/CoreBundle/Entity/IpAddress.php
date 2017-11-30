<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class IpAddress.
 */
class IpAddress
{
    /**
     * Set by factory of configured IPs to not track.
     *
     * @var array
     */
    private $doNotTrack = [];

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
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ip_addresses')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\IpAddressRepository')
            ->addIndex(['ip_address'], 'ip_search');

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
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('ipAddress')
            ->addListProperties(
                [
                    ['ipAddress', 'ip'],
                ]
            )
            ->addProperties(
                [
                    'id',
                    'ipAddress',
                    'ipDetails',
                ]
            )
            ->addGroup('ipAddress', true)
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ipAddress.
     *
     * @param $ipAddress
     *
     * @return $this
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set ipDetails.
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
     * Get ipDetails.
     *
     * @return string
     */
    public function getIpDetails()
    {
        return $this->ipDetails;
    }

    /**
     * Set list of IPs to not track.
     *
     * @param array $ips
     */
    public function setDoNotTrackList(array $ips)
    {
        $this->doNotTrack = $ips;
    }

    /**
     * Get list of IPs to not track.
     *
     * @return array
     */
    public function getDoNotTrackList()
    {
        return $this->doNotTrack;
    }

    /**
     * Determine if this IP is trackable.
     */
    public function isTrackable()
    {
        if (!empty($this->doNotTrack)) {
            foreach ($this->doNotTrack as $ip) {
                if (strpos($ip, '/') == false) {
                    if (preg_match('/'.str_replace('.', '\\.', $ip).'/', $this->ipAddress)) {
                        return false;
                    }
                } else {
                    // has a netmask range
                    // https://gist.github.com/tott/7684443
                    list($range, $netmask) = explode('/', $ip, 2);
                    $range_decimal         = ip2long($range);
                    $ip_decimal            = ip2long($ip);
                    $wildcard_decimal      = pow(2, (32 - $netmask)) - 1;
                    $netmask_decimal       = ~$wildcard_decimal;

                    if ((($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal))) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
