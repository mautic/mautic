<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Exception;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;

/**
 * Exception that handles failing run of batch action.
 */
class BatchActionFailException extends \Exception
{
    /**
     * Thrown when source adapter hasn't been set.
     *
     * @return BatchActionFailException
     */
    public static function sourceAdapterNotSet()
    {
        return new self('Source adapter must be set to run a batch action.');
    }

    /**
     * Thrown when handler adapter hasn't been set.
     *
     * @return BatchActionFailException
     */
    public static function handlerAdapterNotSet()
    {
        return new self('Handler adapter must be set to run a batch action.');
    }

    /**
     * Thrown when you try to pass a source to a handler unless it is implemented.
     *
     * @param object                  $object
     * @param HandlerAdapterInterface $handlerAdapter
     *
     * @return BatchActionFailException
     */
    public static function sourceInHandlerNotImplementedYet($object, HandlerAdapterInterface $handlerAdapter)
    {
        return new self(sprintf('Source of class %s hasn\'t been implemented in handler %s', get_class($object), get_class($handlerAdapter)));
    }
}
