<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigMonitoredEmailType.
 */
class ConfigMonitoredEmailType extends AbstractType
{
    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (function_exists('imap_open')) {
            $data = $options['data'];

            $event = new MonitoredEmailEvent($builder, $data);

            // Default email bundles
            $event->addFolder('general', '', 'mautic.email.config.monitored_email.general');
            $event->addFolder('EmailBundle', 'bounces', 'mautic.email.config.monitored_email.bounce_folder');
            $event->addFolder('EmailBundle', 'unsubscribes', 'mautic.email.config.monitored_email.unsubscribe_folder');
            $event->addFolder('EmailBundle', 'reply', 'mautic.email.config.monitored_email.reply_folder');

            if ($this->factory->getDispatcher()->hasListeners(EmailEvents::MONITORED_EMAIL_CONFIG)) {
                $this->factory->getDispatcher()->dispatch(EmailEvents::MONITORED_EMAIL_CONFIG, $event);
            }

            $folderSettings = $event->getFolders();
            foreach ($folderSettings as $key => $settings) {
                $folderData = (array_key_exists($key, $data)) ? $data[$key] : [];
                $builder->add(
                    $key,
                    'monitored_mailboxes',
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
    public function getName()
    {
        return 'monitored_email';
    }
}
