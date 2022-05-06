<?php

namespace Mautic\ApiBundle\Serializer\Exclusion;

/**
 * Class ParentChildrenExclusionStrategy.
 *
 * Only include the first level of a children/parent of an entity that relates to itself
 */
class ParentChildrenExclusionStrategy extends FieldExclusionStrategy
{
    /**
     * ParentChildrenExclusionStrategy constructor.
     *
     * @param int $level
     */
    public function __construct($level = 3)
    {
        parent::__construct(
            [
                'parent',
                'children',
            ],
            $level
        );
    }
}
