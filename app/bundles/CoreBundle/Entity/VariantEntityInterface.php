<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface VariantEntityInterface.
 */
interface VariantEntityInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Check publish status with option to check against category, publish up and down dates.
     *
     * @param bool $checkPublishStatus
     * @param bool $checkCategoryStatus
     *
     * @return bool
     */
    public function isPublished($checkPublishStatus = true, $checkCategoryStatus = true);

    /**
     * Get translation parent.
     *
     * @return mixed
     */
    public function getVariantParent();

    /**
     * Set entity this is a translation of.
     *
     * @param VariantEntityInterface $parent
     *
     * @return mixed
     */
    public function setVariantParent(VariantEntityInterface $parent = null);

    /**
     * Set this entity as a main content (remove translation parent).
     *
     * @return mixed
     */
    public function removeVariantParent();

    /**
     * Get ArrayCollection of translated entities.
     *
     * @return ArrayCollection
     */
    public function getVariantChildren();

    /**
     * Add entity to $translationChildren.
     *
     * @return mixed
     */
    public function addVariantChild(VariantEntityInterface $child);

    /**
     * Remove entity from $translationChildren.
     *
     * @return mixed
     */
    public function removeVariantChild(VariantEntityInterface $child);

    /**
     * Get settings array for the variant.
     *
     * @return mixed
     */
    public function getVariantSettings();

    /**
     * Get \DateTime when a/b test went into effect.
     *
     * @return \DateTime
     */
    public function getVariantStartDate();

    /**
     * Get all entities for variant parent/children.
     *
     * @return array[$parent, $children]
     */
    public function getVariants();

    /**
     * @param bool $isChild True to return if the item is a variant of a parent
     *
     * @return bool
     */
    public function isVariant($isChild = false);

    /**
     * Sets settings array for the variant.
     *
     * @param array<int|string> $variantSettings
     */
    public function setVariantSettings($variantSettings): self;

    /**
     * @param \DateTimeInterface|null $variantStartDate
     */
    public function setVariantStartDate($variantStartDate): self;

    public function isParent(): bool;

    /**
     * @return array<int>
     */
    public function getOnlyChildrenRelatedEntityIds(): array;
}
