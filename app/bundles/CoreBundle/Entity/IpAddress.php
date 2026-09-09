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
#[ORM\Entity(repositoryClass: IpAddressRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['ip_address'], name: 'ip_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
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
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var mixed[]
     */
    #[Groups(['ipaddress:read', 'ipaddress:write', 'download:read'])]
    #[ORM\Column(name: 'ip_details', type: 'array', nullable: true)]
    private $ipDetails;

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

    public function __construct(
        #[Groups(['ipaddress:read', 'ipaddress:write', 'download:read'])] #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
        private ?string $ipAddress = null,
    ) {
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @param array<string,string> $ipDetails
     */
    public function setIpDetails($ipDetails): static
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
     */
    public function getDoNotTrackList(): array
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

                if (($ip_decimal & $netmask_decimal) === ($range_decimal & $netmask_decimal)) {
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
