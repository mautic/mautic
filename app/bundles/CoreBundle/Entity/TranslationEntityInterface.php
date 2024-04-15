<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

interface TranslationEntityInterface
{
    /**
     * @return ?TranslationEntityInterface
     */
    public function getTranslationParent();

    /**
     * Set entity this is a translation of.
     *
     * @return mixed
     */
    public function setTranslationParent(TranslationEntityInterface $parent = null);

    /**
     * Set this entity as a main content (remove translation parent).
     *
     * @return mixed
     */
    public function removeTranslationParent();

    /**
     * Get ArrayCollection of translated entities.
     *
     * @return ?ArrayCollection
     */
    public function getTranslationChildren();

    /**
     * @return mixed
     */
    public function addTranslationChild(TranslationEntityInterface $child);

    /**
     * @return mixed
     */
    public function removeTranslationChild(TranslationEntityInterface $child);

    /**
     * Get array with entities for this translation.
     *
     * If $onlyChildren, then return just $children; otherwise [$parent, $children]
     *
     * @return array
     */
    public function getTranslations($onlyChildren = false);

    /**
     * @param bool $isChild True to return if the item is a translation of a parent
     *
     * @return bool
     */
    public function isTranslation($isChild = false);

    /**
     * @return mixed
     */
    public function getLanguage();
}
