<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle;

/**
 * Class ConfigEvents
 * Events available for ConfigBundle.
 */
final class ConfigEvents
{
    /**
     * The mautic.config_on_generate event is thrown when the configuration form is generated.
     *
     * The event listener receives a
     * Mautic\ConfigBundle\Event\ConfigGenerateEvent instance.
     *
     * @var string
     */
    const CONFIG_ON_GENERATE = 'mautic.config_on_generate';

    /**
     * The mautic.config_pre_save event is thrown right before config data are saved.
     *
     * The event listener receives a Mautic\ConfigBundle\Event\ConfigEvent instance.
     *
     * @var string
     */
    const CONFIG_PRE_SAVE = 'mautic.config_pre_save';

    /**
     * The mautic.config_post_save event is thrown right after config data are saved.
     *
     * The event listener receives a Mautic\ConfigBundle\Event\ConfigEvent instance.
     *
     * @var string
     */
    const CONFIG_POST_SAVE = 'mautic.config_post_save';
}
