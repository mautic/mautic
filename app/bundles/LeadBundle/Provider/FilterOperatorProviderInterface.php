<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

interface FilterOperatorProviderInterface
{
    /**
     * Finds all operators and reutrn them in an array.
     */
    public function getAllOperators(): array;
}
