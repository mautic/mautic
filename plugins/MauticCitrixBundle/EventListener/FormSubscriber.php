<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => ['onFormBuilder', 0],
            CitrixEvents::ON_FORM_SUBMIT_ACTION => ['onFormSubmit', 0],
            CitrixEvents::ON_FORM_VALIDATE_ACTION => ['onFormValidate', 0],
        );
    }

    /**
     *
     *
     * @param Events\ValidationEvent $event
     */
    public function onFormValidate(Events\ValidationEvent $event)
    {
        $translator = CitrixHelper::getContainer()->get('translator');
        $logger = CitrixHelper::getContainer()->get('monolog.logger.mautic');
        $field = $event->getField();
        $eventType = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
        $eventId = $event->getValue();
       // $event->failedValidation($translator->trans('plugin.citrix.'.$eventType.'.nolongeravailable'));
    }

    /**
     *
     *
     * @param Events\SubmissionEvent $event
     */
    public function onFormSubmit(Events\SubmissionEvent $event)
    {
        $logger = CitrixHelper::getContainer()->get('monolog.logger.mautic');
        // Make sure the form is still applicable
        $form = $event->getSubmission()->getForm();
        $post = $event->getPost();
       // $logger->log('error', print_r($event, true));

//        if ((int)$form->getId() === (int)$focus->getForm()) {
//            $this->model->addStat($focus, Stat::TYPE_FORM, $event->getSubmission());
//        }
    }

    /**
     * Add a simple email form
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        // Register form submit actions
        $action = [
            'group' => 'plugin.citrix.webinar.header',
            'description' => 'plugin.citrix.webinar.header.tooltip',
            'label' => 'plugin.citrix.webinar.subscribe',
            'formType' => 'citrix_submit_action',
            'template' => 'MauticFormBundle:Action:generic.html.php',
            'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
        ];
        $event->addSubmitAction('plugin.citrix.subscribe.webinar', $action);

        $action = [
            'group' => 'plugin.citrix.meeting.header',
            'description' => 'plugin.citrix.meeting.header.tooltip',
            'label' => 'plugin.citrix.meeting.subscribe',
            'formType' => 'citrix_submit_action',
            'template' => 'MauticFormBundle:Action:generic.html.php',
            'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
        ];
        $event->addSubmitAction('plugin.citrix.subscribe.meeting', $action);

        $action = [
            'group' => 'plugin.citrix.training.header',
            'description' => 'plugin.citrix.training.header.tooltip',
            'label' => 'plugin.citrix.training.subscribe',
            'formType' => 'citrix_submit_action',
            'template' => 'MauticFormBundle:Action:generic.html.php',
            'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
        ];
        $event->addSubmitAction('plugin.citrix.subscribe.training', $action);

        $action = [
            'group' => 'plugin.citrix.assist.header',
            'description' => 'plugin.citrix.assist.header.tooltip',
            'label' => 'plugin.citrix.assist.subscribe',
            'formType' => 'citrix_submit_action',
            'template' => 'MauticFormBundle:Action:generic.html.php',
            'eventName' => CitrixEvents::ON_FORM_SUBMIT_ACTION,
        ];
        $event->addSubmitAction('plugin.citrix.subscribe.assist', $action);

        // Register form fields

        $field = [
            'label' => 'plugin.citrix.webinar.listfield',
            'formType' => 'citrix_list',
            'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
            'listType' => 'webinars',
        ];
        $event->addFormField('plugin.citrix.select.webinar', $field);

        $field = [
            'label' => 'plugin.citrix.meeting.listfield',
            'formType' => 'citrix_list',
            'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
            'listType' => 'meetings',
        ];
        $event->addFormField('plugin.citrix.select.meeting', $field);

        $field = [
            'label' => 'plugin.citrix.training.listfield',
            'formType' => 'citrix_list',
            'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
            'listType' => 'trainings',
        ];
        $event->addFormField('plugin.citrix.select.training', $field);

        $field = [
            'label' => 'plugin.citrix.assist.listfield',
            'formType' => 'citrix_list',
            'template' => 'MauticCitrixBundle:Field:citrixlist.html.php',
            'listType' => 'assists',
        ];
        $event->addFormField('plugin.citrix.select.assist', $field);

        // Register custom validation services

        $validator = [
            'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
            'fieldType' => 'plugin.citrix.select.meeting',
        ];
        $event->addValidator('plugin.citrix.validate.meeting', $validator);

        $validator = [
            'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
            'fieldType' => 'plugin.citrix.select.webinar'
        ];
        $event->addValidator('plugin.citrix.validate.webinar', $validator);

        $validator = [
            'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
            'fieldType' => 'plugin.citrix.select.training'
        ];
        $event->addValidator('plugin.citrix.validate.training', $validator);

        $validator = [
            'eventName' => CitrixEvents::ON_FORM_VALIDATE_ACTION,
            'fieldType' => 'plugin.citrix.select.assist'
        ];
        $event->addValidator('plugin.citrix.validate.assist', $validator);

    }

}