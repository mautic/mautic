<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\DoNotContactAddEvent;
use Mautic\LeadBundle\Event\DoNotContactRemoveEvent;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DoNotContactSubscriber implements EventSubscriberInterface
{
    /**
     * @var DoNotContact
     */
    private $doNotContact;

    public function __construct(DoNotContact $doNotContact)
    {
        $this->doNotContact = $doNotContact;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DoNotContactAddEvent::ADD_DONOT_CONTACT       => ['addDncForLead', 0],
            DoNotContactRemoveEvent::REMOVE_DONOT_CONTACT => ['removeDncForLead', 0],
        ];
    }

    public function removeDncForLead(DoNotContactRemoveEvent $doNotContactRemoveEvent): void
    {
        $this->doNotContact->removeDncForContact(
            $doNotContactRemoveEvent->getLead()->getId(),
            $doNotContactRemoveEvent->getChannel(),
            $doNotContactRemoveEvent->getPersist()
        );
    }

    public function addDncForLead(DoNotContactAddEvent $doNotContactAddEvent): void
    {
        if (empty($doNotContactAddEvent->getLead()->getId())) {
            $this->doNotContact->createDncRecord(
                $doNotContactAddEvent->getLead(),
                $doNotContactAddEvent->getChannel(),
                $doNotContactAddEvent->getReason(),
                $doNotContactAddEvent->getComments()
            );
        } else {
            $this->doNotContact->addDncForContact(
                $doNotContactAddEvent->getLead()->getId(),
                $doNotContactAddEvent->getChannel(),
                $doNotContactAddEvent->getReason(),
                $doNotContactAddEvent->getComments(),
                $doNotContactAddEvent->isPersist(),
                $doNotContactAddEvent->isCheckCurrentStatus(),
                $doNotContactAddEvent->isOverride()
            );
        }
    }
}
