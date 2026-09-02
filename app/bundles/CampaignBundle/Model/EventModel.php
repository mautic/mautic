<?php

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Event>
 */
class EventModel extends FormModel
{
    public static function getName(): string
    {
        return 'campaign.event';
    }

    private LeadEventLogRepository $leadEventLogRepository;

    private CampaignRepository $campaignRepository;

    private EventRepository $eventRepository;

    #[Required]
    public function autowireEventModel(
        EventRepository $eventRepository,
        CampaignRepository $campaignRepository,
        LeadEventLogRepository $leadEventLogRepository,
    ): void {
        $this->eventRepository = $eventRepository;
        $this->campaignRepository = $campaignRepository;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    public function getRepository(): EventRepository
    {
        return $this->eventRepository;
    }

    public function getPermissionBase(): string
    {
        return 'campaign:campaigns';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Event
    {
        if (null === $id) {
            return new Event();
        }

        return parent::getEntity($id);
    }

    /**
     * Deletes campaign events and sets their redirect targets.
     */
    public function deleteEvents(array $currentEvents, array $deletedEvents): void
    {
        $deletedKeys = [];
        $deletedData = [];

        foreach ($deletedEvents as $k => $deleteInfo) {
            $eventId       = $deleteInfo['id'];
            $redirectEvent = $deleteInfo['redirectEvent'] ?? null;

            if (str_starts_with($eventId, 'new') || isset($currentEvents[$eventId])) {
                unset($deletedEvents[$k]);
                continue;
            }

            $deletedKeys[] = $eventId;
            $deletedData[] = [
                'id'              => $eventId,
                'redirectEvent'   => $redirectEvent instanceof Event ? $redirectEvent->getId() : $redirectEvent,
            ];
        }

        if ([] !== $deletedKeys) {
            $this->eventRepository->nullEventRelationships($deletedKeys);
            $this->eventRepository->setEventsAsDeletedWithRedirect($deletedData);
            $this->dispatcher->dispatch(new DeleteEvent($deletedKeys));
        }
    }

    public function deleteEventsByCampaignId(int $campaignId): void
    {
        $eventIds = $this->eventRepository->getCampaignEventIds($campaignId);
        $this->deleteEventsByEventIds($eventIds);
    }

    /**
     * @param string[] $eventIds
     */
    public function deleteEventsByEventIds(array $eventIds): void
    {
        $deletedData = array_map(fn (string $id): array => ['id' => (int) $id, 'redirectEvent' => null], $eventIds);
        $this->eventRepository->setEventsAsDeletedWithRedirect($deletedData);
        $this->dispatcher->dispatch(new DeleteEvent($eventIds), CampaignEvents::ON_AFTER_EVENTS_DELETE);
    }

    /**
     * Get line chart data of campaign events.
     *
     * @param string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     * @param bool   $canViewOthers
     */
    public function getEventLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('campaign_lead_event_log', 'date_triggered', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = t.campaign_id')
                ->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.campaign.triggered.events'), $data);

        return $chart->render();
    }
}
