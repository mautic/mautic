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
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber
 */
class PointSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            PointEvents::POINT_ON_BUILD     => array('onPointBuild', 0),
            PointEvents::TRIGGER_ON_BUILD   => array('onTriggerBuild', 0),
            EmailEvents::EMAIL_ON_OPEN      => array('onEmailOpen', 0),
            EmailEvents::EMAIL_ON_SEND      => array('onEmailSend', 0)
        );
    }

    /**
     * @param PointBuilderEvent $event
     */
    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = array(
            'label'       => 'mautic.email.point.action.open',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\PointActionHelper', 'validateEmail'),
            'formType'    => 'emailopen_list'
        );

        $event->addAction('email.open', $action);

        $action = array(
            'label'       => 'mautic.email.point.action.send',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\PointActionHelper', 'validateEmail'),
            'formType'    => 'emailopen_list'
        );

        $event->addAction('email.send', $action);
    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $sendEvent = array(
            'label'       => 'mautic.email.point.trigger.sendemail',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\PointActionHelper', 'sendEmail'),
            'formType'    => 'emailsend_list'
        );

        $event->addEvent('email.send', $sendEvent);
    }

    /**
     * Trigger point actions for email open
     *
     * @param EmailOpenEvent $event
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $this->factory->getModel('point')->triggerAction('email.open', $event->getEmail());
    }

    /**
     * Trigger point actions for email send
     *
     * @param EmailSendEvent $event
     */
    public function onEmailSend(EmailSendEvent $event)
    {
        $this->factory->getModel('point')->triggerAction('email.send', $event->getEmail());
    }
}
