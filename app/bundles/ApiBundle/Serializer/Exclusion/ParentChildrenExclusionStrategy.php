<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
