<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignEventImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignModel $campaignModel,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class                 => ['onExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN_EVENT => ['onCampaignEventImport', 0],
        ];
    }

    public function onExport(EntityExportEvent $event): void
    {
        if (Event::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $campaignId = $event->getEntityId();
        $campaign   = $this->campaignModel->getEntity($campaignId);
        if (!$campaign) {
            return;
        }

        $campaignEvents = $campaign->getEvents();
        $data           = [];

        foreach ($campaignEvents as $campaignEvent) {
            $eventData  = $this->createEventData($campaign, $campaignEvent);
            $dependency = $this->createDependency($campaignId, $campaignEvent);

            $this->handleChannelExport($campaignEvent, $data);

            $event->addEntity(Event::ENTITY_NAME, $eventData);
            $event->addDependencyEntity(Event::ENTITY_NAME, $dependency);
        }

        if (!empty($data)) {
            foreach ($data as $entityName => $entities) {
                $event->addEntities([$entityName => array_values($entities)]);
            }
        }
    }

    private function createEventData($campaign, $campaignEvent): array
    {
        $parentId = $campaignEvent->getParent()?->getId();

        return [
            'id'                    => $campaignEvent->getId(),
            'campaign_id'           => $campaign->getId(),
            'name'                  => $campaignEvent->getName(),
            'description'           => $campaignEvent->getDescription(),
            'type'                  => $campaignEvent->getType(),
            'event_type'            => $campaignEvent->getEventType(),
            'event_order'           => $campaignEvent->getOrder(),
            'properties'            => $campaignEvent->getProperties(),
            'trigger_interval'      => $campaignEvent->getTriggerInterval(),
            'trigger_interval_unit' => $campaignEvent->getTriggerIntervalUnit(),
            'trigger_mode'          => $campaignEvent->getTriggerMode(),
            'triggerDate'           => $campaignEvent->getTriggerDate()?->format(DATE_ATOM),
            'channel'               => $campaignEvent->getChannel(),
            'channel_id'            => $campaignEvent->getChannelId(),
            'parent_id'             => $parentId,
        ];
    }

    private function createDependency($campaignId, $campaignEvent): array
    {
        $channel   = $campaignEvent->getChannel() ?: '';
        $channelId = $campaignEvent->getChannelId() ?: 0;

        return [
            Campaign::ENTITY_NAME    => (int) $campaignId,
            Event::ENTITY_NAME       => (int) $campaignEvent->getId(),
            $channel                 => (int) $channelId,
        ];
    }

    private function handleChannelExport($campaignEvent, &$data): void
    {
        $channel   = $campaignEvent->getChannel();
        $channelId = $campaignEvent->getChannelId();

        if ($channel && $channelId) {
            $event = new EntityExportEvent($channel, $channelId);
            $this->dispatcher->dispatch($event);

            foreach ($event->getEntities()[$channel] ?? [] as $entity) {
                $data[$channel][$entity['id']] = $entity;
            }
        }
    }

    private function onCampaignEventImport(EntityImportEvent $event): void
    {
        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $campaignEvent = $this->createCampaignEvent($element);
            $this->entityManager->persist($campaignEvent);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $campaignEvent->getId());
            $output->writeln('<info>Imported campaign event: '.$campaignEvent->getName().' with ID: '.$campaignEvent->getId().'</info>');
        }

        $this->updateParentEvents($event);
    }

    private function createCampaignEvent(array $element): Event
    {
        $campaignEvent = new Event();
        $campaignEvent->setName($element['name'] ?? '');
        $campaignEvent->setDescription($element['description'] ?? '');
        $campaignEvent->setType($element['type'] ?? '');
        $campaignEvent->setEventType($element['event_type'] ?? '');
        $campaignEvent->setOrder($element['event_order'] ?? 0);
        $campaignEvent->setProperties($element['properties'] ?? []);
        $campaignEvent->setTriggerInterval($element['trigger_interval'] ?? 0);
        $campaignEvent->setTriggerIntervalUnit($element['trigger_interval_unit'] ?? '');
        $campaignEvent->setTriggerMode($element['trigger_mode'] ?? '');
        $campaignEvent->setTriggerDate(isset($element['triggerDate']) ? new \DateTime($element['triggerDate']) : null);
        $campaignEvent->setChannel($element['channel'] ?? '');
        $campaignEvent->setChannelId($element['channel_id'] ?? 0);

        $campaign = $this->campaignModel->getEntity($element['campaign_id']);
        $campaignEvent->setCampaign($campaign);

        return $campaignEvent;
    }

    private function updateParentEvents(EntityImportEvent $event): void
    {
        $idMap = $event->getEntityIdMap();
        foreach ($event->getEntityData() as $element) {
            if (isset($element['parent_id'])) {
                $originalParentId = (int) $element['parent_id'];
                $newParentId      = $idMap[$originalParentId] ?? null;

                if ($newParentId) {
                    $campaignEventId = $idMap[(int) $element['id']];
                    $campaignEvent   = $this->entityManager->getRepository(Event::class)->find($campaignEventId);
                    $parentEvent     = $this->entityManager->getRepository(Event::class)->find($newParentId);

                    if ($campaignEvent && $parentEvent) {
                        $campaignEvent->setParent($parentEvent);
                        $this->entityManager->persist($campaignEvent);
                    }
                }
            }
        }

        $this->entityManager->flush();
    }
}
