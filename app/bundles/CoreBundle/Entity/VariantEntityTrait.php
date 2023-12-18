<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait VariantEntityTrait
{
    /**
     * @var mixed
     **/
    private $variantChildren;

    /**
     * @var mixed
     **/
    private $variantParent;

    /**
     * @var array
     */
    private $variantSettings = [];

    /**
     * @var \DateTimeInterface|null
     */
    private $variantStartDate;

    /**
     * @param ClassMetadata $builder
     */
    protected static function addVariantMetadata(ClassMetadataBuilder $builder, $entityClass)
    {
        $builder->createManyToOne('variantParent', $entityClass)
            ->inversedBy('variantChildren')
            ->addJoinColumn('variant_parent_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createOneToMany('variantChildren', $entityClass)
            ->setIndexBy('id')
            ->setOrderBy(['isPublished' => 'DESC'])
            ->mappedBy('variantParent')
            ->build();

        $builder->createField('variantSettings', 'array')
            ->columnName('variant_settings')
            ->nullable()
            ->build();

        $builder->createField('variantStartDate', 'datetime')
            ->columnName('variant_start_date')
            ->nullable()
            ->build();
    }

    /**
     * Add variant.
     *
     * @return $this
     */
    public function addVariantChild(VariantEntityInterface $child)
    {
        if (!$this->variantChildren->contains($child)) {
            $this->variantChildren[] = $child;
        }

        return $this;
    }

    /**
     * Remove variant.
     */
    public function removeVariantChild(VariantEntityInterface $child): void
    {
        $this->variantChildren->removeElement($child);
    }

    /**
     * Get variantChildren.
     *
     * @return mixed
     */
    public function getVariantChildren()
    {
        return $this->variantChildren;
    }

    /**
     * Set variantParent.
     *
     * @param VarientEntityEnterface $parent
     *
     * @return $this
     */
    public function setVariantParent(VariantEntityInterface $parent = null)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantParent', $parent);
        }

        $this->variantParent = $parent;

        return $this;
    }

    /**
     * Get variantParent.
     *
     * @return mixed
     */
    public function getVariantParent()
    {
        return $this->variantParent;
    }

    /**
     * Remove variant parent.
     */
    public function removeVariantParent(): void
    {
        $this->setVariantParent();
    }

    /**
     * Set variantSettings.
     *
     * @param array $variantSettings
     *
     * @return $this
     */
    public function setVariantSettings($variantSettings)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantSettings', $variantSettings);
        }

        $this->variantSettings = $variantSettings;

        return $this;
    }

    /**
     * Get variantSettings.
     *
     * @return array
     */
    public function getVariantSettings()
    {
        return $this->variantSettings;
    }

    /**
     * @return mixed
     */
    public function getVariantStartDate()
    {
        return $this->variantStartDate;
    }

    /**
     * @return $this
     */
    public function setVariantStartDate($variantStartDate)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantStartDate', $variantStartDate);
        }

        $this->variantStartDate = $variantStartDate;

        return $this;
    }

    /**
     * @param bool $isChild True to return if the item is a variant of a parent
     *
     * @return bool
     */
    public function isVariant($isChild = false)
    {
        $parent   = $this->getVariantParent();
        $children = $this->getVariantChildren();

        if ($isChild) {
            return (null === $parent) ? false : true;
        } else {
            return (!empty($parent) || count($children)) ? true : false;
        }
    }

    /**
     * Check if this entity has variants.
     */
    public function hasVariants(): int
    {
        $children = $this->getTranslationChildren();

        return count($children);
    }

    /**
     * Clear variants.
     */
    public function clearVariants(): void
    {
        $this->variantChildren = new ArrayCollection();
        $this->variantParent   = null;
    }

    /**
     * Get the variant parent/children.
     **.
     *
     * @return array<mixed>
     */
    public function getVariants(): array
    {
        $parent = $this->getVariantParent();
        if (empty($parent)) {
            $parent = $this;
        }

        if ($children = $parent->getVariantChildren()) {
            if ($children instanceof Collection) {
                $children = $children->toArray();
            }
        }

        if (!is_array($children)) {
            $children = [];
        }

        return [$parent, $children];
    }

    /**
     * Get an array of all IDs for parent/child variants and associated translations if applicable.
     *
     * @param bool $publishedOnly
     *
     * @return array<int,int|string>
     */
    public function getRelatedEntityIds($publishedOnly = false): array
    {
        [$parent, $children] = $this->getVariants();

        // If parent is not published and only published has been requested, no need to proceed
        if ($parent && $publishedOnly && !$parent->isPublished()) {
            return [];
        }

        // If this is a new top level entity, it may not have an ID
        $ids = ($parent->getId()) ? [$parent->getId()] : [];

        // Append translations for this variant if applicable
        $this->appendTranslationEntityIds($this, $ids, $publishedOnly);

        foreach ($children as $variant) {
            if ((!$publishedOnly || $variant->isPublished()) && $id = $variant->getId()) {
                $ids[] = $id;

                // Append translations for this variant if applicable
                $this->appendTranslationEntityIds($variant, $ids, $publishedOnly);
            }
        }

        return array_unique($ids);
    }

    /**
     * @return mixed
     */
    protected function getAccumulativeVariantCount($getter)
    {
        [$parent, $children]     = $this->getVariants();
        $count                   = $parent->$getter();

        if ($checkTranslations = method_exists($parent, 'getAccumulativeTranslationCount')) {
            // Append translations for this variant if applicable
            $count += $parent->getAccumulativeTranslationCount($getter, $parent);
        }

        foreach ($children as $variant) {
            $count += $variant->$getter();

            if ($checkTranslations) {
                // Append translations for this variant if applicable
                $count += $variant->getAccumulativeTranslationCount($getter, $variant);
            }
        }

        return $count;
    }

    /**
     * Finds and appends IDs for translations of a variant.
     */
    protected function appendTranslationEntityIds($entity, &$ids, $publishedOnly)
    {
        if (!($entity instanceof TranslationEntityInterface && method_exists($this, 'getTranslations'))) {
            return;
        }

        /** @var TranslationEntityInterface $parentTranslation */
        /** @var ArrayCollection $childrenTranslations */
        [$parentTranslation, $childrenTranslations] = $entity->getTranslations();
        if ($entity->getId() && $parentTranslation != $entity) {
            if (!$publishedOnly || $parentTranslation->isPublished()) {
                $ids[] = $parentTranslation->getId();
            }
        }

        if (!$publishedOnly) {
            if (is_array($childrenTranslations)) {
                $ids = array_merge($ids, array_keys($childrenTranslations));
            } elseif ($childrenTranslations instanceof Collection) {
                $ids = array_merge($ids, $childrenTranslations->getKeys());
            }
        } else {
            foreach ($childrenTranslations as $translation) {
                if ($translation->isPublished() && $id = $translation->getId()) {
                    $ids[] = $id;
                }
            }
        }
    }
}
