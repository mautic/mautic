<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;

interface ReferenceResolverInterface
{
    /**
     * @param ObjectChangeDAO[] $changedObjects
     */
    public function resolveReferences(string $objectName, array $changedObjects): void;
}
