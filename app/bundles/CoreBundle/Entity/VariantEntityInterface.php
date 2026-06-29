<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface VariantEntityInterface
{
    /**
     * @return int|null
     */
    public function getId();

    public function getVariantParent(): ?VariantEntityInterface;

    public function setVariantParent(?VariantEntityInterface $parent = null): static;

    public function removeVariantParent(): void;

    public function getVariantChildren(): ArrayCollection|Collection;

    public function addVariantChild(VariantEntityInterface $child): static;

    public function removeVariantChild(VariantEntityInterface $child): void;

    /**
     * @param array<mixed> $variantSettings
     */
    public function setVariantSettings(array $variantSettings): static;

    /**
     * @return array<mixed>
     */
    public function getVariantSettings(): array;

    public function getVariantStartDate(): mixed;

    public function setVariantStartDate(mixed $variantStartDate): static;

    /**
     * @return array<int, mixed>
     */
    public function getVariants(): array;

    public function isVariant(bool $isChild = false): bool;
}
