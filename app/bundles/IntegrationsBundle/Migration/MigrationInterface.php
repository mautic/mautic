<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\IntegrationsBundle\Migration;

interface MigrationInterface
{
    /**
     * Returns true if the migration should be executed.
     */
    public function shouldExecute(): bool;

    /**
     * Execute migration if applicable.
     */
    public function execute(): void;
}
