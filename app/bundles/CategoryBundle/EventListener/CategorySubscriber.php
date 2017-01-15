<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event as Events;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

/**
 * Class CategorySubscriber.
 */
class CategorySubscriber extends CommonSubscriber
{
    /**
     * @var BundleHelper
     */
    protected $bundleHelper;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * CategorySubscriber constructor.
     *
     * @param BundleHelper   $bundleHelper
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
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
     *
     * @param CategoryTypesEvent $event
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
     *
     * @param Events\CategoryEvent $event
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
     *
     * @param Events\CategoryEvent $event
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
