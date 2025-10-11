<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * @method int|null getId()
 * @method bool     isPublished()
 */
interface TranslationEntityInterface
{
    public function getTranslationParent(): ?TranslationEntityInterface;

    public function setTranslationParent(?TranslationEntityInterface $parent = null): self;

    public function removeTranslationParent(): void;

    public function getTranslationChildren(): ?Collection;

    /**
     * @return $this
     */
    public function addTranslationChild(TranslationEntityInterface $child);

    public function removeTranslationChild(TranslationEntityInterface $child): void;

    /**
     * @return array<int, mixed>
     */
    public function getTranslations(bool $onlyChildren = false): array;

    public function isTranslation(bool $isChild = false): bool;

    public function getLanguage(): ?string;
}
