<?php

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Event\CategoryTypeEntityEvent;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreDelete;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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
    #[AsEventListener(
        event: CategoryTypesEvent::class,
    )]
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
    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onCategoryPostSave(EntityPostSave $event): void
    {
        $category = $event->getEntity();
        if (!$category instanceof Category) {
            return;
        }
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
    #[AsEventListener(
        event: EntityPostDelete::class,
    )]
    public function onCategoryDelete(EntityPostDelete $event): void
    {
        $category = $event->getEntity();
        if (!$category instanceof Category) {
            return;
        }
        $log = [
            'bundle'    => 'category',
            'object'    => 'category',
            'objectId'  => $category->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $category->getTitle()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    #[AsEventListener(
        event: EntityPreDelete::class,
    )]
    public function onCategoryPreDelete(EntityPreDelete $event): void
    {
        $category = $event->getEntity();
        if (!$category instanceof Category) {
            return;
        }
        if ($usage = $this->categoryModel->getUsage($category)) {
            $message = $this->translator->trans(
                'mautic.category.is_in_use.delete',
                [
                    '%entities%'     => implode(', ', array_map(fn (array $entity): string => $this->translator->trans($entity['label']).' Id: '.$entity['id'], $usage)),
                    '%categoryName%' => $category->getTitle(),
                ],
                'validators'
            );
            $event->addDependencyError($message);
        }
    }

    #[AsEventListener(
        event: CategoryTypeEntityEvent::class,
    )]
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
