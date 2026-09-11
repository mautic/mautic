<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
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
    private $id;

    #[ORM\ManyToOne(targetEntity: Stat::class)]
    #[ORM\JoinColumn(name: 'stat_id', onDelete: 'CASCADE')]
    private ?Stat $stat = null;

    /**
     * @var LeadDevice|null
     */
    #[ORM\ManyToOne(targetEntity: LeadDevice::class)]
    #[ORM\JoinColumn(name: 'device_id', onDelete: 'CASCADE')]
    private $device;

    private ?IpAddress $ipAddress = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_opened', type: 'datetime')]
    private $dateOpened;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addBigIntIdField();

        $builder->addIpAddress(true);
    }

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

    /**
     * @return LeadDevice|null
     */
    public function getDevice()
    {
        return $this->device;
    }

    public function setDevice(LeadDevice $device): void
    {
        $this->device = $device;
    }
}
