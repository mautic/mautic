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

namespace Mautic\CoreBundle\Helper;

interface PageHelperInterface
{
    public function getLimit(): int;

    /**
     * The number of entities can be less then the current page so calculate the last page.
     */
    public function countPage(int $count): int;

    public function getStart(): int;

    /**
     * Remember what page currently on so that we can return here after form submission/cancellation.
     */
    public function rememberPage(int $page): void;
}
