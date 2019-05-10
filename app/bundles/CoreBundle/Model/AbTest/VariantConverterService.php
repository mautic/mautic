<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;

/**
 * Class VariantConverterService.
 */
class VariantConverterService
{
    /**
     * @const integer
     */
    const DEFAULT_WEIGHT = 100;

    /**
     * @var array
     */
    private $updatedVariants = [];

    /**
     * @var bool
     */
    private $switchParent = false;

    /**
     * Converts variants for a new winner.
     *
     * @param VariantEntityInterface $winner
     */
    public function convertWinnerVariant(VariantEntityInterface $winner)
    {
        $this->setDefaultValues($winner);

        $this->switchParent = $winner->isVariant(true);

        //set this email as the parent for the original parent and children
        if ($this->switchParent === true) {
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
     * @return array
     */
    public function getUpdatedVariants()
    {
        return $this->updatedVariants;
    }

    /**
     * @param VariantEntityInterface $winner
     * @param VariantEntityInterface $oldParent
     */
    private function switchParent(VariantEntityInterface $winner, VariantEntityInterface $oldParent)
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
     * @param $variantChildren
     * @param VariantEntityInterface $winner
     */
    private function updateOldChildren($variantChildren, VariantEntityInterface $winner)
    {
        foreach ($variantChildren as $child) {
            if ($child->getId() !== $winner->getId()) {
                if ($this->switchParent === true) {
                    $this->transferChildToWinner($child, $winner);
                }
                $child->setIsPublished(false);
            }

            $this->setDefaultValues($child);

            $this->addToUpdatedVariants($child);
        }
    }

    /**
     * @param VariantEntityInterface $winner
     */
    private function updateWinnerSettings(VariantEntityInterface $winner)
    {
        $variantSettings = $winner->getVariantSettings();

        $variantSettings['totalWeight'] = self::DEFAULT_WEIGHT;

        $winner->setVariantSettings($variantSettings);
        $this->addToUpdatedVariants($winner);
    }

    /**
     * Sets oldParent settings.
     *
     * @param VariantEntityInterface $oldParent
     */
    public function updateOldParentSettings(VariantEntityInterface $oldParent)
    {
        if (method_exists($oldParent, 'setIsPublished')) {
            $oldParent->setIsPublished(false);
        }

        $this->setDefaultValues($oldParent);
    }

    /**
     * @param VariantEntityInterface $child
     * @param VariantEntityInterface $winner
     */
    private function transferChildToWinner(VariantEntityInterface $child, VariantEntityInterface $winner)
    {
        if ($this->switchParent === false) {
            return;
        }

        if ($child->getVariantParent()) {
            $child->getVariantParent()->removeVariantChild($child);
        }

        $winner->addVariantChild($child);
        $child->setVariantParent($winner);
    }

    /**
     * @param VariantEntityInterface $variant
     */
    private function addToUpdatedVariants(VariantEntityInterface $variant)
    {
        if (in_array($variant, $this->updatedVariants)) {
            return;
        }

        $this->updatedVariants[] = $variant;
    }

    /**
     * @param VariantEntityInterface $winner
     * @param VariantEntityInterface $oldParent
     */
    private function switchVariantSettings(VariantEntityInterface $winner, VariantEntityInterface $oldParent)
    {
        $winnerSettings    = $winner->getVariantSettings();
        $oldParentSettings = $oldParent->getVariantSettings();

        if(array_key_exists('winnerCriteria', $oldParentSettings)) {
            $winnerSettings['winnerCriteria'] = $oldParentSettings['winnerCriteria'];
        }

        if(array_key_exists('sendWinnerWait', $oldParentSettings)) {
            $winnerSettings['sendWinnerWait'] = $oldParentSettings['sendWinnerWait'];
        }
        $winnerSettings['totalWeight'] = self::DEFAULT_WEIGHT;

        $parentSettings = ['weight' => 0];

        $winner->setVariantSettings($winnerSettings);
        $oldParent->setVariantSettings($parentSettings);
    }

    /**
     * @param VariantEntityInterface $variant
     */
    private function setDefaultValues(VariantEntityInterface $variant)
    {
         $variant->setVariantStartDate(null);
    }
}
