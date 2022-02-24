<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BroadcastSubscriber implements EventSubscriberInterface
{
    private AuditLogModel $auditLogModel;

    private IpLookupHelper $ipLookupHelper;

    /**
     * @var EmailModel
     */
    private $model;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(EmailModel $emailModel, EntityManager $em, TranslatorInterface $translator, AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->model          = $emailModel;
        $this->em             = $em;
        $this->translator     = $translator;
        $this->auditLogModel  = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    public function onBroadcast(ChannelBroadcastEvent $event)
    {
        if (!$event->checkContext('email')) {
            return;
        }

        // Get list of published broadcasts or broadcast if there is only a single ID
        $emails = $this->model->getRepository()->getPublishedBroadcasts($event->getId());

        while (false !== ($email = $emails->next())) {
            $emailEntity                                            = $email[0];
            $pending                                                = $this->model->getPendingLeads($emailEntity, null, true);
            if ((int) $pending > 0) {
                $log = [
                    'bundle'    => 'email',
                    'object'    => 'email',
                    'objectId'  => $emailEntity->getId(),
                    'action'    => 'broadcast-start-sending',
                    'details'   => [
                        'pending' => (int) $pending,
                    ],
                    'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
                ];
                $this->auditLogModel->writeToLog($log);
            }
            [$sentCount, $failedCount, $failedRecipientsByList] = $this->model->sendEmailToLists(
                $emailEntity,
                null,
                $event->getLimit(),
                $event->getBatch(),
                $event->getOutput(),
                $event->getMinContactIdFilter(),
                $event->getMaxContactIdFilter()
            );

            $event->setResults(
                $this->translator->trans('mautic.email.email').': '.$emailEntity->getName(),
                $sentCount,
                $failedCount,
                $failedRecipientsByList
            );
            $this->em->detach($emailEntity);
        }
    }
}
