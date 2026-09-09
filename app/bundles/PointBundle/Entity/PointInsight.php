<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PointInsightRepository::class)]
#[ORM\Table(name: 'point_insights')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PointInsight extends FormEntity
{
    public const INSIGHT_TYPE_COMPARE_POINT_GROUPS = 'compare_point_groups';

    public const INSIGHT_ACTION_SET_CUSTOM_FIELD = 'set_custom_field';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private string $name = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'mautic.point.insight.type.required')]
    #[ORM\Column(name: 'insight_type', type: Types::STRING, length: 191)]
    private $insightType = self::INSIGHT_TYPE_COMPARE_POINT_GROUPS;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'mautic.point.insight.action.required')]
    #[ORM\Column(name: 'insight_action', type: Types::STRING, length: 191)]
    private $insightAction = self::INSIGHT_ACTION_SET_CUSTOM_FIELD;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'custom_field', type: Types::STRING, length: 191, nullable: true)]
    private $customField;

    /**
     * @var array<int>
     */
    #[ORM\Column(name: 'point_groups', type: Types::JSON)]
    private $pointGroups = [];

    /**
     * @var Category|null
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private $category;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('pointInsight')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'insightType',
                    'insightAction',
                    'customField',
                    'pointGroups',
                ]
            )
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInsightType()
    {
        return $this->insightType;
    }

    /**
     * @param string|null $insightType
     */
    public function setInsightType($insightType): static
    {
        $this->isChanged('insightType', $insightType);
        $this->insightType = $insightType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInsightAction()
    {
        return $this->insightAction;
    }

    /**
     * @param string|null $insightAction
     */
    public function setInsightAction($insightAction): static
    {
        $this->isChanged('insightAction', $insightAction);
        $this->insightAction = $insightAction;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomField()
    {
        return $this->customField;
    }

    /**
     * @param string|null $customField
     */
    public function setCustomField($customField): static
    {
        $this->isChanged('customField', $customField);
        $this->customField = $customField;

        return $this;
    }

    /**
     * @return array<int>
     */
    public function getPointGroups()
    {
        return $this->pointGroups;
    }

    /**
     * @param array<int> $pointGroups
     */
    public function setPointGroups($pointGroups): static
    {
        $this->isChanged('pointGroups', $pointGroups);
        $this->pointGroups = $pointGroups;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory($category): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isPublished();
    }

    /**
     * Alias of isActive().
     */
    public function getActive(): bool
    {
        return $this->isActive();
    }

    public function setActive(bool $active): self
    {
        return $this->setIsPublished($active);
    }
}
