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
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\Event\TriggerBuilderEvent;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_ON_BUILD     => array('onStageBuild', 0),
            EmailEvents::EMAIL_ON_OPEN      => array('onEmailOpen', 0),
            EmailEvents::EMAIL_ON_SEND      => array('onEmailSend', 0)
        );
    }

    /**
     * @param StageBuilderEvent $event
     */
    public function onStageBuild(StageBuilderEvent $event)
    {
        $action = array(
            'group'       => 'mautic.email.actions',
            'label'       => 'mautic.email.stage.action.open',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\StageEventHelper', 'validateEmail'),
            'formType'    => 'emailopen_list'
        );

        $event->addAction('email.open', $action);

        $action = array(
            'group'       => 'mautic.email.actions',
            'label'       => 'mautic.email.stage.action.send',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\StageEventHelper', 'validateEmail'),
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
            'group'           => 'mautic.email.stage.trigger',
            'label'           => 'mautic.email.stage.trigger.sendemail',
            'callback'        => array('\\Mautic\\EmailBundle\\Helper\\StageEventHelper', 'sendEmail'),
            'formType'        => 'emailsend_list',
            'formTypeOptions' => array('update_select' => 'stagetriggerevent_properties_email'),
            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
        );

        $event->addEvent('email.send', $sendEvent);
    }

    /**
     * Trigger stage actions for email open
     *
     * @param EmailOpenEvent $event
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $this->factory->getModel('stage')->triggerAction('email.open', $event->getEmail());
    }

    /**
     * Trigger stage actions for email send
     *
     * @param EmailSendEvent $event
     */
    public function onEmailSend(EmailSendEvent $event)
    {
        if ($leadArray = $event->getLead()) {
            $lead = $this->factory->getEntityManager()->getReference('MauticLeadBundle:Lead', $leadArray['id']);
        } else {

            return;
        }
        $this->factory->getModel('stage')->triggerAction('email.send', $event->getEmail(), null, $lead);
    }
}
