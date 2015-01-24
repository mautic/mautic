<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
/**
 * Class ConfigSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class ConfigSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            ConfigEvents::CONFIG_ON_GENERATE => array('onConfigGenerate', 0)
        );
    }

    public function onConfigGenerate (ConfigBuilderEvent $event)
    {
        $event->addForm(array(
            'bundle'     => 'MauticChatBundle',
            'formAlias'  => 'chatconfig',
            'formTheme'  => 'MauticChatBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticChatBundle')
        ));
    }
}