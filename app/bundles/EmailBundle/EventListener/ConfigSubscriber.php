<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\EmailBundle\Helper\MailerDsnConvertor;
use Mautic\EmailBundle\Model\TransportType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    private TransportType $transportType;

    /**
     * Temp fields that will not be saved in env file
     * but will be converted to Dsn string.
     *
     * @var array<string>
     */
    private array $tempFields = [
        'mailer_transport',
        'mailer_host',
        'mailer_port',
        'mailer_user',
        'mailer_password',
        'mailer_encryption',
        'mailer_auth_mode',
        'mailer_api_key',
    ];

    public function __construct(CoreParametersHelper $coreParametersHelper, TransportType $transportType)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->transportType        = $transportType;
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

        $data['mailer_dsn']       = MailerDsnConvertor::convertArrayToDsnString($data, $this->transportType->getTransportDsnConvertors());

        // remove options that are now part of the DSN string
        $mailerKeys = array_filter($data, function ($key) {
            return 0 === strpos($key, 'mailer_option');
        }, ARRAY_FILTER_USE_KEY);

        $removeKeys = \array_merge($this->tempFields, $mailerKeys);

        // remove the parameters that are not to be saved in the env file
        foreach ($removeKeys as $key => $tempField) {
            unset($data[$key]);
        }

        $event->setConfig($data, 'emailconfig');
    }

    /**
     * return parsed paramters from the config.
     *
     * @param ConfigBuilderEvent $event config builder event
     *
     * @return array<string, string> the parsed parameters
     */
    private function getParameters(ConfigBuilderEvent $event): array
    {
        $parameters       = $event->getParametersFromConfig('MauticEmailBundle');
        $loadedParameters = $this->coreParametersHelper->all();

        //parse dsn parameters to user friendly
        if (!empty($loadedParameters['mailer_dsn'])) {
            $mailerParameters = MailerDsnConvertor::convertDsnToArray($loadedParameters['mailer_dsn']);
            $parameters       = array_merge($parameters, $mailerParameters);
        }

        return $parameters;
    }
}
