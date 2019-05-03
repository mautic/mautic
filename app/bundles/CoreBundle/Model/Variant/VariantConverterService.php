<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model\Variant;


use Mautic\CoreBundle\Entity\VariantEntityInterface;

/**
 * Class VariantConverterService
 * @package Mautic\CoreBundle\Model\Variant
 */
class VariantConverterService
{
    /**
     *
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
     * @param VariantEntityInterface $winner
     */
    public function convertWinnerVariant(VariantEntityInterface $winner)
    {
        $this->updateWinnerSettings($winner);

        $this->switchParent = $winner->isVariant(true);

        //set this email as the parent for the original parent and children
        if ($this->switchParent === true) {
            $oldParent = $winner->getVariantParent();

            $this->switchParent($winner, $oldParent);
            $this->updateOldChildren($oldParent->getVariantChildren(), $winner);
            $this->updateOldParentSettings($oldParent);
        }
        else {
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

            $child->setVariantSentCount(0);
            $child->setVariantStartDate(null);

            $this->addToUpdatedVariants($child);
        }
    }

    /**
     * @param VariantEntityInterface $winner
     */
    private function updateWinnerSettings(VariantEntityInterface $winner)
    {
        $variantSettings = $winner->getVariantSettings();

        $variantSettings['weight']      = self::DEFAULT_WEIGHT;
        $variantSettings['weightTotal'] = self::DEFAULT_WEIGHT;

        $winner->setVariantSettings($variantSettings);
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

        $variantSettings = $oldParent->getVariantSettings();

        $variantSettings['weightTotal'] = self::DEFAULT_WEIGHT;

        $oldParent->setVariantSettings($variantSettings);
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
}