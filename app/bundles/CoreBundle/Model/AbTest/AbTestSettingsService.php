<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\EmailBundle\Entity\Email;

class AbTestSettingsService
{
    /**
     * @var int
     */
    public const DEFAULT_TOTAL_WEIGHT = 100;

    public const DEFAULT_AB_WEIGHT = 10;

    private ?int $allPublishedVariantsWeight = null;

    /**
     * @var array<int,array<string|int>>
     */
    private array $variantsSettings = [];

    private ?string $winnerCriteria = null;

    private int $totalWeight = 0;

    private int $sendWinnerDelay = 0;

    private bool $configurationError = false;

    private bool $setCriteriaFromVariants = false;

    /**
     * @return array<mixed>
     */
    public function getAbTestSettings(VariantEntityInterface $variant): array
    {
        $parentVariant = $variant->getVariantParent();
        if (!$parentVariant instanceof VariantEntityInterface) {
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

    public function getSendWinnerDelay(Email $entity): ?int
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
        $this->sendWinnerDelay            = 0;
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
        $weight           = $variantsSettings['weight'] ?? 0;
        $parentVariant    = $variant->getVariantParent();

        if ($parentVariant instanceof VariantEntityInterface) {
            $weight = (int) floor(100 / (count($parentVariant->getVariantChildren()) + 1));
        }

        $this->setVariantSettingsWeight($variant, $weight);

        if (true === $this->setCriteriaFromVariants && array_key_exists('winnerCriteria', $variantsSettings)) {
            $this->setWinnerCriteriaFromVariant($variantsSettings['winnerCriteria']);
        }
    }

    private function setVariantSettingsWeight(VariantEntityInterface|FormEntity $variant, int $weight): void
    {
        if ($variant->getIsPublished()) {
            $variantWeight                                       = (int) round(($weight / 100) * $this->totalWeight);
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
     */
    private function addPublishedVariantWeight(int|float $weight): void
    {
        $this->allPublishedVariantsWeight += $weight;
    }

    /**
     * Sets winner criteria from variant children (for BC of old variants).
     */
    private function setWinnerCriteriaFromVariant(string $variantCriteria): void
    {
        if (!empty($this->winnerCriteria) && $variantCriteria !== $this->winnerCriteria) {
            // there are variants with different winner criteria
            $this->configurationError = true;
        } else {
            $this->winnerCriteria = $variantCriteria;
        }
    }
}
