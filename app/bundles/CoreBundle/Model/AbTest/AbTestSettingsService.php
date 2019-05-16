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
 * Class AbTestSettingsService.
 * Reads configuration from variants and returns configuration set for AB test.
 * Helps with BC of old variants that have settings in variant children.
 */
class AbTestSettingsService
{
    /**
     * @const integer
     */
    const DEFAULT_TOTAL_WEIGHT = 100;

    /**
     * @var int
     */
    private $allPublishedVariantsWeight;

    /**
     * @var array
     */
    private $variantsSettings;

    /**
     * @var string
     */
    private $winnerCriteria;

    /**
     * @var int
     */
    private $totalWeight;

    /**
     * @var int
     */
    private $sendWinnerDelay;

    /**
     * @var bool
     */
    private $configurationError;

    /**
     * @var bool
     */
    private $setCriteriaFromVariants;

    /**
     * @param VariantEntityInterface $variant
     *
     * @return array
     */
    public function getAbTestSettings(VariantEntityInterface $variant)
    {
        $parentVariant = $variant->getVariantParent();
        if (empty($parentVariant)) {
            $parentVariant = $variant;
        }

        $this->init();
        $this->setGeneralSettings($parentVariant);
        $this->setVariantsSettings($parentVariant);

        $settings = [];

        $settings['variants']            = $this->variantsSettings;
        $settings['winnerCriteria']      = $this->winnerCriteria;
        $settings['totalWeight']         = $this->totalWeight;
        $settings['sendWinnerDelay']     = $this->sendWinnerDelay;
        $settings['configurationError']  = $this->configurationError;

        return $settings;
    }

    /**
     * Sets default values.
     */
    private function init()
    {
        $this->variantsSettings           = [];
        $this->winnerCriteria             = null;
        $this->allPublishedVariantsWeight = 0;
        $this->totalWeight                = self::DEFAULT_TOTAL_WEIGHT;
        $this->configurationError         = false;
        $this->setCriteriaFromVariants    = false;
    }

    /**
     * @param VariantEntityInterface $parentVariant
     */
    private function setGeneralSettings(VariantEntityInterface $parentVariant)
    {
        $parentSettings = $parentVariant->getVariantSettings();
        if (isset($parentSettings['totalWeight'])) {
            $this->totalWeight = $parentSettings['totalWeight'];
        } else {
            $this->totalWeight = self::DEFAULT_TOTAL_WEIGHT;
        }

        if (isset($parentSettings['sendWinnerDelay'])) {
            $this->sendWinnerDelay = $parentSettings['sendWinnerDelay'];
        }

        if (isset($parentSettings['winnerCriteria'])) {
            $this->winnerCriteria = $parentSettings['winnerCriteria'];
        } else {
            $this->setCriteriaFromVariants = true;
        }
    }

    /**
     * @param VariantEntityInterface $parentVariant
     */
    private function setVariantsSettings(VariantEntityInterface $parentVariant)
    {
        $variants = $parentVariant->getVariantChildren();

        foreach ($variants as $variant) {
            $this->setVariantSettings($variant);
        }
        $this->setParentSettingsWeight($parentVariant);
    }

    /**
     * @param VariantEntityInterface $variant
     */
    private function setVariantSettings(VariantEntityInterface $variant)
    {
        $variantsSettings = $variant->getVariantSettings();
        $weight           = isset($variantsSettings['weight']) ? $variantsSettings['weight'] : 0;
        $this->setVariantSettingsWeight($variant, $weight);

        if ($this->setCriteriaFromVariants === true && array_key_exists('winnerCriteria', $variantsSettings)) {
            $this->setWinnerCriteriaFromVariant($variantsSettings['winnerCriteria']);
        }
    }

    /**
     * @param VariantEntityInterface $variant
     * @param $weight
     */
    private function setVariantSettingsWeight(VariantEntityInterface $variant, $weight)
    {
        if ($variant->isPublished()) {
            $this->variantsSettings[$variant->getId()]['weight'] = $weight;
            $this->addPublishedVariantWeight($weight);
        } else {
            $this->variantsSettings[$variant->getId()]['weight'] = 0;
        }
    }

    /**
     * @param VariantEntityInterface $parentVariant
     */
    private function setParentSettingsWeight(VariantEntityInterface $parentVariant)
    {
        if ($this->totalWeight < $this->allPublishedVariantsWeight) {
            // published variants weight exceeds total weight
            $this->configurationError = true;
        }
        $this->variantsSettings[$parentVariant->getId()]['weight'] = $this->totalWeight - $this->allPublishedVariantsWeight;
    }

    /**
     * Adds variant weight for further calculation.
     *
     * @param int $weight
     */
    private function addPublishedVariantWeight($weight)
    {
        $this->allPublishedVariantsWeight += $weight;
    }

    /**
     * Sets winner criteria from variant children (for BC of old variants).
     *
     * @param string $variantCriteria
     */
    private function setWinnerCriteriaFromVariant($variantCriteria)
    {
        if (!empty($this->winnerCriteria) && $variantCriteria != $this->winnerCriteria) {
            // there are variants with different winner criteria
            $this->configurationError = true;
        } else {
            $this->winnerCriteria = $variantCriteria;
        }
    }
}
