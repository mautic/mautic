<?php

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryEvent;
use Mautic\CategoryBundle\Event\CategoryTypeEntityEvent;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CategorySubscriber
{
    public function __construct(
        private BundleHelper $bundleHelper,
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private CategoryModel $categoryModel,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Add bundle to the category.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: CategoryTypesEvent::class)]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: CategoryEvents::CATEGORY_POST_SAVE)]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: CategoryEvents::CATEGORY_POST_DELETE)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: CategoryEvents::CATEGORY_PRE_DELETE)]
    public function onCategoryPreDelete(CategoryEvent $event): void
    {
        if ($usage = $this->categoryModel->getUsage($event->getCategory())) {
            $message = $this->translator->trans(
                'mautic.category.is_in_use.delete',
                [
                    '%entities%'     => implode(', ', array_map(fn (array $entity): string => $this->translator->trans($entity['label']).' Id: '.$entity['id'], $usage)),
                    '%categoryName%' => $event->getCategory()->getTitle(),
                ],
                'validators');
            $event->addDependencyError($message);
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: CategoryTypeEntityEvent::class)]
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
