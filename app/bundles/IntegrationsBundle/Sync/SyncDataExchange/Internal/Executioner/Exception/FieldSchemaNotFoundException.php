<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\Exception;

use Exception;

class FieldSchemaNotFoundException extends Exception
{
    public function __construct(string $object, string $alias)
    {
        parent::__construct(sprintf('Schema for alias "%s" of object "%s" not found', $alias, $object));
    }
}
