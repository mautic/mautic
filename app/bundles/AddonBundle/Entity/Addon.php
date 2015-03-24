<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class Addon
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Addon extends CommonEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $isEnabled = true;

    /**
     * @var bool
     */
    private $isMissing = false;

    /**
     * @var string
     */
    private $bundle;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $author;

    /**
     * @var ArrayCollection
     */
    private $integrations;

    public function __construct ()
    {
        $this->integrations = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('addons')
            ->setCustomRepositoryClass('Mautic\AddonBundle\Entity\AddonRepository')
            ->addUniqueConstraint(array('bundle'), 'unique_bundle');

        $builder->addIdColumns();

        $builder->createField('isEnabled', 'boolean')
            ->columnName('is_enabled')
            ->build();

        $builder->createField('isMissing', 'boolean')
            ->columnName('is_missing')
            ->build();

        $builder->createField('bundle', 'string')
            ->length(50)
            ->build();

        $builder->createField('version', 'string')
            ->nullable()
            ->build();

        $builder->createField('author', 'string')
            ->nullable()
            ->build();

        $builder->createOneToMany('integrations', 'Integration')
            ->setIndexBy('id')
            ->mappedBy('addon')
            ->fetchExtraLazy()
            ->build();
    }

    /**
     * @return void
     */
    public function __clone ()
    {
        $this->id = null;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Addon
     */
    public function setName ($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return Addon
     */
    public function setIsEnabled ($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled ()
    {
        return $this->isEnabled;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     *
     * @return Addon
     */
    public function setBundle ($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Get bundle
     *
     * @return string
     */
    public function getBundle ()
    {
        return $this->bundle;
    }

    /**
     * Check the publish status of an entity based on publish up and down datetimes
     *
     * @return string published|unpublished
     */
    public function getPublishStatus ()
    {
        return $this->getIsEnabled() ? 'published' : 'unpublished';
    }

    /**
     * @return mixed
     */
    public function getIntegrations ()
    {
        return $this->integrations;
    }

    /**
     * @return mixed
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription ($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getVersion ()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion ($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getIsMissing ()
    {
        return $this->isMissing;
    }

    /**
     * @param mixed $isMissing
     */
    public function setIsMissing ($isMissing)
    {
        $this->isMissing = $isMissing;
    }

    /**
     * @return mixed
     */
    public function getAuthor ()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor ($author)
    {
        $this->author = $author;
    }
}
