<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Serializer\Exclusion;

/**
 * Only include the first level of a children/parent of an entity that relates to itself.
 */
final class ParentChildrenExclusionStrategy extends FieldExclusionStrategy
{
    public function __construct(int $level = 3)
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
