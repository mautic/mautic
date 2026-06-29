<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class PointInsight extends FormEntity
{
    public const INSIGHT_TYPE_COMPARE_POINT_GROUPS = 'compare_point_groups';

    public const INSIGHT_ACTION_SET_CUSTOM_FIELD = 'set_custom_field';

    private ?int $id = null;

    private string $name = '';

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $insightType = self::INSIGHT_TYPE_COMPARE_POINT_GROUPS;

    /**
     * @var string
     */
    private $insightAction = self::INSIGHT_ACTION_SET_CUSTOM_FIELD;

    /**
     * @var string|null
     */
    private $customField;

    /**
     * @var array<int>
     */
    private $pointGroups = [];

    /**
     * @var Category|null
     **/
    private $category;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('point_insights')
            ->setCustomRepositoryClass(PointInsightRepository::class);

        $builder->addIdColumns();

        $builder->createField('insightType', Types::STRING)
            ->columnName('insight_type')
            ->build();

        $builder->createField('insightAction', Types::STRING)
            ->columnName('insight_action')
            ->build();

        $builder->createField('customField', Types::STRING)
            ->columnName('custom_field')
            ->nullable()
            ->build();

        $builder->createField('pointGroups', Types::JSON)
            ->columnName('point_groups')
            ->build();

        $builder->addCategory();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('insightType', new Assert\NotBlank([
            'message' => 'mautic.point.insight.type.required',
        ]));

        $metadata->addPropertyConstraint('insightAction', new Assert\NotBlank([
            'message' => 'mautic.point.insight.action.required',
        ]));
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

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isPublished();
    }

    /**
     * Alias of isActive().
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->isActive();
    }

    /**
     * @param bool $active
     *
     * @return PointInsight
     */
    public function setActive($active): FormEntity
    {
        return $this->setIsPublished($active);
    }
}
