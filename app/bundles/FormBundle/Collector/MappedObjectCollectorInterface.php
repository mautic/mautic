<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\MappedObjectCollection;

interface MappedObjectCollectorInterface
{
    public function buildCollection(string ...$objects): MappedObjectCollection;
}
