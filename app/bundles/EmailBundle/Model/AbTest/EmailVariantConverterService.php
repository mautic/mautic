<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model\AbTest;

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
     *
     * @param VariantConverterService $variantConverterService
     */
    public function __construct(VariantConverterService $variantConverterService)
    {
        $this->variantConverterService = $variantConverterService;
    }

    /**
     * @param Email $email
     */
    public function convertWinnerVariant(Email $email)
    {
        $this->variantConverterService->convertWinnerVariant($email);

        $this->setDefaultValues();
    }

    /**
     * @return array
     */
    public function getUpdatedVariants()
    {
        return $this->variantConverterService->getUpdatedVariants();
    }

    /**
     * Sets default values for AB test variants.
     */
    private function setDefaultValues()
    {
        foreach ($this->getUpdatedVariants() as $variant) {
            $variant->setVariantSentCount(0);
        }
    }
}
