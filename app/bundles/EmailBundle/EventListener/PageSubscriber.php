<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Event as Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class PageSubscriber
{
    public function __construct(
        private EmailModel $emailModel,
        private RealTimeExecutioner $realTimeExecutioner,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Trigger point actions for page hits.
     */
    #[AsEventListener(
        event: Events\PageHitEvent::class,
        priority: 0
    )]
    public function onPageHit(Events\PageHitEvent $event): void
    {
        $hit      = $event->getHit();
        $redirect = $hit->getRedirect();

        if ($redirect && $email = $hit->getEmail()) {
            // click trigger condition
            $this->realTimeExecutioner->execute('email.click', $hit, 'email', $email->getId());
            // Check for an email stat
            $clickthrough = $event->getClickthroughData();

            $stat = null;
            if (isset($clickthrough['stat'])) {
                $stat = $this->emailModel->getEmailStatus($clickthrough['stat']);
            }

            if (! $stat instanceof Stat) {
                if ($lead = $hit->getLead()) {
                    // Try searching by email and lead IDs
                    $stats = $this->emailModel->getEmailStati($hit->getSourceId(), $lead->getId());
                    if (count($stats)) {
                        $stat = $stats[0];
                    }
                }
            }

            if (!empty($stat)) {
                // Check to see if it has been marked as opened
                if (!$stat->isRead()) {
                    // Mark it as read
                    $this->emailModel->hitEmail($stat, $this->requestStack->getCurrentRequest() ?: $event->getRequest());
                }
            }
        }
    }
}
