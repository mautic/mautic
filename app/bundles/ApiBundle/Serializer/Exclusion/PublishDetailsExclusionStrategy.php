<?php

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
