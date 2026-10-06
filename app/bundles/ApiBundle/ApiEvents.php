<?php

declare(strict_types=1);

namespace Mautic\ApiBundle;

final class ApiEvents
{
    /**
     * The mautic.client_pre_save event is thrown right before an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     */
    public const string CLIENT_PRE_SAVE = 'mautic.client_pre_save';

    /**
     * The mautic.client_post_save event is thrown right after an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     */
    public const string CLIENT_POST_SAVE = 'mautic.client_post_save';

    /**
     * The mautic.client_post_delete event is thrown after an API client is deleted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     */
    public const string CLIENT_POST_DELETE = 'mautic.client_post_delete';

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
