<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * ConfigSubscriber constructor.
     *
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
            'bundle'     => 'EmailBundle',
            'formAlias'  => 'emailconfig',
            'formTheme'  => 'MauticEmailBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticEmailBundle'),
        ]);
    }

    /**
     * @param ConfigEvent $event
     */
    public function onConfigBeforeSave(ConfigEvent $event)
    {
        $event->unsetIfEmpty(
            [
                'mailer_password',
                'mailer_api_key',
            ]
        );

        $data = $event->getConfig('emailconfig');

        // Get the original data so that passwords aren't lost
        $monitoredEmail = $this->coreParametersHelper->getParameter('monitored_email');
        if (isset($data['monitored_email'])) {
            foreach ($data['monitored_email'] as $key => $monitor) {
                if (empty($monitor['password']) && !empty($monitoredEmail[$key]['password'])) {
                    $data['monitored_email'][$key]['password'] = $monitoredEmail[$key]['password'];
                }

                if ($key != 'general') {
                    if (empty($monitor['host']) || empty($monitor['address']) || empty($monitor['folder'])) {
                        $data['monitored_email'][$key]['override_settings'] = '';
                        $data['monitored_email'][$key]['address']           = '';
                        $data['monitored_email'][$key]['host']              = '';
                        $data['monitored_email'][$key]['user']              = '';
                        $data['monitored_email'][$key]['password']          = '';
                        $data['monitored_email'][$key]['ssl']               = '1';
                        $data['monitored_email'][$key]['port']              = '993';
                    }
                }
            }
        }

        $event->setConfig($data, 'emailconfig');
    }
}
