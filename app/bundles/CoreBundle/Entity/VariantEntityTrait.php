<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait VariantEntityTrait
{
    /**
     * @var ArrayCollection
     **/
    private $variantChildren;

    /**
     * @var Page
     **/
    private $variantParent = null;

    /**
     * @var array
     */
    private $variantSettings = [];

    /**
     * @var \DateTime
     */
    private $variantStartDate;

    /**
     * @param ClassMetadata $builder
     * @param               $entityClass
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
     * @param VariantEntityInterface $child
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
     *
     * @param VariantEntityInterface $child
     */
    public function removeVariantChild(VariantEntityInterface $child)
    {
        $this->variantChildren->removeElement($child);
    }

    /**
     * Get variantChildren.
     *
     * @return \Doctrine\Common\Collections\Collection
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
     * @return $this
     */
    public function getVariantParent()
    {
        return $this->variantParent;
    }

    /**
     * Remove variant parent.
     */
    public function removeVariantParent()
    {
        $this->setVariantParent(null);
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
     * @param $variantStartDate
     *
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
            return ($parent === null) ? false : true;
        } else {
            return (!empty($parent) || count($children)) ? true : false;
        }
    }

    /**
     * Check if this entity has variants.
     *
     * @return int
     */
    public function hasVariants()
    {
        $children = $this->getTranslationChildren();

        return count($children);
    }

    /**
     * Clear variants.
     */
    public function clearVariants()
    {
        $this->variantChildren = new ArrayCollection();
        $this->variantParent   = null;
    }

    /**
     * Get the variant parent/children.
     **.
     *
     * @return array[$parent, $children]
     */
    public function getVariants()
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
     * @return array
     */
    public function getRelatedEntityIds($publishedOnly = false)
    {
        list($parent, $children) = $this->getVariants();

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
     * @param $getter
     *
     * @return mixed
     */
    protected function getAccumulativeVariantCount($getter)
    {
        list($parent, $children) = $this->getVariants();
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
     *
     * @param $entity
     * @param $ids
     * @param $publishedOnly
     */
    protected function appendTranslationEntityIds($entity, &$ids, $publishedOnly)
    {
        if (!($entity instanceof TranslationEntityInterface && method_exists($this, 'getTranslations'))) {
            return;
        }

        /** @var TranslationEntityInterface $parentTranslation */
        /** @var ArrayCollection $childrenTranslations */
        list($parentTranslation, $childrenTranslations) = $entity->getTranslations();
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
