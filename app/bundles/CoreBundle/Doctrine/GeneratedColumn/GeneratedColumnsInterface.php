<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

interface GeneratedColumnsInterface extends \Iterator
{
    public function add(GeneratedColumn $generatedColumn);

    /**
     * @param string $originalDateColumn
     * @param string $unit
     */
    public function getForOriginalDateColumnAndUnit($originalDateColumn, $unit);
}
