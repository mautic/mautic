<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @template T of TranslationEntityInterface
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
     * @var Collection
     *
     * @phpstan-var Collection<int, T>
     **/
    #[Groups(['page:read', 'page:write', 'download:read', 'download:write', 'email:read', 'email:write', 'dynamicContent:read', 'dynamicContent:write'])]
    private $translationChildren;

    /**
     * @var TranslationEntityInterface|null
     *
     * @phpstan-var T|null
     **/
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

    public function addTranslationChild(TranslationEntityInterface $child): static
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
     */
    public function getTranslationChildren(): ?Collection
    {
        return $this->translationChildren;
    }

    public function setTranslationParent(?TranslationEntityInterface $parent = null): self
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
        } else {
            return !empty($parent) || ($children && count($children));
        }
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
     * @return array<mixed>
     */
    public function getTranslations(bool $onlyChildren = false): array
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

    /**
     * @param string                      $getter
     * @param ?TranslationEntityInterface $variantParent
     *
     * @return int
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
