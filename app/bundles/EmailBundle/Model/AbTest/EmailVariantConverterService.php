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
    }

    /**
     * @return array<VariantEntityInterface>
     */
    public function getUpdatedVariants()
    {
        return $this->variantConverterService->getUpdatedVariants();
    }
}
