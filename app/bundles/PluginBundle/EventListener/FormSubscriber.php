<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    use PushToIntegrationTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD            => ['onFormBuild', 0],
            FormEvents::ON_EXECUTE_SUBMIT_ACTION => ['onFormSubmitActionTriggered', 0],
        ];
    }

    public function onFormBuild(FormBuilderEvent $event)
    {
        $event->addSubmitAction('plugin.leadpush', [
            'group'       => 'mautic.plugin.actions',
            'description' => 'mautic.plugin.actions.tooltip',
            'label'       => 'mautic.plugin.actions.push_lead',
            'formType'    => IntegrationsListType::class,
            'formTheme'   => 'MauticPluginBundle:FormTheme\Integration',
            'eventName'   => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
        ]);
    }

    /**
     * @return mixed
     */
    public function onFormSubmitActionTriggered(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('plugin.leadpush')) {
            return;
        }

        $this->pushToIntegration($event->getActionConfig(), $event->getSubmission()->getLead());
    }
}
