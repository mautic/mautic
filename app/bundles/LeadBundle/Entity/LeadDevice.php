<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

#[ORM\Entity(repositoryClass: LeadDeviceRepository::class)]
#[ORM\Table(name: 'lead_devices')]
#[ORM\Index(columns: ['date_added'], name: 'date_added_search')]
#[ORM\Index(columns: ['device'], name: 'device_search')]
#[ORM\Index(columns: ['device_os_name'], name: 'device_os_name_search')]
#[ORM\Index(columns: ['device_os_shortname'], name: 'device_os_shortname_search')]
#[ORM\Index(columns: ['device_os_version'], name: 'device_os_version_search')]
#[ORM\Index(columns: ['device_os_platform'], name: 'device_os_platform_search')]
#[ORM\Index(columns: ['device_brand'], name: 'device_brand_search')]
#[ORM\Index(columns: ['device_model'], name: 'device_model_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadDevice
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'client_info', type: 'array', nullable: true)]
    private $clientInfo = [];

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $device;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_os_name', type: 'string', length: 191, nullable: true)]
    private $deviceOsName;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_os_shortname', type: 'string', length: 191, nullable: true)]
    private $deviceOsShortName;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_os_version', type: 'string', length: 191, nullable: true)]
    private $deviceOsVersion;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_os_platform', type: 'string', length: 191, nullable: true)]
    private $deviceOsPlatform;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_brand', type: 'string', length: 191, nullable: true)]
    private $deviceBrand;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'device_model', type: 'string', length: 191, nullable: true)]
    private $deviceModel;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tracking_id', type: 'string', length: 191, nullable: true, unique: true)]
    private $trackingId;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getSignature(): string
    {
        return md5(json_encode($this->clientInfo).$this->device.$this->deviceOsName.$this->deviceOsPlatform.$this->deviceBrand.$this->deviceModel);
    }

    /**
     * @return array<mixed>
     */
    public function getClientInfo()
    {
        return $this->clientInfo;
    }

    /**
     * @param mixed $clientInfo
     */
    public function setClientInfo($clientInfo): void
    {
        $this->clientInfo = $clientInfo;
    }

    /**
     * @return string|null
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice($device): void
    {
        $this->device = $device;
    }

    /**
     * @return string|null
     */
    public function getDeviceBrand()
    {
        return $this->deviceBrand;
    }

    public function setDeviceBrand($brand): void
    {
        $this->deviceBrand = $brand;
    }

    /**
     * @return string|null
     */
    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    /**
     * @param mixed $deviceModel
     */
    public function setDeviceModel($deviceModel): void
    {
        $this->deviceModel = $deviceModel;
    }

    /**
     * @return string|null
     */
    public function getDeviceOsName()
    {
        return $this->deviceOsName;
    }

    /**
     * @param string $deviceOsName
     */
    public function setDeviceOsName($deviceOsName): static
    {
        $this->deviceOsName = $deviceOsName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceOsShortName()
    {
        return $this->deviceOsShortName;
    }

    /**
     * @param string $deviceOsShortName
     */
    public function setDeviceOsShortName($deviceOsShortName): static
    {
        $this->deviceOsShortName = $deviceOsShortName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceOsVersion()
    {
        return $this->deviceOsVersion;
    }

    /**
     * @param string $deviceOsVersion
     */
    public function setDeviceOsVersion($deviceOsVersion): static
    {
        $this->deviceOsVersion = $deviceOsVersion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceOsPlatform()
    {
        return $this->deviceOsPlatform;
    }

    /**
     * @param string $deviceOsPlatform
     */
    public function setDeviceOsPlatform($deviceOsPlatform): static
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
     * @param array<string, mixed>|null $deviceOs
     */
    public function setDeviceOs(?array $deviceOs): void
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
     * @return string|null
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @param string $trackingId
     */
    public function setTrackingId($trackingId): static
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

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded): void
    {
        $this->dateAdded = $dateAdded;
    }
}
