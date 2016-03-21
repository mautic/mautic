<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\FormBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            FormEvents::FORM_ON_SUBMIT        => array('onFormSubmit', 0)
        );
    }

    /**
     * Add the option to the list
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $trigger = array(
            'label'       => 'mautic.form.campaign.event.submit',
            'description' => 'mautic.form.campaign.event.submit_descr',
            'formType'    => 'campaignevent_formsubmit',
            'callback'    => array('\\Mautic\\FormBundle\\Helper\\CampaignEventHelper', 'validateFormSubmit')
        );
        $event->addLeadDecision('form.submit', $trigger);

        $trigger = array(
            'label'       => 'mautic.form.campaign.event.field_value',
            'description' => 'mautic.form.campaign.event.field_value_descr',
            'formType'    => 'campaignevent_form_field_value',
            'formTheme'   => 'MauticFormBundle:FormTheme\FieldValueCondition',
            'callback'    => array('\\Mautic\\FormBundle\\Helper\\CampaignEventHelper', 'validateFormValue')
        );
        $event->addLeadCondition('form.field_value', $trigger);
    }

    /**
     * Trigger campaign event for when a form is submitted
     *
     * @param FormEvent $event
     */
    public function onFormSubmit(SubmissionEvent $event)
    {
        $form = $event->getSubmission()->getForm();
        $this->factory->getModel('campaign')->triggerEvent('form.submit', $form, 'form.submit' . $form->getId());
    }
}
