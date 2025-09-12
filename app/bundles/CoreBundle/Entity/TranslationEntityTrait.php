<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Annotation\Groups;

trait TranslationEntityTrait
{
    /**
     * Set by AbstractCommonModel::getEntityBySlugs() if a language slug was used to fetch the entity.
     *
     * @var string
     *
     * @Groups({"page:read", "download:read", "email:read", "form:read"})
     */
    public $languageSlug;

    /**
     * @var mixed
     *
     * @Groups({"page:read", "download:read", "email:read", "form:read"})
     **/
    private $translationChildren;

    /**
     * @var mixed
     **/
    private $translationParent;

    /**
     * @var string
     *
     * @Groups({"page:read", "download:read", "email:read", "form:read"})
     */
    private $language = 'en';

    protected static function addTranslationMetadata(ClassMetadataBuilder $builder, string $entityClass, string $languageColumnName = 'lang'): void
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
     * @return $this
     */
    public function addTranslationChild(TranslationEntityInterface $child)
    {
        if (!$this->translationChildren->contains($child)) {
            $this->translationChildren[] = $child;
        }

        return $this;
    }

    public function removeTranslationChild(TranslationEntityInterface $child): void
    {
        $this->translationChildren->removeElement($child);
    }

    /**
     * Get translated items.
     *
     * @return Collection<int, TranslationEntityInterface>
     */
    public function getTranslationChildren(): Collection
    {
        return $this->translationChildren;
    }

    /**
     * @return $this
     */
    public function setTranslationParent(?TranslationEntityInterface $parent = null)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('translationParent', $parent);
        }

        $this->translationParent = $parent;

        return $this;
    }

    public function getTranslationParent(): ?TranslationEntityInterface
    {
        return $this->translationParent;
    }

    public function removeTranslationParent(): void
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('translationParent', '');
        }

        $this->translationParent = null;
    }

    /**
     * @return $this
     */
    public function setLanguage(string $language)
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('language', $language);
        }

        $this->language = $language;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param bool $isChild True to return if the item is a translation of a parent
     */
    public function isTranslation($isChild = false): bool
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
     * @return array{0: TranslationEntityInterface, 1: array<TranslationEntityInterface>}|array<TranslationEntityInterface>
     */
    public function getTranslations($onlyChildren = false)
    {
        $parent = $this->getTranslationParent();

        if (empty($parent)) {
            $parent = $this;
        }

        $children = $parent->getTranslationChildren();

        if ($children instanceof Collection) {
            $children = $children->toArray();
        }

        if (!is_array($children)) {
            $children = [];
        }

        if ($onlyChildren) {
            return $children;
        }

        return [$parent, $children];
    }

    protected function getAccumulativeTranslationCount(string $getter, ?TranslationEntityInterface $variantParent = null): int
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
