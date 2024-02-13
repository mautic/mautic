<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait TranslationEntityTrait
{
    /**
     * Set by AbstractCommonModel::getEntityBySlugs() if a language slug was used to fetch the entity.
     *
     * @var string
     */
    public $languageSlug;

    /**
     * @var mixed
     **/
    private $translationChildren;

    /**
     * @var mixed
     **/
    private $translationParent;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @param ClassMetadata $builder
     * @param string        $languageColumnName
     */
    protected static function addTranslationMetadata(ClassMetadataBuilder $builder, $entityClass, $languageColumnName = 'lang')
    {
        $builder->createOneToMany('translationChildren', $entityClass)
            ->setIndexBy('id')
            ->setOrderBy(['isPublished' => 'DESC'])
            ->mappedBy('translationParent')
            ->build();

        $builder->createManyToOne('translationParent', $entityClass)
            ->inversedBy('translationChildren')
            ->addJoinColumn('translation_parent_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('language', 'string')
            ->columnName($languageColumnName)
            ->build();
    }

    /**
     * Add translation.
     *
     * @return $this
     */
    public function addTranslationChild(TranslationEntityInterface $child)
    {
        if (!$this->translationChildren->contains($child)) {
            $this->translationChildren[] = $child;
        }

        return $this;
    }

    /**
     * Remove translation.
     */
    public function removeTranslationChild(TranslationEntityInterface $child): void
    {
        $this->translationChildren->removeElement($child);
    }

    /**
     * Get translated items.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslationChildren()
    {
        return $this->translationChildren;
    }

    /**
     * Set translation parent.
     *
     * @return $this
     */
    public function setTranslationParent(TranslationEntityInterface $parent = null)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('translationParent', $parent);
        }

        $this->translationParent = $parent;

        return $this;
    }

    /**
     * Get translation parent.
     *
     * @return mixed
     */
    public function getTranslationParent()
    {
        return $this->translationParent;
    }

    /**
     * Remove translation parent.
     */
    public function removeTranslationParent(): void
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('translationParent', '');
        }

        $this->translationParent = null;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('language', $language);
        }

        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param bool $isChild True to return if the item is a translation of a parent
     *
     * @return bool
     */
    public function isTranslation($isChild = false)
    {
        $parent   = $this->getTranslationParent();
        $children = $this->getTranslationChildren();

        if ($isChild) {
            return (null === $parent) ? false : true;
        } else {
            return (!empty($parent) || count($children)) ? true : false;
        }
    }

    /**
     * Check if this entity has translations.
     */
    public function hasTranslations(): int
    {
        $children = $this->getTranslationChildren();

        return count($children);
    }

    /**
     * Clear translations.
     */
    public function clearTranslations(): void
    {
        $this->translationChildren = new ArrayCollection();
        $this->translationParent   = null;
    }

    /**
     * Get translation parent/children.
     *
     * @param bool $onlyChildren
     *
     * @return array|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getTranslations($onlyChildren = false)
    {
        $parent = $this->getTranslationParent();

        if (empty($parent)) {
            $parent = $this;
        }

        if ($children = $parent->getTranslationChildren()) {
            if ($children instanceof Collection) {
                $children = $children->toArray();
            }
        }

        if (!is_array($children)) {
            $children = [];
        }

        if ($onlyChildren) {
            return $children;
        }

        return [$parent, $children];
    }

    /**
     * @return mixed
     */
    protected function getAccumulativeTranslationCount($getter, $variantParent = null)
    {
        $count = 0;

        [$parent, $children] = $this->getTranslations();
        if ($variantParent != $parent) {
            $count = $parent->$getter();
        }

        foreach ($children as $translation) {
            if ($variantParent != $translation) {
                $count += $translation->$getter();
            }
        }

        return $count;
    }
}
