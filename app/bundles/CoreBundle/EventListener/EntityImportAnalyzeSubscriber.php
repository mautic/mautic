<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EntityImportAnalyzeSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityImportAnalyzeEvent::class => 'onAnalyzeImport',
        ];
    }

    public function onAnalyzeImport(EntityImportAnalyzeEvent $event): void
    {
        $data           = $event->getEntityData();
        $updatedObjects = [];
        $newObjects     = [];

        // List of entities to analyze
        $entities = [
            Campaign::ENTITY_NAME,
            Event::ENTITY_NAME,
            Asset::ENTITY_NAME,
            DynamicContent::ENTITY_NAME,
            LeadField::ENTITY_NAME,
            Form::ENTITY_NAME,
            Page::ENTITY_NAME,
            LeadList::ENTITY_NAME,
            Email::ENTITY_NAME,
            Group::ENTITY_NAME,
        ];

        // Iterate through each entity type and collect data
        foreach ($entities as $entityName) {
            if (isset($data[$entityName])) {
                foreach ($data[$entityName] as $item) {
                    // Check if the UUID exists in the data
                    $uuid = isset($item['uuid']) ? $item['uuid'] : null;
                    $name = isset($item['name']) ? $item['name'] : (isset($item['title']) ? $item['title'] : (isset($item['label']) ? $item['label'] : 'Unnamed'));

                    if ($uuid) {
                        // Query the database to check if the entity with this UUID exists
                        /** @var \Doctrine\ORM\EntityRepository<Campaign|Event|Asset|DynamicContent|LeadField|Form|Page|LeadList|Email|Group> */
                        $repository     = $this->entityManager->getRepository($this->getEntityClass($entityName));
                        $existingEntity = $repository->findOneBy(['uuid' => $uuid]);

                        if ($existingEntity) {
                            // If the entity exists, add to updatedObjects
                            $updatedObjects[$entityName]['names'][] = $name;
                            $updatedObjects[$entityName]['count']   = isset($updatedObjects[$entityName]['count'])
                                ? $updatedObjects[$entityName]['count'] + 1
                                : 1;
                        } else {
                            // If the entity does not exist, add to newObjects
                            $newObjects[$entityName]['names'][] = $name;
                            $newObjects[$entityName]['count']   = isset($newObjects[$entityName]['count'])
                                ? $newObjects[$entityName]['count'] + 1
                                : 1;
                        }
                    } else {
                        // If no UUID is found, consider it a new object
                        $newObjects[$entityName]['names'][] = $name;
                        $newObjects[$entityName]['count']   = isset($newObjects[$entityName]['count'])
                            ? $newObjects[$entityName]['count'] + 1
                            : 1;
                    }
                }
            }
        }

        // Prepare the analysis result
        $analysisResult = [
            'updatedObjects' => $updatedObjects,
            'newObjects'     => $newObjects,
        ];

        // Set the result in the event
        $event->setSummary($analysisResult);
    }

    private function getEntityClass(string $entityName): string
    {
        return match ($entityName) {
            Campaign::ENTITY_NAME       => Campaign::class,
            Event::ENTITY_NAME          => Event::class,
            Asset::ENTITY_NAME          => Asset::class,
            DynamicContent::ENTITY_NAME => DynamicContent::class,
            LeadField::ENTITY_NAME      => LeadField::class,
            Form::ENTITY_NAME           => Form::class,
            Page::ENTITY_NAME           => Page::class,
            LeadList::ENTITY_NAME       => LeadList::class,
            Email::ENTITY_NAME          => Email::class,
            Group::ENTITY_NAME          => Group::class,
            default                     => throw new \InvalidArgumentException("Unknown entity name: $entityName"),
        };
    }
}
