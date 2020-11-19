<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\PageBundle\Form\Type\ConfigTrackingPageType;
use Mautic\PageBundle\Form\Type\ConfigType;
use MauticPlugin\MauticFocusBundle\Form\Type\FocusConfigTrackingPageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerateTracking', 0],
        ];
    }

    public function onConfigGenerateTracking(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'FocusBundle',
            'formAlias'  => 'focusconfig',
            'formType'   => FocusConfigTrackingPageType::class,
            'formTheme'  => 'MauticFocusBundle:FormTheme\Config',
            'parameters' => [
                'focus_pixel_enabled' => true,
            ],
        ]);
    }
}
