<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;

final class FormSubscriber
{
    use PushToIntegrationTrait;
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: FormBuilderEvent::class,
        priority: 0
    )]
    public function onFormBuild(FormBuilderEvent $event): void
    {
        $event->addSubmitAction('plugin.leadpush', [
            'group'       => 'mautic.plugin.actions',
            'description' => 'mautic.plugin.actions.tooltip',
            'label'       => 'mautic.plugin.actions.push_lead',
            'formType'    => IntegrationsListType::class,
            'formTheme'   => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
            'eventName'   => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'template'    => '@MauticPlugin/Action/integration.html.twig',
        ]);
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: FormEvents::ON_EXECUTE_SUBMIT_ACTION,
        priority: 0
    )]
    public function onFormSubmitActionTriggered(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('plugin.leadpush')) {
            return;
        }

        $this->pushToIntegration($event->getActionConfig(), $event->getSubmission()->getLead());
    }
}
