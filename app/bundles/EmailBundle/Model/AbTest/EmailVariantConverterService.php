<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Model\AbTest\VariantConverterService;
use Mautic\EmailBundle\Entity\Email;

class EmailVariantConverterService
{
    public function __construct(private readonly VariantConverterService $variantConverterService)
    {
    }

    public function convertWinnerVariant(Email $email): void
    {
        $this->variantConverterService->convertWinnerVariant($email);
    }

    /**
     * @return array<VariantEntityInterface>
     */
    public function getUpdatedVariants(): array
    {
        return $this->variantConverterService->getUpdatedVariants();
    }
}
