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

    /**
     * @return $this
     */
    public function setVariantParent(?VariantEntityInterface $parent = null): static;

    public function removeVariantParent(): void;

    public function getVariantChildren(): ArrayCollection|Collection;

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
