<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;

interface GeneratedColumnsInterface extends \Iterator
{
    public function add(GeneratedColumn $generatedColumn): void;

    /**
     * @throws \UnexpectedValueException
     */
    public function getForOriginalDateColumnAndUnit(string $originalDateColumn, string $unit): GeneratedColumnInterface;
}
