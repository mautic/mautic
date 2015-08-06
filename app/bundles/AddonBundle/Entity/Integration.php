<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class Integration
 */
class Integration extends CommonEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var Addon
     */
    private $addon;

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
    private $supportedFeatures = array();

    /**
     * @var array
     */
    private $apiKeys = array();

    /**
     * @var array
     */
    private $featureSettings = array();

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('addon_integration_settings')
            ->setCustomRepositoryClass('Mautic\AddonBundle\Entity\IntegrationRepository');

        $builder->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createManyToOne('addon', 'Addon')
            ->inversedBy('integrations')
            ->addJoinColumn('addon_id', 'id', true, false, 'CASCADE')
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

    /**
     * @return mixed
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getApiKeys ()
    {
        return $this->apiKeys;
    }

    /**
     * @param mixed $apiKeys
     */
    public function setApiKeys (array $apiKeys)
    {
        $this->apiKeys = $apiKeys;
    }

    /**
     * @return mixed
     */
    public function getIsPublished ()
    {
        return $this->isPublished;
    }

    /**
     * @param mixed $isPublished
     */
    public function setIsPublished ($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    /**
     * @return mixed
     */
    public function getFeatureSettings ()
    {
        return $this->featureSettings;
    }

    /**
     * @param mixed $featureSettings
     */
    public function setFeatureSettings (array $featureSettings)
    {
        $this->featureSettings = $featureSettings;
    }

    /**
     * @return mixed
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName ($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getSupportedFeatures ()
    {
        return $this->supportedFeatures;
    }

    /**
     * @param mixed $supportedFeatures
     */
    public function setSupportedFeatures ($supportedFeatures)
    {
        $this->supportedFeatures = $supportedFeatures;
    }

    /**
     * @return mixed
     */
    public function getAddon ()
    {
        return $this->addon;
    }

    /**
     * @param mixed $addon
     */
    public function setAddon (Addon $addon)
    {
        $this->addon = $addon;
    }
}