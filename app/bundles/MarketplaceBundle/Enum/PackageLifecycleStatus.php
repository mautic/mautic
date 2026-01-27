<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Enum;

enum PackageLifecycleStatus: string
{
    case DRAFT       = 'draft';
    case SUBMITTED   = 'submitted';
    case APPROVED    = 'approved';
    case REJECTED    = 'rejected';
    case UNPUBLISHED = 'unpublished';
}
