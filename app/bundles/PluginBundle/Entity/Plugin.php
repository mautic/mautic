<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class Plugin.
 */
class Plugin extends CommonEntity
{
    const DESCRIPTION_DELIMITER_REGEX = "/\R---\R/";

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
     * @var string
     */
    private $primaryDescription;

    /**
     * @var string
     */
    private $secondaryDescription;

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

    public function __construct()
    {
        $this->integrations = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugins')
            ->setCustomRepositoryClass('Mautic\PluginBundle\Entity\PluginRepository')
            ->addUniqueConstraint(['bundle'], 'unique_bundle');

        $builder->addIdColumns();

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
            ->mappedBy('plugin')
            ->fetchExtraLazy()
            ->build();
    }

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Plugin
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set bundle.
     *
     * @param string $bundle
     *
     * @return Plugin
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Get bundle.
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @return mixed
     */
    public function getIntegrations()
    {
        return $this->integrations;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        $this->splitDescriptions();
    }

    /**
     * @return string|null
     */
    public function getPrimaryDescription()
    {
        return $this->primaryDescription ?: $this->description;
    }

    /**
     * @return bool
     */
    public function hasSecondaryDescription()
    {
        return preg_match(self::DESCRIPTION_DELIMITER_REGEX, $this->description) >= 1;
    }

    /**
     * @return string|null
     */
    public function getSecondaryDescription()
    {
        return $this->secondaryDescription;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getIsMissing()
    {
        return $this->isMissing;
    }

    /**
     * @param mixed $isMissing
     */
    public function setIsMissing($isMissing)
    {
        $this->isMissing = $isMissing;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Splits description into primary and secondary.
     */
    public function splitDescriptions()
    {
        if ($this->hasSecondaryDescription()) {
            $parts                      = preg_split(self::DESCRIPTION_DELIMITER_REGEX, $this->description);
            $this->primaryDescription   = trim($parts[0]);
            $this->secondaryDescription = trim($parts[1]);
        }
    }
}
