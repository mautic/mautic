<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\CoreBundle\Event\MessageQueueProcessEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class CalendarSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class MessageQueueSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * MessageQueueSubscriber constructor.
     *
     * @param LeadModel  $leadModel
     * @param EmailModel $emailModel
     */
    public function __construct(LeadModel $leadModel, EmailModel $emailModel)
    {
        $this->leadModel  = $leadModel;
        $this->emailModel = $emailModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            CoreEvents::PROCESS_MESSAGE_QUEUE => ['onProcessMessageQueue', 0]
        ];
    }

    /**
     * sends campaign emails
     *
     * @param MessageQueueProcessEvent $event
     *
     * @return void
     */
    public function onProcessMessageQueue(MessageQueueProcessEvent $event)
    {
        $queueItem                   = $event->getMessageQueue();
        $lead                        = $queueItem->getLead();
        $leadCredentials             = $lead->getProfileFields();

        $leadCredentials['owner_id'] = (
            ($lead instanceof Lead) && ($owner = $lead->getOwner())
        ) ? $owner->getId() : 0;

        $success = false;
        if (!empty($leadCredentials['email'])) {
            $options = $queueItem->getOptions();

            $message = $this->emailModel->getEntity($queueItem->getChannelId());

            $success = $this->emailModel->sendEmail($message, $leadCredentials, $options);
        }

        return $success;
    }
}
