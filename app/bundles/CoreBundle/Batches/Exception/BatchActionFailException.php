<?php

namespace Mautic\CoreBundle\Batches\Exception;

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
        return new BatchActionFailException(sprintf(
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
        return new BatchActionFailException('Source adapter must be set to run a batch action.');
    }

    /**
     * Thrown when handler adapter hasn't been set
     *
     * @return BatchActionFailException
     */
    public static function handlerAdapterNotSet()
    {
        return new BatchActionFailException('Handler adapter must be set to run a batch action.');
    }
}