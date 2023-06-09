<?php

namespace Mautic\CoreBundle\Model\AbTest;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\VariantEntityInterface;

/**
 * Class VariantConverterService.
 */
class VariantConverterService
{
    /**
     * @const integer
     */
    public const DEFAULT_WEIGHT = 100;

    /**
     * @var array<VariantEntityInterface>
     */
    private $updatedVariants = [];

    /**
     * @var bool
     */
    private $switchParent = false;

    /**
     * Converts variants for a new winner.
     */
    public function convertWinnerVariant(VariantEntityInterface $winner): void
    {
        $this->setDefaultValues($winner);

        $this->switchParent = $winner->isVariant(true);

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
    public function getUpdatedVariants()
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
     * @param ArrayCollection<int, VariantEntityInterface|FormEntity> $variantChildren
     */
    private function updateOldChildren($variantChildren, VariantEntityInterface $winner): void
    {
        foreach ($variantChildren as $child) {
            if ($child->getId() !== $winner->getId()) {
                if (true === $this->switchParent) {
                    $this->transferChildToWinner($child, $winner);
                }
                $child->setIsPublished(false);
            }

            $this->setDefaultValues($child);

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

        $this->setDefaultValues($oldParent);
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
        $winnerSettings['totalWeight'] = self::DEFAULT_WEIGHT;

        $parentSettings = ['weight' => 0];

        $winner->setVariantSettings($winnerSettings);
        $oldParent->setVariantSettings($parentSettings);
    }

    private function setDefaultValues(VariantEntityInterface $variant): void
    {
        $variant->setVariantStartDate(null);
    }
}
