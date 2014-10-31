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
        //add form submit actions
        $action = array(
            'group'        => 'mautic.email.form.action.group',
            'label'        => 'mautic.email.form.action.sendemail',
            'description'  => 'mautic.email.form.action.sendemail_descr',
            'formType'     => 'email_submitaction_sendemail',
            'callback'     => '\Mautic\EmailBundle\Helper\FormSubmitHelper::sendEmailAction'
        );

        $event->addSubmitAction('email.send', $action);
    }
}