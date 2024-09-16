<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigMonitoredEmailType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (function_exists('imap_open')) {
            $data  = $options['data'];
            $event = new MonitoredEmailEvent($builder, $data);

            // Default email bundles
            $event->addFolder('general', '', 'mautic.email.config.monitored_email.general');

            $this->dispatcher->dispatch(EmailEvents::MONITORED_EMAIL_CONFIG, $event);

            $folderSettings = $event->getFolders();
            foreach ($folderSettings as $key => $settings) {
                $folderData = (array_key_exists($key, $data)) ? $data[$key] : [];
                $builder->add(
                    $key,
                    ConfigMonitoredMailboxesType::class,
                    [
                        'label'            => $settings['label'],
                        'mailbox'          => $key,
                        'default_folder'   => $settings['default'],
                        'data'             => $folderData,
                        'required'         => false,
                        'general_settings' => (array_key_exists('general', $data)) ? $data['general'] : [],
                    ]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'monitored_email';
    }
}
