<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Exception;

class ExitMonitorException extends \Exception
{
    public function __construct($message = 'Exit monitor requested', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
