<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Exception;

use Throwable;

class NumberNotFoundException extends \Exception
{
    /***
     * @var string
     */
    private $number;

    /**
     * NumberNotFoundException constructor.
     *
     * @param string         $number
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($number, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->number = $number;

        if (!$message) {
            $message = "Phone number '{$number}' not found";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }
}
