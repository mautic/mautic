<?php

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Event\CategoryTypeEntityEvent;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Exception\RecordCanNotBeDeletedException;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategorySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BundleHelper $bundleHelper,
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private CategoryModel $categoryModel,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD => ['onCategoryBundleListBuild', 0],
            CategoryEvents::CATEGORY_POST_SAVE            => ['onCategoryPostSave', 0],
            CategoryEvents::CATEGORY_POST_DELETE          => ['onCategoryDelete', 0],
            CategoryEvents::CATEGORY_PRE_DELETE           => ['onCategoryPreDelete', 0],
            CategoryTypeEntityEvent::class                => ['onCategoryTypeEntity', 0],
        ];
    }

    /**
     * Add bundle to the category.
     */
    public function onCategoryBundleListBuild(CategoryTypesEvent $event): void
    {
        $bundles = $this->bundleHelper->getMauticBundles(true);

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['categories'])) {
                foreach ($bundle['config']['categories'] as $type => $data) {
                    $event->addCategoryType($type, $data['label'] ?? null);
                }
            }
        }
    }

    /**
     * Add an entry to the audit log.
     */
    public function onCategoryPostSave(CategoryEvent $event): void
    {
        $category = $event->getCategory();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'category',
                'object'    => 'category',
                'objectId'  => $category->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onCategoryDelete(CategoryEvent $event): void
    {
        $category = $event->getCategory();
        $log      = [
            'bundle'    => 'category',
            'object'    => 'category',
            'objectId'  => $category->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $category->getTitle()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onCategoryPreDelete(CategoryEvent $event): void
    {
        if ($usage = $this->categoryModel->getUsage($event->getCategory())) {
            $message = $this->translator->trans(
                'mautic.category.is_in_use.delete',
                [
                    '%entities%'     => implode(', ', array_map(fn ($entity): string => $this->translator->trans($entity['label']).' Id: '.$entity['id'], $usage)),
                    '%categoryName%' => $event->getCategory()->getTitle(),
                ],
                'validators');
            throw new RecordCanNotBeDeletedException($message);
        }
    }

    public function onCategoryTypeEntity(CategoryTypeEntityEvent $event): void
    {
        $bundles = $this->bundleHelper->getMauticBundles(true);

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['categories'])) {
                foreach ($bundle['config']['categories'] as $type => $data) {
                    $event->addCategoryTypeEntity($type, $data);
                }
            }
        }
    }
}
