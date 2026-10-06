<?php

declare(strict_types=1);

namespace Mautic\ApiBundle;

final class ApiEvents
{
    /**
     * The mautic.api_pre_serialization_context event is dispatched before the serialization context is created for the view.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiSerializationContextEvent instance.
     */
    public const string API_PRE_SERIALIZATION_CONTEXT = 'mautic.api_pre_serialization_context';

    /**
     * The mautic.api_post_serialization_context event is dispatched after the serialization context is created for the view.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiSerializationContextEvent instance.
     */
    public const string API_POST_SERIALIZATION_CONTEXT = 'mautic.api_post_serialization_context';
}
