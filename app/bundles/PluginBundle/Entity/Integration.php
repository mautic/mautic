<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\CommonEntity;

class Integration extends CommonEntity implements CacheInvalidateInterface
{
    public const CACHE_NAMESPACE = 'IntegrationSettings';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Plugin|null
     */
    private $plugin;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isPublished = false;

    /**
     * @var array
     */
    private $supportedFeatures = [];

    /**
     * @var array
     */
    private $apiKeys = [];

    /**
     * @var array
     */
    private $featureSettings = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_integration_settings')
            ->setCustomRepositoryClass(IntegrationRepository::class);

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createManyToOne('plugin', 'Plugin')
            ->inversedBy('integrations')
            ->addJoinColumn('plugin_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addField('name', 'string');

        $builder->createField('isPublished', 'boolean')
            ->columnName('is_published')
            ->build();

        $builder->createField('supportedFeatures', 'array')
            ->columnName('supported_features')
            ->nullable()
            ->build();

        $builder->createField('apiKeys', 'array')
            ->columnName('api_keys')
            ->build();

        $builder->createField('featureSettings', 'array')
            ->columnName('feature_settings')
            ->nullable()
            ->build();
    }

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
     * @return Integration
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Integration
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * @return Integration
     */
    public function setIsPublished($isPublished)
    {
        $this->isChanged('isPublished', $isPublished);

        $this->isPublished = $isPublished;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function getSupportedFeatures()
    {
        return $this->supportedFeatures;
    }

    /**
     * @return Integration
     */
    public function setSupportedFeatures($supportedFeatures)
    {
        $this->isChanged('supportedFeatures', $supportedFeatures);

        $this->supportedFeatures = $supportedFeatures;

        return $this;
    }

    public function getApiKeys()
    {
        return $this->apiKeys;
    }

    /**
     * @return Integration
     */
    public function setApiKeys($apiKeys)
    {
        $this->apiKeys = $apiKeys;

        return $this;
    }

    public function getFeatureSettings()
    {
        return $this->featureSettings;
    }

    /**
     * @return Integration
     */
    public function setFeatureSettings($featureSettings)
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
