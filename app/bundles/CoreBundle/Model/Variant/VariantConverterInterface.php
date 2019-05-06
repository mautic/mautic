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

interface VariantConverterInterface
{
    /**
     * @param VariantEntityInterface $winner
     */
    public function convertWinnerVariant(VariantEntityInterface $winner);
}