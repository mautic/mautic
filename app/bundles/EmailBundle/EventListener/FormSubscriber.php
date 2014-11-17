<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
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
        // Add form submit actions
        // Send email to admin user
        $action = array(
            'label'        => 'mautic.email.form.action.sendemail.admin',
            'description'  => 'mautic.email.form.action.sendemail.admin.descr',
            'formType'     => 'email_submitaction_sendemail_admin',
            'callback'     => '\Mautic\EmailBundle\Helper\FormSubmitHelper::onFormSubmit'
        );

        $event->addSubmitAction('email.send.admin', $action);

        // Send email to lead
        $action = array(
            'label'        => 'mautic.email.form.action.sendemail.lead',
            'description'  => 'mautic.email.form.action.sendemail.lead.descr',
            'formType'     => 'email_submitaction_sendemail_lead',
            'callback'     => '\Mautic\EmailBundle\Helper\FormSubmitHelper::onFormSubmit'
        );

        $event->addSubmitAction('email.send.lead', $action);
    }
}