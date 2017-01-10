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

/**
 * Interface TranslationInterface.
 */
interface TranslationEntityInterface
{
    /**
     * Get translation parent.
     *
     * @return TranslationEntityInterface
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
     * @return ArrayCollection
     */
    public function getTranslationChildren();

    /**
     * Add entity to $translationChildren.
     *
     * @return mixed
     */
    public function addTranslationChild(TranslationEntityInterface $child);

    /**
     * Remove entity from $translationChildren.
     *
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
     * Get the language.
     *
     * @return mixed
     */
    public function getLanguage();
}
