<?php

namespace Mautic\CoreBundle\Batches\Exception;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;

/**
 * Exception that handles failing run of batch action.
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class BatchActionFailException extends \Exception
{
    /**
     * Thrown when batch action does not exist in batch group
     *
     * @param string                $actionType
     * @param BatchGroupInterface   $batchGroup
     *
     * @return BatchActionFailException
     */
    public static function unknownActionTypeInGroup($actionType, BatchGroupInterface $batchGroup)
    {
        return new self(sprintf(
            'Unknown batch action type "%s" in group %s. Please check registration inside group class.',
            $actionType,
            $batchGroup
        ));
    }

    /**
     * Thrown when source adapter hasn't been set
     *
     * @return BatchActionFailException
     */
    public static function sourceAdapterNotSet()
    {
        return new self('Source adapter must be set to run a batch action.');
    }

    /**
     * Thrown when handler adapter hasn't been set
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
     * @param object $object
     * @param HandlerAdapterInterface $handlerAdapter
     *
     * @return BatchActionFailException
     */
    public static function sourceInHandlerNotImplementedYet($object, HandlerAdapterInterface $handlerAdapter)
    {
        return new self(sprintf('Source of class %s hasn\'t been implemented in handler %s', get_class($object), get_class($handlerAdapter)));
    }
}