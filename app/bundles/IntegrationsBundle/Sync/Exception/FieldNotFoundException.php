<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\Exception;

class FieldNotFoundException extends \Exception
{
    /**
     * @param                 $field
     * @param                 $object
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($field, $object, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The %s field is not mapped for the %s object.', $field, $object), $code, $previous);
    }
}
