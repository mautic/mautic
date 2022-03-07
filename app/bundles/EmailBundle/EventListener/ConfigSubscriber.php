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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\EmailBundle\Mailer\Dsn\MailerDsnConvertor;
use Mautic\EmailBundle\Mailer\Dsn\MessengerDsnConvertor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    private array $tempFields = [
        'mailer_transport',
        'mailer_host',
        'mailer_port',
        'mailer_user',
        'mailer_password',
        'mailer_spool_type',
        'mailer_amazon_region',
        'mailer_messenger_type',
        'mailer_messenger_host',
        'mailer_messenger_port',
        'mailer_messenger_stream',
        'mailer_messenger_group',
        'mailer_messenger_auto_setup',
        'mailer_messenger_tls',
    ];

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

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addTemporaryFields($this->tempFields);
        $event->addForm([
            'bundle'     => 'EmailBundle',
            'formType'   => ConfigType::class,
            'formAlias'  => 'emailconfig',
            'formTheme'  => 'MauticEmailBundle:FormTheme\Config',
            'parameters' => $this->getParameters($event),
        ]);
    }

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
        $monitoredEmail = $this->coreParametersHelper->get('monitored_email');
        if (isset($data['monitored_email'])) {
            foreach ($data['monitored_email'] as $key => $monitor) {
                if (empty($monitor['password']) && !empty($monitoredEmail[$key]['password'])) {
                    $data['monitored_email'][$key]['password'] = $monitoredEmail[$key]['password'];
                }

                if ('general' != $key) {
                    if (empty($monitor['host']) || empty($monitor['address']) || empty($monitor['folder'])) {
                        // Reset to defaults
                        $data['monitored_email'][$key]['override_settings'] = 0;
                        $data['monitored_email'][$key]['address']           = null;
                        $data['monitored_email'][$key]['host']              = null;
                        $data['monitored_email'][$key]['user']              = null;
                        $data['monitored_email'][$key]['password']          = null;
                        $data['monitored_email'][$key]['encryption']        = '/ssl';
                        $data['monitored_email'][$key]['port']              = '993';
                    }
                }
            }
        }

        $data['mailer_dsn']           = MailerDsnConvertor::convertArrayToDsnString($data);
        $data['mailer_messenger_dsn'] = MessengerDsnConvertor::convertArrayToDsnString($data);

        foreach ($this->tempFields as $tempField) {
            unset($data[$tempField]);
        }

        $event->setConfig($data, 'emailconfig');
    }

    private function getParameters(ConfigBuilderEvent $event): array
    {
        $parameters       = $event->getParametersFromConfig('MauticEmailBundle');
        $loadedParameters = $this->coreParametersHelper->all();

        //parse dsn parameters to user friendly
        if (!empty($loadedParameters['mailer_dsn'])) {
            $mailerParameters = MailerDsnConvertor::convertDsnToArray($loadedParameters['mailer_dsn']);
            $parameters       = array_merge($parameters, $mailerParameters);
        }

        if (!empty($loadedParameters['mailer_messenger_dsn'])) {
            $messengerParameters = MessengerDsnConvertor::convertDsnToArray($loadedParameters['mailer_messenger_dsn']);
            $parameters          = array_merge($parameters, $messengerParameters);
        }

        return $parameters;
    }
}
