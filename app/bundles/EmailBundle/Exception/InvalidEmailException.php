<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Exception;

use Throwable;

/**
 * Class InvalidEmailException.
 */
class InvalidEmailException extends \Exception
{
    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * InvalidEmailException constructor.
     *
     * @param string         $emailAddress
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($emailAddress, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->emailAddress = $emailAddress;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
}
