<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\CommonEntity;

#[ORM\Entity(repositoryClass: IntegrationRepository::class)]
#[ORM\Table(name: 'plugin_integration_settings')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Integration extends CommonEntity implements CacheInvalidateInterface
{
    public const CACHE_NAMESPACE = 'IntegrationSettings';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var Plugin|null
     */
    #[ORM\ManyToOne(targetEntity: Plugin::class, inversedBy: 'integrations')]
    #[ORM\JoinColumn(name: 'plugin_id', onDelete: 'CASCADE')]
    private $plugin;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_published', type: 'boolean')]
    private $isPublished = false;

    /**
     * @var array
     */
    #[ORM\Column(name: 'supported_features', type: 'array', nullable: true)]
    private $supportedFeatures = [];

    /**
     * @var array
     */
    #[ORM\Column(name: 'api_keys', type: 'array')]
    private $apiKeys = [];

    /**
     * @var array
     */
    #[ORM\Column(name: 'feature_settings', type: 'array', nullable: true)]
    private $featureSettings = [];

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Plugin|null
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @param mixed $plugin
     */
    public function setPlugin($plugin): static
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * @param mixed $isPublished
     */
    public function setIsPublished($isPublished): static
    {
        $this->isChanged('isPublished', $isPublished);

        $this->isPublished = $isPublished;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSupportedFeatures(): array
    {
        return $this->supportedFeatures;
    }

    /**
     * @param mixed $supportedFeatures
     */
    public function setSupportedFeatures($supportedFeatures): static
    {
        $this->isChanged('supportedFeatures', $supportedFeatures);

        $this->supportedFeatures = $supportedFeatures;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getApiKeys()
    {
        return $this->apiKeys;
    }

    /**
     * @param mixed $apiKeys
     */
    public function setApiKeys($apiKeys): static
    {
        $this->apiKeys = $apiKeys;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getFeatureSettings()
    {
        return $this->featureSettings;
    }

    /**
     * @param mixed $featureSettings
     */
    public function setFeatureSettings($featureSettings): static
    {
        $this->isChanged('featureSettings', $featureSettings);

        $this->featureSettings = $featureSettings;

        return $this;
    }

    public function getCacheNamespacesToDelete(): array
    {
        return [self::CACHE_NAMESPACE];
    }
}
