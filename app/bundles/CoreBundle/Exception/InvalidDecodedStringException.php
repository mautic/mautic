<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Exception;

class InvalidDecodedStringException extends \InvalidArgumentException
{
    public function __construct(string $string = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('The string %s is not a serialized array', $string), $code, $previous);
    }
}
