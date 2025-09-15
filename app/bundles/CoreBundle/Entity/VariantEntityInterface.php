<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface VariantEntityInterface
{
    public function getVariantParent(): ?VariantEntityInterface;

    /**
     * @return $this
     */
    public function setVariantParent(?VariantEntityInterface $parent = null): static;

    public function removeVariantParent(): void;

    /**
     * @return Collection|array<VariantEntityInterface|object>
     */
    public function getVariantChildren(): Collection|array;

    /**
     * @return $this
     */
    public function addVariantChild(VariantEntityInterface $child): static;

    public function removeVariantChild(VariantEntityInterface $child): void;

    /**
     * @return array<mixed>
     */
    public function getVariantSettings(): array;

    public function getVariantStartDate(): mixed;

    /**
     * @return array<int, mixed>
     */
    public function getVariants(): array;

    public function isVariant(bool $isChild = false): bool;
}
