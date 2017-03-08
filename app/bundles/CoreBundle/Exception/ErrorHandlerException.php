<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Exception;

use Exception;

class ErrorHandlerException extends \Exception
{
    /**
     * @var bool
     */
    protected $showMessage = false;

    /**
     * ErrorHandlerException constructor.
     *
     * @param string         $message
     * @param bool           $showMessage
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($message = '', $showMessage = false, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->showMessage = $showMessage;
    }

    /**
     * @return bool
     */
    public function showMessage()
    {
        return $this->showMessage;
    }
}
