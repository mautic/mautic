<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @template TEntity of TranslationEntityInterface
 */
trait TranslationEntityTrait
{
    /**
     * Set by AbstractCommonModel::getEntityBySlugs() if a language slug was used to fetch the entity.
     *
     * @var string
     */
    #[Groups(['page:read', 'download:read', 'email:read'])]
    public $languageSlug;

    /**
     * @var Collection<int, TEntity>
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'download:write', 'email:read', 'email:write', 'dynamicContent:read', 'dynamicContent:write'])]
    private $translationChildren;

    /**
     * @var TEntity|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'download:write', 'email:read', 'email:write', 'dynamicContent:read', 'dynamicContent:write'])]
    private $translationParent;

    #[Groups(['page:read', 'page:write', 'download:read', 'download:write', 'email:read', 'email:write', 'dynamicContent:read', 'dynamicContent:write'])]
    private string $language = 'en';

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
     * @param TEntity $child
     */
    public function addTranslationChild(TranslationEntityInterface $child): static
    {
        if (!$this->translationChildren->contains($child)) {
            $this->translationChildren->add($child);
        }

        return $this;
    }

    /**
     * @param TEntity $child
     */
    public function removeTranslationChild(TranslationEntityInterface $child): void
    {
        $this->translationChildren->removeElement($child);
    }

    /**
     * @return Collection<int, TEntity>|null
     */
    public function getTranslationChildren(): ?Collection
    {
        return $this->translationChildren;
    }

    /**
     * @param TEntity|null $parent
     */
    public function setTranslationParent(?TranslationEntityInterface $parent = null): self
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('translationParent', $parent);
        }

        $this->translationParent = $parent;

        return $this;
    }

    /**
     * @return TEntity|null
     */
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

    public function setLanguage(?string $language): self
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('language', $language);
        }

        $this->language = $language;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param bool $isChild True to return if the item is a translation of a parent
     */
    public function isTranslation(bool $isChild = false): bool
    {
        $parent   = $this->getTranslationParent();
        $children = $this->getTranslationChildren();

        if ($isChild) {
            return null !== $parent;
        }

        return $parent instanceof TranslationEntityInterface || ($children && count($children));
    }

    /**
     * Check if this entity has translations.
     */
    public function hasTranslations(): int
    {
        $children = $this->getTranslationChildren();

        return $children ? count($children) : 0;
    }

    public function clearTranslations(): void
    {
        $this->translationChildren = new ArrayCollection();
        $this->translationParent   = null;
    }

    /**
     * Get translation parent/children.
     *
     * @return array<int, TranslationEntityInterface|array<TranslationEntityInterface>>
     */
    public function getTranslations(bool $onlyChildren = false): array
    {
        $parent = $this->getTranslationParent();

        if (!$parent instanceof TranslationEntityInterface) {
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
            $count = $parent->{$getter}();
        }

        foreach ($children as $translation) {
            if ($variantParent != $translation) {
                $count += $translation->{$getter}();
            }
        }

        return $count;
    }
}
