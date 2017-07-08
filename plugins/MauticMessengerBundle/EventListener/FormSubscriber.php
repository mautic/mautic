<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Event as Events;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;


/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * @var LeadModel $leadModel
     */
    protected $leadModel;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * FormSubscriber constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel, CoreParametersHelper $coreParametersHelper)
    {
        $this->leadModel = $leadModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD => ['onFormBuilder', 0],
        ];
    }


    /**
     * Add a lead generation action to available form submit actions.
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        $action = [
            'label' => 'mautic.plugin.field.messenger.checkbox',
            'formType' => 'messenger_checkbox',
            'template' => 'MauticMessengerBundle:SubscribedEvents\Field:messenger.checkbox.html.php',
            'builderOptions' => [
                'addLeadFieldList' => false,
                'addIsRequired' => false,
                'addDefaultValue' => false,
                'addSaveResult' => true,
                'addShowLabel' => true,
                'addHelpMessage' => false,
                'addLabelAttributes' => false,
                'addInputAttributes' => false,
                'addBehaviorFields' => false,
                'addContainerAttributes' => false,
                'allowCustomAlias' => true,
                'labelText' => false,
            ],
        ];

        $event->addFormField('plugin.messenger.checkbox', $action);

        $action = [
            'group'              => 'mautic.plugin.messenger.actions',
            'label'              => 'plugin.messenger.send_to_messanger',
            'description'        => 'plugin.messenger.send_to_messanger.descr',
            'formType'           => 'messenger_send_to_messenger',
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ];

        $event->addSubmitAction('plugin.messenger.form.send.to.messenger', $action);
    }

}
