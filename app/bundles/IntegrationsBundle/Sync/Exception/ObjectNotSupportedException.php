<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\Exception;

class ObjectNotSupportedException extends \Exception
{
    public function __construct(string $integration, string $object)
    {
        parent::__construct("$integration does not support a $object object");
    }
}
