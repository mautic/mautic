<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Exception;

class FieldNotFoundException extends \Exception
{
    public function __construct($field, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf('The field (%s) is not mapped for this object.', $field), $code, $previous);
    }
}
