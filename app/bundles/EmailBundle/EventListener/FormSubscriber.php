<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
    static public function getSubscribedEvents ()
    {
        return array(
            FormEvents::FORM_ON_BUILD => array('onFormBuilder', 0),
        );
    }

    /**
     * Add a send email actions to available form submit actions
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder (FormBuilderEvent $event)
    {
        // Add form submit actions
        // Send email to user
        $action = array(
            'group'       => 'mautic.email.actions',
            'label'       => 'mautic.email.form.action.sendemail.admin',
            'description' => 'mautic.email.form.action.sendemail.admin.descr',
            'formType'    => 'email_submitaction_useremail',
            'formTheme'   => 'MauticEmailBundle:FormTheme\EmailSendList',
            'callback'    => '\Mautic\EmailBundle\Helper\FormSubmitHelper::sendEmail'
        );

        $event->addSubmitAction('email.send.user', $action);

        // Send email to lead
        $action = array(
            'group'           => 'mautic.email.actions',
            'label'           => 'mautic.email.form.action.sendemail.lead',
            'description'     => 'mautic.email.form.action.sendemail.lead.descr',
            'formType'        => 'emailsend_list',
            'formTypeOptions' => array('update_select' => 'formaction_properties_email'),
            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
            'callback'        => '\Mautic\EmailBundle\Helper\FormSubmitHelper::sendEmail'
        );

        $event->addSubmitAction('email.send.lead', $action);
    }
}