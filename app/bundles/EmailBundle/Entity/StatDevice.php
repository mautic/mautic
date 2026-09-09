<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\LeadDevice;

#[ORM\Entity(repositoryClass: StatDeviceRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['date_opened'], name: 'date_opened_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class StatDevice
{
    public const TABLE_NAME = 'email_stats_devices';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: 'Stat')]
    #[ORM\JoinColumn(name: 'stat_id', onDelete: 'CASCADE')]
    private ?Stat $stat = null;

    #[ORM\ManyToOne(targetEntity: LeadDevice::class)]
    #[ORM\JoinColumn(name: 'device_id', onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\LeadDevice $device = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private ?IpAddress $ipAddress = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_opened', type: 'datetime')]
    private $dateOpened;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'device',
                    'ipAddress',
                    'stat',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getIpAddress(): ?IpAddress
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?IpAddress $ip): void
    {
        $this->ipAddress = $ip;
    }

    public function getStat(): ?Stat
    {
        return $this->stat;
    }

    public function setStat(?Stat $stat): void
    {
        $this->stat = $stat;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateOpened()
    {
        return $this->dateOpened;
    }

    /**
     * @param mixed $dateOpened
     */
    public function setDateOpened($dateOpened): void
    {
        $this->dateOpened = $dateOpened;
    }

    public function getDevice(): ?\Mautic\LeadBundle\Entity\LeadDevice
    {
        return $this->device;
    }

    public function setDevice(LeadDevice $device): void
    {
        $this->device = $device;
    }
}
