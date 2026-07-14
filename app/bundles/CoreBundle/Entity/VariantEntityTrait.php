<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @template T of VariantEntityInterface
 */
trait VariantEntityTrait
{
    /**
     * @var mixed
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    private $variantChildren;

    /**
     * @var VariantEntityInterface|null
     *
     * @phpstan-var T|null
     **/
    #[Groups(['email:read', 'email:write', 'download:read'])]
    private $variantParent;

    /**
     * @var array<string>
     */
    private array $variantSettingsKeys = ['weight', 'winnerCriteria'];

    /**
     * @var array<string>
     */
    private array $parentSettingsKeys = ['totalWeight', 'enableAbTest', 'winnerCriteria', 'sendWinnerDelay'];

    /**
     * @var array<mixed>|null
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    private $variantSettings = ['totalWeight' => AbTestSettingsService::DEFAULT_AB_WEIGHT, 'enableAbTest' => false];

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    private $variantStartDate;

    protected static function addVariantMetadata(ClassMetadataBuilder $builder, string $entityClass): void
    {
        $builder->createManyToOne('variantParent', $entityClass)
            ->inversedBy('variantChildren')
            ->addJoinColumn('variant_parent_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createOneToMany('variantChildren', $entityClass)
            ->setIndexBy('id')
            ->setOrderBy(['isPublished' => 'DESC'])
            ->mappedBy('variantParent')
            ->cascadePersist()
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
     */
    public function addVariantChild(VariantEntityInterface $child): static
    {
        if (!$this->getVariantChildren()->contains($child)) {
            $this->variantChildren->add($child);
        }

        return $this;
    }

    /**
     * Remove variant.
     */
    public function removeVariantChild(VariantEntityInterface $child): void
    {
        $this->getVariantChildren()->removeElement($child);
    }

    /**
     * Get variantChildren.
     */
    public function getVariantChildren(): ArrayCollection|Collection
    {
        return $this->variantChildren;
    }

    public function setVariantParent(?VariantEntityInterface $parent = null): static
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantParent', $parent);
        }

        $this->variantParent = $parent;

        return $this;
    }

    public function getVariantParent(): ?VariantEntityInterface
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
     * @param array<mixed> $variantSettings
     */
    public function setVariantSettings(array $variantSettings): static
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantSettings', $variantSettings);
        }

        $this->variantSettings = [];

        foreach ($this->getSettingsKeys() as $key) {
            if (array_key_exists($key, $variantSettings)) {
                $this->variantSettings[$key] = $variantSettings[$key];
            }
        }

        return $this;
    }

    /**
     * Get variantSettings.
     *
     * @return array<mixed>
     */
    public function getVariantSettings(): array
    {
        return $this->variantSettings ?? [];
    }

    public function getVariantStartDate(): mixed
    {
        return $this->variantStartDate;
    }

    public function setVariantStartDate(mixed $variantStartDate): static
    {
        if (method_exists($this, 'isChanged')) {
            $this->isChanged('variantStartDate', $variantStartDate);
        }

        $this->variantStartDate = $variantStartDate;

        return $this;
    }

    /**
     * @param bool $isChild True to return if the item is a variant of a parent
     */
    public function isVariant(bool $isChild = false): bool
    {
        $parent   = $this->getVariantParent();
        $children = $this->getVariantChildren();

        if ($isChild) {
            return null !== $parent;
        }

        return !empty($parent) || count($children);
    }

    public function isParent(): bool
    {
        return $this->isVariant() && empty($this->getVariantParent());
    }

    /**
     * Check if this entity has variants.
     */
    public function hasVariants(): int
    {
        return $this->getVariantChildren()->count();
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

        $children = [];
        if ($parent->getVariantChildren()->count()) {
            $children = $parent->getVariantChildren()->toArray();
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
     * @return string[]
     */
    private function getSettingsKeys(): array
    {
        if ($this->getVariantParent()) {
            return $this->variantSettingsKeys;
        }

        return $this->parentSettingsKeys;
    }

    public function clearVariantSettings(): void
    {
        if (!$this->getVariantParent()) {
            $this->variantSettings = [
                'enableAbTest' => false,
                'totalWeight'  => AbTestSettingsService::DEFAULT_AB_WEIGHT,
            ];
        } else {
            $this->variantSettings = [];
        }
    }

    public function isEnableAbTest(): bool
    {
        if ($this->getVariantParent()) {
            return (bool) ($this->getVariantParent()->getVariantSettings()['enableAbTest'] ?? false);
        }

        return (bool) ($this->getVariantSettings()['enableAbTest'] ?? false);
    }

    public function getVariantsPendingCount(int $pendingCount): int
    {
        if (!$this->isEnableAbTest()) {
            return $pendingCount;
        }

        $pendingCount += (int) (method_exists($this, 'getVariantSentCount') ? $this->getVariantSentCount(true) : 0);

        $totalWeight = $this->variantSettings['totalWeight'] ?? null;
        if ($this->getVariantParent()) {
            $totalWeight = $this->getVariantParent()->getVariantSettings()['totalWeight'] ?? null;
        }
        $totalWeight = (int) ($totalWeight ?? AbTestSettingsService::DEFAULT_TOTAL_WEIGHT);

        $variants           = $this->getVariantChildren();
        $variantCount       = count($variants) + 1;
        $singleVariantCount = (int) ceil(($pendingCount / $variantCount) * ($totalWeight / 100));

        return $singleVariantCount * $variantCount;
    }

    public function getVariantEndDate(): ?\DateTime
    {
        /** @var \DateTime $startDate */
        $startDate  = $this->getVariantStartDate();
        $delayHours = $this->getSendWinnerDelay();

        if (null === $startDate || 0 === $delayHours) {
            return null;
        }

        $endDate = clone $startDate;
        $endDate->modify("+$delayHours hours");

        return $endDate;
    }

    private function getSendWinnerDelay(): int
    {
        return (int) ($this->getVariantSettings()['sendWinnerDelay'] ?? null);
    }

    protected function getAccumulativeVariantCount(string $getter): mixed
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
     *
     * @param array<mixed> $ids
     *
     * @param-out  array<mixed> $ids
     */
    protected function appendTranslationEntityIds(object $entity, array &$ids, bool $publishedOnly): void
    {
        if (!$entity instanceof TranslationEntityInterface || !method_exists($this, 'getTranslations')) {
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
