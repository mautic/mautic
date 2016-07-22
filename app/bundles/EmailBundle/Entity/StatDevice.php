<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
/**
 * Class StatDevice
 *
 * @package Mautic\EmailBundle\Entity
 */
class StatDevice
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Mautic\EmailBundle\Entity\Email
     */
    private $stat;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \DateTime
     */
    private $dateOpened;

    /**
     * @var array
     */
    private $clientInfo = array();

    /**
     * @var string
     */
    private $device;

    /**
     * @var array
     */
    private $deviceOs;

    /**
     * @var string
     */
    private $deviceBrand;

    /**
     * @var string
     */
    private $deviceModel;


    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_stats_device')
            ->setCustomRepositoryClass('Mautic\EmailBundle\Entity\StatDeviceRepository');

        $builder->addId();

        $builder->createManyToOne('stat', 'Mautic\EmailBundle\Entity\Stat')
            ->addJoinColumn('stat_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateOpened', 'datetime')
            ->columnName('date_opened')
            ->build();
        $builder->createField('clientInfo', 'array')
            ->columnName('client_info')
            ->nullable()
            ->build();

        $builder->addNullableField('device', 'string');

        $builder->createField('deviceOs', 'array')
            ->columnName('device_os')
            ->nullable()
            ->build();

        $builder->createField('deviceBrand', 'string')
            ->columnName('device_brand')
            ->nullable()
            ->build();

        $builder->createField('deviceModel', 'string')
            ->columnName('device_model')
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
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                array(
                    'id',
                    'clientInfo',
                    'dateOpened',
                    'device',
                    'deviceBrand',
                    'deviceModel',
                    'deviceOs',
                    'ipAddress',
                    'stat'
                )
            )
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ip
     */
    public function setIpAddress (IpAddress $ip)
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return Lead
     */
    public function getStat ()
    {
        return $this->stat;
    }

    /**
     * @param mixed $stat
     */
    public function setStat (Stat $stat = null)
    {
        $this->stat = $stat;
    }

    /**
     * @return mixed
     */
    public function getDateOpened ()
    {
        return $this->dateOpened;
    }

    /**
     * @param mixed $dateOpened
     */
    public function setDateOpened ($dateOpened)
    {
        $this->dateOpened = $dateOpened;
    }

    /**
     * @return mixed
     */
    public function getClientInfo ()
    {
        return $this->clientInfo;
    }

    /**
     * @param mixed $clientInfo
     */
    public function setClientInfo ($clientInfo)
    {
        $this->clientInfo = $clientInfo;
    }

    /**
     * @return mixed
     */
    public function getDevice ()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice ($device)
    {
        $this->device = $device;
    }

    /**
     * @return mixed
     */
    public function getDeviceBrand ()
    {
        return $this->deviceBrand;
    }

    /**
     * @param mixed $isFailed
     */
    public function setDeviceBrand ($brand)
    {
        $this->deviceBrand = $brand;
    }

    /**
     * @return mixed
     */
    public function getDeviceModel ()
    {
        return $this->deviceModel();
    }


    /**
     * @param mixed $emailAddress
     */
    public function setDeviceModel ($deviceModel)
    {
        $this->deviceModel = $deviceModel;
    }

    /**
     * @return mixed
     */
    public function getDeviceOs ()
    {
        return $this->deviceOs;
    }

    /**
     * @param mixed $viewedInBrowser
     */
    public function setDeviceOs ($deviceOs)
    {
        $this->deviceOs = $deviceOs;
    }
}
