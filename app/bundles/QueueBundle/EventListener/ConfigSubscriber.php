<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\QueueBundle\Form\Type\ConfigType;

class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigBeforeSave', 0],
        ];
    }

    /**
     * @param ConfigBuilderEvent $event
     */
    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'QueueBundle',
            'formAlias'  => 'queueconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticQueueBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticQueueBundle'),
        ]);
    }

    /**
     * @param ConfigEvent $event
     */
    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $data = $event->getConfig('queueconfig');

        // Don't erase password if someone doesn't provide it
        foreach ($data as $key => $value) {
            if (empty($value) && false !== strpos($key, 'password')) {
                $data[$key] = $this->coreParametersHelper->getParameter($key);
            }
        }

        $event->setConfig($data, 'queueconfig');
    }
}
