<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PageImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageModel $pageModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class => ['onPageExport', 0],
            EntityImportEvent::class => ['onPageImport', 0],
        ];
    }

    public function onPageExport(EntityExportEvent $event): void
    {
        if (Page::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $pageId = $event->getEntityId();
        $page   = $this->pageModel->getEntity($pageId);
        if (!$page) {
            return;
        }

        $pageData = [
            'id'                   => $page->getId(),
            // 'category_id'          => $page->getCategory() ? $page->getCategory()->getId() : null,
            'is_published'         => $page->isPublished(),
            'title'                => $page->getTitle(),
            'alias'                => $page->getAlias(),
            'template'             => $page->getTemplate(),
            'custom_html'          => $page->getCustomHtml(),
            'content'              => $page->getContent(),
            'publish_up'           => $page->getPublishUp() ? $page->getPublishUp()->format(DATE_ATOM) : null,
            'publish_down'         => $page->getPublishDown() ? $page->getPublishDown()->format(DATE_ATOM) : null,
            'hits'                 => $page->getHits(),
            'unique_hits'          => $page->getUniqueHits(),
            'variant_hits'         => $page->getVariantHits(),
            'revision'             => $page->getRevision(),
            'meta_description'     => $page->getMetaDescription(),
            'head_script'          => $page->getHeadScript(),
            'footer_script'        => $page->getFooterScript(),
            'redirect_type'        => $page->getRedirectType(),
            'redirect_url'         => $page->getRedirectUrl(),
            'is_preference_center' => $page->isPreferenceCenter(),
            'no_index'             => $page->getNoIndex(),
            'lang'                 => $page->getLanguage(),
            'variant_settings'     => $page->getVariantSettings(),
        ];

        $event->addEntity(Page::ENTITY_NAME, $pageData);
    }

    public function onPageImport(EntityImportEvent $event): void
    {
        if (Page::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output    = new ConsoleOutput();
        $elements  = $event->getEntityData();
        $userId    = $event->getUserId();
        $userName  = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.');
            }
        }

        if (!$elements) {
            return;
        }

        foreach ($elements as $element) {
            $object = new Page();
            $object->setTitle($element['title']);
            $object->setIsPublished((bool) $element['is_published']);
            $object->setAlias($element['alias'] ?? '');
            $object->setTemplate($element['template'] ?? '');
            $object->setContent($element['content'] ?? '');
            $object->setCustomHtml($element['custom_html'] ?? '');
            $object->setPublishUp($element['publish_up']);
            $object->setPublishDown($element['publish_down']);
            $object->setHits($element['hits']);
            $object->setUniqueHits($element['unique_hits']);
            $object->setVariantHits($element['variant_hits']);
            $object->setRevision($element['revision']);
            $object->setLanguage($element['lang'] ?? '');
            $object->setMetaDescription($element['meta_description'] ?? '');
            $object->setHeadScript($element['head_script'] ?? '');
            $object->setFooterScript($element['footer_script'] ?? '');
            $object->setRedirectType($element['redirect_type'] ?? '');
            $object->setRedirectUrl($element['redirect_url']);
            $object->setIsPreferenceCenter($element['is_preference_center'] ?? false);
            $object->setNoIndex($element['no_index'] ?? false);
            $object->setVariantSettings($element['variant_settings'] ?? []);
            $object->setDateAdded(new \DateTime());
            $object->setDateModified(new \DateTime());
            $object->setCreatedByUser($userName);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], (int) $object->getId());
            $output->writeln('<info>Imported page: '.$object->getName().' with ID: '.$object->getId().'</info>');
        }
    }
}
