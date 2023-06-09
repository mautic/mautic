<?php

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\EmailBundle\Entity\Email;

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
    public const DEFAULT_TOTAL_WEIGHT = 100;

    public const DEFAULT_AB_WEIGHT = 10;

    /**
     * @var int
     */
    private $allPublishedVariantsWeight;

    /**
     * @var array<int,array<string|int>>
     */
    private $variantsSettings;

    /**
     * @var string|null
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
     * @return array<mixed>
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
     * @return int|null
     */
    public function getSendWinnerDelay(Email $entity)
    {
        $settings = $this->getAbTestSettings($entity);
        if ($settings['totalWeight'] < self::DEFAULT_TOTAL_WEIGHT
            && $settings['sendWinnerDelay'] > 0) {
            return $settings['sendWinnerDelay'];
        }

        return null;
    }

    /**
     * Sets default values.
     */
    private function init(): void
    {
        $this->variantsSettings           = [];
        $this->winnerCriteria             = null;
        $this->allPublishedVariantsWeight = 0;
        $this->totalWeight                = self::DEFAULT_TOTAL_WEIGHT;
        $this->configurationError         = false;
        $this->setCriteriaFromVariants    = false;
    }

    private function setGeneralSettings(VariantEntityInterface $parentVariant): void
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

    private function setVariantsSettings(VariantEntityInterface $parentVariant): void
    {
        $variants = $parentVariant->getVariantChildren();

        foreach ($variants as $variant) {
            $this->setVariantSettings($variant);
        }
        $this->setParentSettingsWeight($parentVariant);
    }

    private function setVariantSettings(VariantEntityInterface $variant): void
    {
        $variantsSettings = $variant->getVariantSettings();
        $weight           = isset($variantsSettings['weight']) ? $variantsSettings['weight'] : 0;
        $this->setVariantSettingsWeight($variant, $weight);

        if (true === $this->setCriteriaFromVariants && array_key_exists('winnerCriteria', $variantsSettings)) {
            $this->setWinnerCriteriaFromVariant($variantsSettings['winnerCriteria']);
        }
    }

    /**
     * @param VariantEntityInterface|FormEntity $variant
     * @param int                               $weight
     */
    private function setVariantSettingsWeight($variant, $weight): void
    {
        if ($variant->getIsPublished()) {
            $variantWeight                                       = round(($weight / 100) * $this->totalWeight);
            $this->variantsSettings[$variant->getId()]['weight'] = $variantWeight;
            $this->addPublishedVariantWeight($variantWeight);
        } else {
            $this->variantsSettings[$variant->getId()]['weight'] = 0;
        }
    }

    private function setParentSettingsWeight(VariantEntityInterface $parentVariant): void
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
     * @param int|float $weight
     */
    private function addPublishedVariantWeight($weight): void
    {
        $this->allPublishedVariantsWeight += $weight;
    }

    /**
     * Sets winner criteria from variant children (for BC of old variants).
     *
     * @param string $variantCriteria
     */
    private function setWinnerCriteriaFromVariant($variantCriteria): void
    {
        if (!empty($this->winnerCriteria) && $variantCriteria != $this->winnerCriteria) {
            // there are variants with different winner criteria
            $this->configurationError = true;
        } else {
            $this->winnerCriteria = $variantCriteria;
        }
    }
}
