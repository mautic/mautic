<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Entity\StatRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TrackingSubscriber implements EventSubscriberInterface
{
    /**
     * @var StatRepository
     */
    private $statRepository;

    /**
     * TrackingSubscriber constructor.
     *
     * @param StatRepository $statRepository
     */
    public function __construct(StatRepository $statRepository)
    {
        $this->statRepository = $statRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION => ['onIdentifyContact', 0],
        ];
    }

    /**
     * @param ContactIdentificationEvent $event
     */
    public function onIdentifyContact(ContactIdentificationEvent $event)
    {
        $clickthrough = $event->getClickthrough();

        // Nothing left to identify by so stick to the tracked lead
        if (empty($clickthrough['channel']['sms']) && empty($clickthrough['stat'])) {
            return;
        }

        /** @var Stat $stat */
        $stat = $this->statRepository->findOneBy(['trackingHash' => $clickthrough['stat']]);

        if (!$stat) {
            // Stat doesn't exist so use the tracked lead
            return;
        }

        if ($stat->getSms() && (int) $stat->getSms()->getId() !== (int) $clickthrough['channel']['sms']) {
            // ID mismatch - fishy so use tracked lead
            return;
        }

        if (!$contact = $stat->getLead()) {
            return;
        }

        $event->setIdentifiedContact($contact, 'sms');
    }
}
