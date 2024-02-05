<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class IpAddress
{
    /**
     * Set by factory of configured IPs to not track.
     */
    private array $doNotTrack = [];

    /**
     * @var int
     */
    private $id;

    /**
     * @var array<string,string>
     */
    private $ipDetails;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('ip_addresses')
            ->setCustomRepositoryClass(\Mautic\CoreBundle\Entity\IpAddressRepository::class)
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
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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
     * @param string|null $ipAddress
     */
    public function __construct(
        private $ipAddress = null
    ) {
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
     * @param array<string,string> $ipDetails
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
     * @return array<string,string>
     */
    public function getIpDetails()
    {
        return $this->ipDetails;
    }

    /**
     * Set list of IPs to not track.
     */
    public function setDoNotTrackList(array $ips): void
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
    public function isTrackable(): bool
    {
        foreach ($this->doNotTrack as $ip) {
            if (str_contains($ip, '/')) {
                // has a netmask range
                // https://gist.github.com/tott/7684443
                [$range, $netmask]     = explode('/', $ip, 2);
                $range_decimal         = ip2long($range);
                $ip_decimal            = ip2long($this->ipAddress);
                $wildcard_decimal      = 2 ** (32 - $netmask) - 1;
                $netmask_decimal       = ~$wildcard_decimal;

                if (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal)) {
                    return false;
                }

                continue;
            }

            if ($ip === $this->ipAddress) {
                return false;
            }

            if (preg_match('/'.str_replace('.', '\\.', $ip).'/', $this->ipAddress)) {
                return false;
            }
        }

        return true;
    }
}
