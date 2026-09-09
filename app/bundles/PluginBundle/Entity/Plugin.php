<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\CommonEntity;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Plugin extends CommonEntity implements CacheInvalidateInterface
{
    public const DESCRIPTION_DELIMITER_REGEX = "/\R---\R/";

    public const CACHE_NAMESPACE             = 'Plugin';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
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
    #[ORM\Column(name: 'is_missing', type: 'boolean')]
    private $isMissing = false;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    private $bundle;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $version;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $author;

    /**
     * @var ArrayCollection<int, Integration>
     */
    #[ORM\OneToMany(mappedBy: 'plugin', targetEntity: 'Integration', fetch: 'EXTRA_LAZY', indexBy: 'id')]
    private $integrations;

    public function __construct()
    {
        $this->integrations = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugins')
            ->setCustomRepositoryClass(PluginRepository::class)
            ->addUniqueConstraint(['bundle'], 'unique_bundle');
    }

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->name = $name;

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
     * @param string $bundle
     */
    public function setBundle($bundle): void
    {
        $this->bundle = $bundle;
    }

    /**
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @return ArrayCollection<int, Integration>
     */
    public function getIntegrations()
    {
        return $this->integrations;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
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

    public function hasSecondaryDescription(): bool
    {
        return $this->description && preg_match(self::DESCRIPTION_DELIMITER_REGEX, $this->description) >= 1;
    }

    /**
     * @return string|null
     */
    public function getSecondaryDescription()
    {
        return $this->secondaryDescription;
    }

    /**
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version): void
    {
        $this->version = $version;
    }

    /**
     * @return bool
     */
    public function getIsMissing()
    {
        return $this->isMissing;
    }

    /**
     * @param mixed $isMissing
     */
    public function setIsMissing($isMissing): void
    {
        $this->isMissing = $isMissing;
    }

    /**
     * @return string|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    /**
     * Splits description into primary and secondary.
     */
    public function splitDescriptions(): void
    {
        if ($this->hasSecondaryDescription()) {
            $parts                      = preg_split(self::DESCRIPTION_DELIMITER_REGEX, $this->description);
            $this->primaryDescription   = trim($parts[0]);
            $this->secondaryDescription = trim($parts[1]);
        }
    }

    public function getCacheNamespacesToDelete(): array
    {
        return [
            self::CACHE_NAMESPACE,
            Integration::CACHE_NAMESPACE,
        ];
    }
}
