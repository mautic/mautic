<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/1/14
 * Time: 8:51 PM
 */

namespace Mautic\CoreBundle\Exception;


class AjaxErrorException extends \Exception
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}