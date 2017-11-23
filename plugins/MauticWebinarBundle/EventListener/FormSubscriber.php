<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticWebinarBundle\WebinarEvents;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                      => ['onFormBuild', 0],
            WebinarEvents::ON_FORM_SUBMIT_ACTION_TRIGGERED => ['onFormSubmitActionTriggered', 0],
        ];
    }

    /**
     * @param FormBuilderEvent $event
     */
    public function onFormBuild(FormBuilderEvent $event)
    {
        $services = $this->integrationHelper->getIntegrationObjects();
        foreach ($services as $name => $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            //Add webinar action subscribe to webinar
            if (method_exists($s, 'subscribeToWebinar')) {
                $action = [
                    'group'           => 'mautic.plugin.actions',
                    'label'           => $this->translator->trans('mautic.plugin.webinar.subscribe_contact', ['%name%' => $s->getName()]),
                    'description'     => $s->getName(),
                    'formType'        => $s->getName().'_campaignevent_webinars',
                    'formTypeOptions' => ['integration_object_name' => $s->getName()],
                    'eventName'       => WebinarEvents::ON_FORM_SUBMIT_ACTION_TRIGGERED,
                ];

                $event->addSubmitAction('plugin.form.subscribecontact_'.$s->getName(), $action);
            }
        }
    }

    /**
     * @param SubmissionEvent $event
     *
     * @return mixed
     */
    public function onFormSubmitActionTriggered(SubmissionEvent $event)
    {
        $config              = $event->getActionConfig();
        $contact             = $event->getLead();
        $subscriptionSuccess = false;
        $formName            = $event->getForm()->getName();

        if ($contact) {
            $services = $this->integrationHelper->getIntegrationObjects();
            foreach ($services as $name => $s) {
                $settings = $s->getIntegrationSettings();
                if (!$settings->isPublished() || !method_exists($s, 'subscribeToWebinar')) {
                    continue;
                }

                $subscriptionSuccess = $s->subscribeToWebinar($config, $contact, $formName);
            }
        }

        return $subscriptionSuccess;
    }
}
