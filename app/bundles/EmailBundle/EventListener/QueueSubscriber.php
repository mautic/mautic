<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;

/**
 * Proceses queue (Beanstalk, RabitMQ, ...) jobs.
 */
class QueueSubscriber extends CommonSubscriber
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

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
            QueueEvents::EMAIL_HIT => ['onEmailHit', 0],
        ];
    }

    /**
     * @param QueueConsumerEvent $event
     */
    public function onEmailHit(QueueConsumerEvent $event)
    {
        $payload = $event->getPayload();
        $this->emailModel->hitEmail($payload['idHash'], $payload['request'], false, false);
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }
}
