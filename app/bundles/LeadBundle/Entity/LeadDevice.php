<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class LeadDevice.
 */
class LeadDevice
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $clientInfo = [];

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
     * @var string
     */
    private $deviceFingerprint;

    /**
     * @var string
     */
    private $trackingId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_devices')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\LeadDeviceRepository')
            ->addIndex(['date_added'], 'date_added_search')
            ->addIndex(['device'], 'device_search')
            ->addIndex(['device_os_name'], 'device_os_name_search')
            ->addIndex(['device_os_shortname'], 'device_os_shortname_search')
            ->addIndex(['device_os_version'], 'device_os_version_search')
            ->addIndex(['device_os_platform'], 'device_os_platform_search')
            ->addIndex(['device_brand'], 'device_brand_search')
            ->addIndex(['device_model'], 'device_model_search')
            ->addIndex(['device_fingerprint'], 'device_fingerprint_search');

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false);

        $builder->addDateAdded();

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

        $builder->createField('deviceFingerprint', 'string')
            ->columnName('device_fingerprint')
            ->nullable()
            ->build();

        $builder->createField('trackingId', 'string')
            ->columnName('tracking_id')
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
        $metadata->setGroupPrefix('leadDevice')
            ->addProperties(
                [
                    'id',
                    'lead',
                    'clientInfo',
                    'device',
                    'deviceBrand',
                    'deviceModel',
                    'deviceOsName',
                    'deviceOsShortName',
                    'deviceOsVersion',
                    'deviceOsPlatform',
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
     * @return string
     */
    public function getSignature()
    {
        return md5(json_encode($this->clientInfo).$this->device.$this->deviceOsName.$this->deviceOsPlatform.$this->deviceBrand.$this->deviceModel);
    }

    /**
     * @return mixed
     */
    public function getClientInfo()
    {
        return $this->clientInfo;
    }

    /**
     * @param mixed $clientInfo
     */
    public function setClientInfo($clientInfo)
    {
        $this->clientInfo = $clientInfo;
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
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * @return mixed
     */
    public function getDeviceBrand()
    {
        return $this->deviceBrand;
    }

    /**
     * @param mixed $isFailed
     */
    public function setDeviceBrand($brand)
    {
        $this->deviceBrand = $brand;
    }

    /**
     * @return mixed
     */
    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    /**
     * @param mixed $deviceModel
     */
    public function setDeviceModel($deviceModel)
    {
        $this->deviceModel = $deviceModel;
    }

    /**
     * @return string
     */
    public function getDeviceOsName()
    {
        return $this->deviceOsName;
    }

    /**
     * @param string $deviceOsName
     *
     * @return $this
     */
    public function setDeviceOsName($deviceOsName)
    {
        $this->deviceOsName = $deviceOsName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceOsShortName()
    {
        return $this->deviceOsShortName;
    }

    /**
     * @param string $deviceOsShortName
     *
     * @return $this
     */
    public function setDeviceOsShortName($deviceOsShortName)
    {
        $this->deviceOsShortName = $deviceOsShortName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceOsVersion()
    {
        return $this->deviceOsVersion;
    }

    /**
     * @param string $deviceOsVersion
     *
     * @return $this
     */
    public function setDeviceOsVersion($deviceOsVersion)
    {
        $this->deviceOsVersion = $deviceOsVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceOsPlatform()
    {
        return $this->deviceOsPlatform;
    }

    /**
     * @param string $deviceOsPlatform
     *
     * @return $this
     */
    public function setDeviceOsPlatform($deviceOsPlatform)
    {
        $this->deviceOsPlatform = $deviceOsPlatform;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceOs()
    {
        return $this->deviceOsName;
    }

    /**
     * @param mixed $deviceOs
     */
    public function setDeviceOs($deviceOs)
    {
        if (isset($deviceOs['name'])) {
            $this->deviceOsName = $deviceOs['name'];
        }
        if (isset($deviceOs['short_name'])) {
            $this->deviceOsShortName = $deviceOs['short_name'];
        }
        if (isset($deviceOs['version'])) {
            $this->deviceOsVersion = $deviceOs['version'];
        }
        if (isset($deviceOs['platform'])) {
            $this->deviceOsPlatform = $deviceOs['platform'];
        }
    }

    /**
     * @return string
     */
    public function getDeviceFingerprint()
    {
        return $this->deviceFingerprint;
    }

    /**
     * @param string $deviceFingerprint
     */
    public function setDeviceFingerprint($deviceFingerprint)
    {
        $this->deviceFingerprint = $deviceFingerprint;
    }

    /**
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @param string $trackingId
     *
     * @return self
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
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
     * @return $this
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return mixed
     *
     * @deprecated 2.4.0 to be removed 3.0; use getDateAdded instead
     */
    public function getDateOpen()
    {
        return $this->getDateAdded();
    }

    /**
     * @param mixed $dateOpen
     *
     * @deprecated 2.4.0 to be removed 3.0; use setDateAdded instead
     */
    public function setDateOpen($dateOpen)
    {
        $this->setDateAdded($dateOpen);
    }
}
