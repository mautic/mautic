<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

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
 * @package Mautic\LeadBundle\Entity
 */
class StatDevice
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var integer
     */
    private $stat;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;


    /**
     * @var string
     */
    private $channel;

    /**
     * @var integer
     */
    private $channelId;

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
     * @var string
     */
    private $deviceOsName;

    /**
     * @var string
     */
    private $deviceOsShortName;

    /**
     * @var string
     */
    private $deviceOsVersion;

    /**
     * @var string
     */
    private $deviceOsPlatform;

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

        $builder->setTable('lead_stats_devices')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\StatDeviceRepository')
            ->addIndex(['date_opened'], 'date_opened_search')
            ->addIndex(['stat_id'], 'stat_search')
            ->addIndex(['channel', 'channel_id'], 'channel_search')
            ->addIndex(['device'], 'device_search');;

        $builder->addId();

        $builder->addLead(false, 'CASCADE');

        $builder->createField('stat', 'integer')
            ->columnName('stat_id')
            ->nullable()
            ->build();

        $builder->addNullableField('channel', 'string');

        $builder->createField('channelId', 'integer')
            ->columnName('channel_id')
            ->nullable()
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

        $builder->createField('deviceOsName', 'string')
            ->columnName('device_os_name')
            ->nullable()
            ->build();

        $builder->createField('deviceOsShortName', 'string')
            ->columnName('device_os_shortname')
            ->nullable()
            ->build();

        $builder->createField('deviceOsVersion', 'string')
            ->columnName('device_os_version')
            ->nullable()
            ->build();

        $builder->createField('deviceOsPlatform', 'string')
            ->columnName('device_os_platform')
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
                    'deviceOsName',
                    'deviceOsShortName',
                    'deviceOsVersion',
                    'deviceOsPlatform',
                    'ipAddress',
                    'stat',
                    'channel',
                    'channelId'
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
     * @return integer
     */
    public function getStat ()
    {
        return $this->stat;
    }

    /**
     * @param integer $stat
     */
    public function setStat ($stat = null)
    {
        $this->stat = $stat;
    }

    /**
     * @return string
     */
    public function getChannel ()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel ($channel = null)
    {
        $this->channel = $channel;
    }

    /**
     * @return integer
     */
    public function getChannelId ()
    {
        return $this->channelId;
    }

    /**
     * @param integer $channelId
     */
    public function setChannelId ($channelId = null)
    {
        $this->channelId = $channelId;
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
     * @param mixed $deviceModel
     */
    public function setDeviceModel ($deviceModel)
    {
        $this->deviceModel = $deviceModel;
    }

    /**
     * @return string
     */
    public function getDeviceOs ()
    {
        return $this->deviceOsName;
    }

    /**
     * @param mixed $deviceOs
     */
    public function setDeviceOs ($deviceOs)
    {
        $this->deviceOsName = $deviceOs['name'];
        $this->deviceOsShortName = $deviceOs['short_name'];
        $this->deviceOsVersion = $deviceOs['version'];
        $this->deviceOsPlatform = $deviceOs['platform'];
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     *
     * @return Hit
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
