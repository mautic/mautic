<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle;

/**
 * Events available for ConfigBundle.
 */
final class ConfigEvents
{
    /**
     * The mautic.config_on_generate event is thrown when the configuration form is generated.
     *
     * The event listener receives a
     * Mautic\ConfigBundle\Event\ConfigGenerateEvent instance.
     */
    public const string CONFIG_ON_GENERATE = 'mautic.config_on_generate';

    /**
     * The mautic.config_pre_save event is thrown right before config data are saved.
     *
     * The event listener receives a Mautic\ConfigBundle\Event\ConfigEvent instance.
     */
    public const string CONFIG_PRE_SAVE = 'mautic.config_pre_save';

    /**
     * The mautic.config_post_save event is thrown right after config data are saved.
     *
     * The event listener receives a Mautic\ConfigBundle\Event\ConfigEvent instance.
     */
    public const string CONFIG_POST_SAVE = 'mautic.config_post_save';
}
