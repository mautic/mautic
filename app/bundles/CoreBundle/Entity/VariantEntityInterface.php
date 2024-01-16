<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface VariantEntityInterface.
 */
interface VariantEntityInterface
{
    /**
     * Get translation parent.
     */
    public function getVariantParent();

    /**
     * Set entity this is a translation of.
     */
    public function setVariantParent(VariantEntityInterface $parent = null);

    /**
     * Set this entity as a main content (remove translation parent).
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
     */
    public function addVariantChild(VariantEntityInterface $child);

    /**
     * Remove entity from $translationChildren.
     */
    public function removeVariantChild(VariantEntityInterface $child);

    /**
     * Get settings array for the variant.
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
}
