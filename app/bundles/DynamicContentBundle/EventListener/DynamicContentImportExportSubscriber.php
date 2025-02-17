<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DynamicContentImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onExport', 0],
            EntityImportEvent::class => ['onImport', 0],
        ];
    }

    public function onExport(EntityExportEvent $event): void
    {
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $object = $this->dynamicContentModel->getEntity($event->getEntityId());
        if (!$object) {
            return;
        }

        $event->addEntity(DynamicContent::ENTITY_NAME, [
            'id'                     => $object->getId(),
            'translation_parent_id'  => $object->getTranslationParent(),
            'variant_parent_id'      => $object->getVariantParent(),
            'is_published'           => $object->getIsPublished(),
            'name'                   => $object->getName(),
            'description'            => $object->getDescription(),
            'publish_up'             => $object->getPublishUp(),
            'publish_down'           => $object->getPublishDown(),
            'content'                => $object->getContent(),
            'utm_tags'               => $object->getUtmTags(),
            'lang'                   => $object->getLanguage(),
            'variant_settings'       => $object->getVariantSettings(),
            'variant_start_date'     => $object->getVariantStartDate(),
            'filters'                => $object->getFilters(),
            'is_campaign_based'      => $object->getIsCampaignBased(),
            'slot_name'              => $object->getSlotName(),
        ]);
    }

    public function onImport(EntityImportEvent $event): void
    {
        if (DynamicContent::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $elements = $event->getEntityData();
        if (!$elements) {
            return;
        }

        $userId   = $event->getUserId();
        $userName = $this->getUserName($userId, $output);

        foreach ($elements as $element) {
            $object = new DynamicContent();
            $object->setId($element['id'] ?? null);
            $object->setTranslationParentId($element['translation_parent_id'] ?? null);
            $object->setVariantParentId($element['variant_parent_id'] ?? null);
            $object->setIsPublished((bool) ($element['is_published'] ?? false));
            $object->setName($element['name'] ?? '');
            $object->setDescription($element['description'] ?? '');
            $object->setPublishUp($element['publish_up'] ?? null);
            $object->setPublishDown($element['publish_down'] ?? null);
            $object->setSentCount($element['sent_count'] ?? 0);
            $object->setContent($element['content'] ?? '');
            $object->setUtmTags($element['utm_tags'] ?? '');
            $object->setLanguage($element['lang'] ?? '');
            $object->setVariantSettings($element['variant_settings'] ?? '');
            $object->setVariantStartDate($element['variant_start_date'] ?? null);
            $object->setFilters($element['filters'] ?? '');
            $object->setIsCampaignBased((bool) ($element['is_campaign_based'] ?? false));
            $object->setSlotName($element['slot_name'] ?? '');

            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setCreatedByUser($userName);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $output->writeln("<info>Imported dynamic content: {$object->getName()} with ID: {$object->getId()}</info>");
        }
    }

    private function getUserName(?int $userId, ConsoleOutput $output): string
    {
        if (!$userId) {
            return '';
        }

        $user = $this->userModel->getEntity($userId);
        if (!$user) {
            $output->writeln("User ID $userId not found. Campaigns will not have a created_by_user field set.");

            return '';
        }

        return $user->getFirstName().' '.$user->getLastName();
    }
}
