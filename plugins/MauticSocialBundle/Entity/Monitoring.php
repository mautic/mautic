<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MonitoringRepository::class)]
#[ORM\Table(name: 'monitoring')]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('mauticSocial:monitoring:view')"),
        new Post(security: "is_granted('mauticSocial:monitoring:create')"),
        new Get(security: "is_granted('mauticSocial:monitoring:view')"),
        new Put(security: "is_granted('mauticSocial:monitoring:edit')"),
        new Patch(security: "is_granted('mauticSocial:monitoring:edit')"),
        new Delete(security: "is_granted('mauticSocial:monitoring:delete')"),
    ],
    normalizationContext: [
        'groups'                  => ['monitoring:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['monitoring:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Monitoring extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    #[Groups(['monitoring:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[Assert\NotBlank(message: 'mautic.core.title.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $title;

    /**
     * @var string|null
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private ?\Mautic\CategoryBundle\Entity\Category $category = null;

    /**
     * @var array
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private $lists = [];

    /**
     * @var string|null
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[Assert\NotBlank(message: 'mautic.social.network.type')]
    #[ORM\Column(name: 'network_type', type: 'string', length: 191, nullable: true)]
    private $networkType;

    /**
     * @var int
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(type: 'integer')]
    private $revision = 1;

    /**
     * @var array
     */
    #[Groups(['monitoring:read'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private $stats = [];

    /**
     * @var array
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private $properties = [];

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['monitoring:read', 'monitoring:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * Constraints for required fields.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * @return string
     */
    public function getNetworkType()
    {
        return $this->networkType;
    }

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Get statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set the category id.
     */
    public function setCategory(?Category $category): void
    {
        $this->isChanged('category', $category);
        $this->category = $category;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Set the monitor lists.
     */
    public function setLists($lists): static
    {
        $this->isChanged('lists', $lists);
        $this->lists = $lists;

        return $this;
    }

    public function setNetworkType($networkType): static
    {
        $this->isChanged('networkType', $networkType);
        $this->networkType = $networkType;

        return $this;
    }

    /**
     * Set the revision counter.
     *
     * @param int $revision
     */
    public function setRevision($revision): static
    {
        $this->isChanged('revision', $revision);
        $this->revision = $revision;

        return $this;
    }

    /**
     * Set the statistics.
     */
    public function setStats(array $stats): static
    {
        $this->isChanged('stats', $stats);
        $this->stats = $stats;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $title
     */
    public function setTitle($title): static
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    public function setProperties(array $properties): static
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param \DateTime $publishDown
     */
    public function setPublishDown($publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @param \DateTime $publishUp
     */
    public function setPublishUp($publishUp): static
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Clear out old properties data.
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function cleanMonitorData(): void
    {
        $property = $this->properties;

        if (!array_key_exists('checknames', $property)) {
            $property['checknames'] = 0;
        }

        // clean up property array for the twitter handle
        if ('twitter_handle' == $this->networkType) {
            $this->setProperties(
                [
                    'handle'     => $property['handle'],
                    'checknames' => $property['checknames'],
                ]
            );
        }

        // clean up property array for the hashtag
        if ('twitter_hashtag' == $this->networkType) {
            $this->setProperties(
                [
                    'hashtag'    => $property['hashtag'],
                    'checknames' => $property['checknames'],
                ]
            );
        }

        // clean up clean up property array for the custom action
        if ('twitter_custom' == $this->networkType) {
            $this->setProperties(
                [
                    'custom' => $property['custom'],
                ]
            );
        }

        // if the property is not new and the old property doesn't match the new one
        if (!$this->isNew() && $property != $this->properties) {
            // reset stats on save of edited
            $this->setStats([]);
        }
    }
}
