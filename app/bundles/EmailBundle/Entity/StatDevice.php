<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\LeadDevice;

class StatDevice
{
    public const TABLE_NAME = 'email_stats_devices';
    /**
     * @var string
     */
    private $id;

    private ?Stat $stat;

    /**
     * @var LeadDevice|null
     */
    private $device;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     */
    private $dateOpened;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(StatDeviceRepository::class)
            ->addIndex(['date_opened'], 'date_opened_search');

        $builder->addBigIntIdField();

        $builder->createManyToOne('device', LeadDevice::class)
            ->addJoinColumn('device_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('stat', 'Stat')
            ->addJoinColumn('stat_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateOpened', 'datetime')
            ->columnName('date_opened')
            ->build();
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

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ip
     */
    public function setIpAddress(IpAddress $ip): void
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
     * @return mixed
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
     * @return mixed
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice(LeadDevice $device): void
    {
        $this->device = $device;
    }
}
