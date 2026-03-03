<?php

namespace Mautic\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('lead:leads:viewown')"),
        new Get(security: "is_granted('lead:leads:viewown')"),
    ],
    normalizationContext: [
        'groups'                  => ['ipaddress:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['ipaddress:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class IpAddress
{
    public const TABLE_NAME = 'ip_addresses';

    /**
     * Set by factory of configured IPs to not track.
     */
    #[Groups(['ipaddress:read', 'download:read'])]
    private array $doNotTrack = [];

    /**
     * @var int
     */
    #[Groups(['ipaddress:read', 'ipaddress:write', 'download:read'])]
    private $id;

    /**
     * @var mixed[]
     */
    #[Groups(['ipaddress:read', 'ipaddress:write', 'download:read'])]
    private $ipDetails;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(IpAddressRepository::class)
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
        #[Groups(['ipaddress:read', 'ipaddress:write', 'download:read'])]
        private $ipAddress = null,
    ) {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
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
     * @return array<string,string>|null
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
