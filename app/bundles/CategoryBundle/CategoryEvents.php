<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle;

/**
 * Events available for CategoryBundle.
 */
final class CategoryEvents
{
    /**
     * The mautic.category_on_bundle_list_build event is thrown when a list of bundles supporting categories is build.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryTypesEvent instance.
     */
    public const string CATEGORY_ON_BUNDLE_LIST_BUILD = 'mautic.category_on_bundle_list_build';
}
