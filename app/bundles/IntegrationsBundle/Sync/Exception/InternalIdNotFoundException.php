<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\Exception;

class InternalIdNotFoundException extends \Exception
{
    public function __construct(string $object)
    {
        parent::__construct("ID for object $object not found");
    }
}
