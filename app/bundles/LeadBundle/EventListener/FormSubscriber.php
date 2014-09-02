<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            FormEvents::FORM_ON_BUILD => array('onFormBuilder', 0),
        );
    }

    /**
     * Add a lead generation action to available form submit actions
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        //add lead generation submit action
        $action = array(
            'group'        => 'mautic.lead.lead.submitaction.group',
            'label'        => 'mautic.lead.lead.submitaction.createlead',
            'descrription' => 'mautic.lead.lead.submitaction.createlead_descr',
            'formType'     => 'lead_submitaction_createlead',
            'callback'     => '\Mautic\LeadBundle\Helper\EventHelper::createLeadOnFormSubmit'
        );

        $event->addSubmitAction('lead.create', $action);

        //add lead generation submit action
        $action = array(
            'group'       => 'mautic.lead.lead.submitaction.group',
            'label'       => 'mautic.lead.lead.submitaction.changescore',
            'description' => 'mautic.lead.lead.submitaction.changescore_descr',
            'formType'    => 'lead_submitaction_scorechange',
            'callback'    => '\Mautic\LeadBundle\Helper\EventHelper::changeScoreOnFormSubmit'
        );

        $event->addSubmitAction('lead.scorechange', $action);
    }
}