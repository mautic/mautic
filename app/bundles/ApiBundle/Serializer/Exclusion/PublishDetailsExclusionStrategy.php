<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Serializer\Exclusion;

/**
 * Class PublishDetailsExclusionStrategy.
 *
 * Only include FormEntity properties for the top level entity and not the associated entities
 */
class PublishDetailsExclusionStrategy extends FieldExclusionStrategy
{
    /**
     * PublishDetailsExclusionStrategy constructor.
     */
    public function __construct()
    {
        parent::__construct(
            [
                'isPublished',
                'dateAdded',
                'createdBy',
                'dateModified',
                'modifiedBy',
                'checkedOut',
                'checkedOutBy',
            ],
            1
        );
    }
}
