<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Form\Type\ActionAddUtmTagsType;
use Mautic\LeadBundle\Form\Type\ActionRemoveDoNotContact;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;
use Mautic\LeadBundle\Form\Type\FormSubmitActionPointsChangeType;
use Mautic\LeadBundle\Form\Type\ListActionType;
use Mautic\LeadBundle\Form\Type\ModifyLeadTagsType;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @param EmailModel $emailModel
     */
    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                    => ['onFormBuilder', 0],
            LeadEvents::FORM_SUBMIT_REMOVE_DO_NO_CONTACT => ['removeFromDoNotContact', 0],
        ];
    }

    /**
     * Add a lead generation action to available form submit actions.
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        //add lead generation submit action
        $action = [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.submitaction.changepoints',
            'description' => 'mautic.lead.lead.submitaction.changepoints_descr',
            'formType'    => FormSubmitActionPointsChangeType::class,
            'formTheme'   => 'MauticLeadBundle:FormTheme\\FormActionChangePoints',
            'callback'    => '\Mautic\LeadBundle\Helper\FormEventHelper::changePoints',
        ];
        $event->addSubmitAction('lead.pointschange', $action);

        //add to lead list
        $action = [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.events.changelist',
            'description' => 'mautic.lead.lead.events.changelist_descr',
            'formType'    => ListActionType::class,
            'callback'    => '\Mautic\LeadBundle\Helper\FormEventHelper::changeLists',
        ];
        $event->addSubmitAction('lead.changelist', $action);

        // modify tags
        $action = [
            'group'             => 'mautic.lead.lead.submitaction',
            'label'             => 'mautic.lead.lead.events.changetags',
            'description'       => 'mautic.lead.lead.events.changetags_descr',
            'formType'          => ModifyLeadTagsType::class,
            'callback'          => '\Mautic\LeadBundle\Helper\EventHelper::updateTags',
            'allowCampaignForm' => true,
        ];
        $event->addSubmitAction('lead.changetags', $action);

        // add UTM tags
        $action = [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.events.addutmtags',
            'description' => 'mautic.lead.lead.events.addutmtags_descr',
            'formType'    => ActionAddUtmTagsType::class,
            'formTheme'   => 'MauticLeadBundle:FormTheme\\ActionAddUtmTags',
            'callback'    => '\Mautic\LeadBundle\Helper\EventHelper::addUtmTags',
        ];
        $event->addSubmitAction('lead.addutmtags', $action);

        // add Do Not Contact
        $action = [
            'group'             => 'mautic.lead.lead.submitaction',
            'label'             => 'mautic.lead.lead.events.removedonotcontact',
            'description'       => 'mautic.lead.lead.events.removedonotcontact_descr',
            'formType'          => ActionRemoveDoNotContact::class,
            'formTheme'         => 'MauticLeadBundle:FormTheme\\ActionRemoveDoNotContact',
            'eventName'         => LeadEvents::FORM_SUBMIT_REMOVE_DO_NO_CONTACT,
            'allowCampaignForm' => true,
        ];
        $event->addSubmitAction('lead.removeronotcontact', $action);

        // score contact's companies
        $action = [
            'group'       => 'mautic.lead.lead.submitaction',
            'label'       => 'mautic.lead.lead.events.changecompanyscore',
            'description' => 'mautic.lead.lead.events.changecompanyscore_descr',
            'formType'    => CompanyChangeScoreActionType::class,
            'callback'    => '\Mautic\LeadBundle\Helper\FormEventHelper::scoreContactsCompanies',
        ];
        $event->addSubmitAction('lead.scorecontactscompanies', $action);
    }

    /**
     * Trigger event for when a form is submitted.
     *
     * @param SubmissionEvent $event
     */
    public function removeFromDoNotContact(SubmissionEvent $event)
    {
        $form = $event->getResults();
        if (isset($form['email']) && !empty($form['email'])) {
            $this->emailModel->removeDoNotContact($form['email']);
        }
    }
}
