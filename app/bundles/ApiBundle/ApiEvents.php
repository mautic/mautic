<?php

namespace Mautic\ApiBundle;

final class ApiEvents
{
    /**
     * The mautic.client_pre_save event is thrown right before an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    public const CLIENT_PRE_SAVE = 'mautic.client_pre_save';

    /**
     * The mautic.client_post_save event is thrown right after an API client is persisted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    public const CLIENT_POST_SAVE = 'mautic.client_post_save';

    /**
     * The mautic.client_post_delete event is thrown after an API client is deleted.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ClientEvent instance.
     *
     * @var string
     */
    public const CLIENT_POST_DELETE = 'mautic.client_post_delete';

    /**
     * The mautic.build_api_route event is thrown to build Mautic API routes.
     *
     * The event listener receives a Mautic\CoreBundle\Event\RouteEvent instance.
     *
     * @var string
     */
    public const BUILD_ROUTE = 'mautic.build_api_route';

    /**
     * The mautic.api_on_entity_pre_save event is thrown after an entity about to be saved via API.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiEntityEvent instance.
     *
     * @var string
     */
    public const API_ON_ENTITY_PRE_SAVE = 'mautic.api_on_entity_pre_save';

    /**
     * The mautic.api_on_entity_post_save event is thrown after an entity is saved via API.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiEntityEvent instance.
     *
     * @var string
     */
    public const API_ON_ENTITY_POST_SAVE = 'mautic.api_on_entity_post_save';

    /**
     * The mautic.api_pre_serialization_context event is dispatched before the serialization context is created for the view.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiSerializationContextEvent instance.
     *
     * @var string
     */
    public const API_PRE_SERIALIZATION_CONTEXT = 'mautic.api_pre_serialization_context';

    /**
     * The mautic.api_post_serialization_context event is dispatched after the serialization context is created for the view.
     *
     * The event listener receives a Mautic\ApiBundle\Event\ApiSerializationContextEvent instance.
     *
     * @var string
     */
    public const API_POST_SERIALIZATION_CONTEXT = 'mautic.api_post_serialization_context';
}
