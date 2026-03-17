<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\PageBundle\Event\UrlTokenReplaceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Appends contact segment IDs to tracking URLs for third-party integrations like VWO.
 */
final class SegmentTrackingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private LeadListRepository $leadListRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UrlTokenReplaceEvent::class => ['onUrlTokenReplace', -100],
        ];
    }

    /**
     * Append segment IDs to the final redirect URL.
     */
    public function onUrlTokenReplace(UrlTokenReplaceEvent $event): void
    {
        $lead = $event->getLead();

        if (!$this->coreParametersHelper->get('append_segment_id_tracking_url')) {
            return;
        }

        $contactId = $lead->getId();
        if (!$contactId) {
            return;
        }

        $segmentIds = $this->leadListRepository->getContactSegmentIds((string) $contactId);
        if ($segmentIds) {
            $this->appendSegmentIdsToUrl($event, $segmentIds);
        }
    }

    /**
     * Append segment IDs as a query parameter to the URL in the event.
     *
     * @param UrlTokenReplaceEvent $event      The event containing the URL
     * @param int[]                $segmentIds Array of segment IDs to append
     */
    private function appendSegmentIdsToUrl(UrlTokenReplaceEvent $event, array $segmentIds): void
    {
        $url = $event->getContent();
        $uri = new Uri($url);

        $queryParams                = Query::parse($uri->getQuery());
        $queryParams['segment_ids'] = implode(',', $segmentIds);

        $newUri = $uri->withQuery(Query::build($queryParams));

        $event->setContent((string) $newUri);
    }
}
