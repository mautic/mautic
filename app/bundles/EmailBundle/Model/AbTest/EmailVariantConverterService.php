<?php

namespace Mautic\EmailBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Model\AbTest\VariantConverterService;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class EmailVariantConverterService.
 */
class EmailVariantConverterService
{
    /**
     * @var VariantConverterService
     */
    private $variantConverterService;

    /**
     * EmailVariantConverterService constructor.
     */
    public function __construct(VariantConverterService $variantConverterService)
    {
        $this->variantConverterService = $variantConverterService;
    }

    public function convertWinnerVariant(Email $email): void
    {
        $this->variantConverterService->convertWinnerVariant($email);

        $this->setDefaultValues();
    }

    /**
     * @return array<VariantEntityInterface>
     */
    public function getUpdatedVariants()
    {
        return $this->variantConverterService->getUpdatedVariants();
    }

    /**
     * Sets default values for AB test variants.
     */
    private function setDefaultValues(): void
    {
        /** @var Email $variant */
        foreach ($this->getUpdatedVariants() as $variant) {
            $variant->setVariantSentCount(0);
        }
    }
}
