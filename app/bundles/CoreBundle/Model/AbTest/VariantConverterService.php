<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model\AbTest;

use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\VariantEntityInterface;

class VariantConverterService
{
    /**
     * @var int
     */
    public const DEFAULT_WEIGHT = 100;

    /**
     * @var array<VariantEntityInterface>
     */
    private array $updatedVariants = [];

    private bool $switchParent = false;

    /**
     * Converts variants for a new winner.
     */
    public function convertWinnerVariant(VariantEntityInterface $winner): void
    {
        $this->updatedVariants = [];
        $this->switchParent    = $winner->isVariant(true);

        // set this email as the parent for the original parent and children
        if (true === $this->switchParent) {
            $oldParent = $winner->getVariantParent();

            $this->switchParent($winner, $oldParent);
            $this->updateOldChildren($oldParent->getVariantChildren(), $winner);
            $this->updateOldParentSettings($oldParent);
        } else {
            $this->updateWinnerSettings($winner);
            $this->updateOldChildren($winner->getVariantChildren(), $winner);
        }
    }

    /**
     * @return array<VariantEntityInterface>
     */
    public function getUpdatedVariants(): array
    {
        return $this->updatedVariants;
    }

    private function switchParent(VariantEntityInterface $winner, VariantEntityInterface $oldParent): void
    {
        if ($winner->getId() === $oldParent->getId()) {
            return;
        }

        $oldParent->removeVariantChild($winner);
        $winner->removeVariantParent();

        $this->transferChildToWinner($oldParent, $winner);
        $this->switchVariantSettings($winner, $oldParent);

        $this->addToUpdatedVariants($winner);
        $this->addToUpdatedVariants($oldParent);
    }

    /**
     * @param Collection<int, VariantEntityInterface|FormEntity> $variantChildren
     */
    private function updateOldChildren(Collection $variantChildren, VariantEntityInterface $winner): void
    {
        foreach ($variantChildren as $child) {
            if ($child->getId() !== $winner->getId()) {
                if (true === $this->switchParent) {
                    $this->transferChildToWinner($child, $winner);
                }
                $child->setIsPublished(false);
            }

            $this->addToUpdatedVariants($child);
        }
    }

    private function updateWinnerSettings(VariantEntityInterface $winner): void
    {
        $variantSettings = $winner->getVariantSettings();

        $variantSettings['totalWeight'] = self::DEFAULT_WEIGHT;

        $winner->setVariantSettings($variantSettings);
        $this->addToUpdatedVariants($winner);
    }

    /**
     * Sets oldParent settings.
     */
    public function updateOldParentSettings(VariantEntityInterface $oldParent): void
    {
        if (method_exists($oldParent, 'setIsPublished')) {
            $oldParent->setIsPublished(false);
        }
    }

    private function transferChildToWinner(VariantEntityInterface $child, VariantEntityInterface $winner): void
    {
        if (false === $this->switchParent) {
            return;
        }

        if ($child->getVariantParent()) {
            $child->getVariantParent()->removeVariantChild($child);
        }

        $winner->addVariantChild($child);
        $child->setVariantParent($winner);
    }

    private function addToUpdatedVariants(VariantEntityInterface $variant): void
    {
        if (in_array($variant, $this->updatedVariants)) {
            return;
        }

        $this->updatedVariants[] = $variant;
    }

    private function switchVariantSettings(VariantEntityInterface $winner, VariantEntityInterface $oldParent): void
    {
        $winnerSettings    = $winner->getVariantSettings();
        $oldParentSettings = $oldParent->getVariantSettings();

        if (array_key_exists('winnerCriteria', $oldParentSettings)) {
            $winnerSettings['winnerCriteria'] = $oldParentSettings['winnerCriteria'];
        }

        if (array_key_exists('sendWinnerDelay', $oldParentSettings)) {
            $winnerSettings['sendWinnerDelay'] = $oldParentSettings['sendWinnerDelay'];
        }

        if (array_key_exists('enableAbTest', $oldParentSettings)) {
            $winnerSettings['enableAbTest'] = $oldParentSettings['enableAbTest'];
        }

        $winnerSettings['totalWeight'] = self::DEFAULT_WEIGHT;

        $parentSettings = ['weight' => 0];

        $winner->setVariantSettings($winnerSettings);
        $oldParent->setVariantSettings($parentSettings);
    }
}
