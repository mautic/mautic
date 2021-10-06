<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\ParametersStorage;

interface ParametersStorageInterface
{
    public function isValid(): bool;

    public function read(): array;

    /**
     * @return void
     */
    public function write(array $parameters);
}
