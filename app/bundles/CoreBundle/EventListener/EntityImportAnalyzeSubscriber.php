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
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\PointBundle\Entity\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Acl\Domain\Acl;

final class EntityImportAnalyzeSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager  = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityImportAnalyzeEvent::class => 'onAnalyzeImport',
        ];
    }

    public function onAnalyzeImport(EntityImportAnalyzeEvent $event): void
    {
        $data = $event->getEntityData();
        $updatedObjects = [];
        $newObjects = [];

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
                        $repository = $this->entityManager->getRepository($this->getEntityClass($entityName));
                        $existingEntity = $repository->findOneBy(['uuid' => $uuid]);

                        if ($existingEntity) {
                            // If the entity exists, add to updatedObjects
                            $updatedObjects[$entityName]['names'][] = $name;
                            $updatedObjects[$entityName]['count'] = isset($updatedObjects[$entityName]['count']) 
                                ? $updatedObjects[$entityName]['count'] + 1 
                                : 1;
                        } else {
                            // If the entity does not exist, add to newObjects
                            $newObjects[$entityName]['names'][] = $name;
                            $newObjects[$entityName]['count'] = isset($newObjects[$entityName]['count']) 
                                ? $newObjects[$entityName]['count'] + 1 
                                : 1;
                        }
                    } else {
                        // If no UUID is found, consider it a new object
                        $newObjects[$entityName]['names'][] = $name;
                        $newObjects[$entityName]['count'] = isset($newObjects[$entityName]['count']) 
                            ? $newObjects[$entityName]['count'] + 1 
                            : 1;
                    }
                }
            }
        }

        // Prepare the analysis result
        $analysisResult = [
            'updatedObjects' => $updatedObjects,
            'newObjects' => $newObjects,
        ];

        // Set the result in the event
        $event->setSummary($analysisResult);
    }

    private function getEntityClass(string $entityName): string
    {
        switch ($entityName) {
            case Campaign::ENTITY_NAME:
                return Campaign::class;
            case Event::ENTITY_NAME:
                return Event::class;
            case Asset::ENTITY_NAME:
                return Asset::class;
            case DynamicContent::ENTITY_NAME:
                return DynamicContent::class;
            case LeadField::ENTITY_NAME:
                return LeadField::class;
            case Form::ENTITY_NAME:
                return Form::class;
            case Page::ENTITY_NAME:
                return Page::class;
            case LeadList::ENTITY_NAME:
                return LeadList::class;
            case Email::ENTITY_NAME:
                return Email::class;
            case Group::ENTITY_NAME:
                return Group::class;
            default:
                throw new \InvalidArgumentException("Unknown entity name: $entityName");
        }
    }
}
