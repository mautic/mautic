<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PluginBundle\PluginEvents;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    use PushToIntegrationTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                     => ['onFormBuild', 0],
            PluginEvents::ON_FORM_SUBMIT_ACTION_TRIGGERED => ['onFormSubmitActionTriggered', 0],
        ];
    }

    /**
     * @param FormBuilderEvent $event
     */
    public function onFormBuild(FormBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.plugin.actions',
            'description' => 'mautic.plugin.actions.tooltip',
            'label'       => 'mautic.plugin.actions.push_lead',
            'formType'    => 'integration_list',
            'formTheme'   => 'MauticPluginBundle:FormTheme\Integration',
            'eventName'   => PluginEvents::ON_FORM_SUBMIT_ACTION_TRIGGERED,
        ];
        $event->addSubmitAction('plugin.leadpush', $action);
    }

    /**
     * @param SubmissionEvent $event
     *
     * @return mixed
     */
    public function onFormSubmitActionTriggered(SubmissionEvent $event)
    {
        $config  = $event->getActionConfig();
        $lead    = $event->getLead();

        $this->pushToIntegration($config, $lead);
    }
}
