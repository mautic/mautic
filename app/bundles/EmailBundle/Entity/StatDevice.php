<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\LeadDevice;

/**
 * Class StatDevice.
 */
class StatDevice
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $stat;

    /**
     * @var \Mautic\LeadBundle\Entity\LeadDevice
     */
    private $device;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \DateTime
     */
    private $dateOpened;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_stats_devices')
            ->setCustomRepositoryClass('Mautic\EmailBundle\Entity\StatDeviceRepository')
            ->addIndex(['date_opened'], 'date_opened_search');

        $builder->addId();

        $builder->createManyToOne('device', 'Mautic\LeadBundle\Entity\LeadDevice')
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function setIpAddress(IpAddress $ip)
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @param Stat
     */
    public function setStat(Stat $stat)
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
    public function setDateOpened($dateOpened)
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
    public function setDevice(LeadDevice $device)
    {
        $this->device = $device;
    }
}
