<?php

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event as Events;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CategorySubscriber implements EventSubscriberInterface
{
    /**
     * @var BundleHelper
     */
    private $bundleHelper;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    public function __construct(BundleHelper $bundleHelper, IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->bundleHelper   = $bundleHelper;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD => ['onCategoryBundleListBuild', 0],
            CategoryEvents::CATEGORY_POST_SAVE            => ['onCategoryPostSave', 0],
            CategoryEvents::CATEGORY_POST_DELETE          => ['onCategoryDelete', 0],
        ];
    }

    /**
     * Add bundle to the category.
     */
    public function onCategoryBundleListBuild(CategoryTypesEvent $event)
    {
        $bundles = $this->bundleHelper->getMauticBundles(true);

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['categories'])) {
                foreach ($bundle['config']['categories'] as $type => $label) {
                    $event->addCategoryType($type, $label);
                }
            }
        }
    }

    /**
     * Add an entry to the audit log.
     */
    public function onCategoryPostSave(Events\CategoryEvent $event)
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
    public function onCategoryDelete(Events\CategoryEvent $event)
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
}
