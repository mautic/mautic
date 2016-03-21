<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CategoryBundle\Event as Events;
use Mautic\CategoryBundle\CategoryEvents;
use Mautic\CategoryBundle\Event\CategoryTypesEvent;

/**
 * Class CategorySubscriber
 *
 * @package Mautic\CategoryBundle\EventListener
 */
class CategorySubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CategoryEvents::CATEGORY_ON_BUNDLE_LIST_BUILD => array('onCategoryBundleListBuild', 0),
            CategoryEvents::CATEGORY_POST_SAVE            => array('onCategoryPostSave', 0),
            CategoryEvents::CATEGORY_POST_DELETE          => array('onCategoryDelete', 0)
        );
    }

    /**
     * Add bundle to the category
     *
     * @param CategoryTypesEvent $event
     *
     * @return void
     */
    public function onCategoryBundleListBuild(CategoryTypesEvent $event)
    {
        $bundles = $this->factory->getMauticBundles(true);

        foreach ($bundles as $bundle) {
            if (!empty($bundle['config']['categories'])) {
                foreach ($bundle['config']['categories'] as $type => $label) {
                    $event->addCategoryType($type, $label);
                }
            }
        }
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\CategoryEvent $event
     */
    public function onCategoryPostSave(Events\CategoryEvent $event)
    {
        $category = $event->getCategory();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "category",
                "object"    => "category",
                "objectId"  => $category->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->factory->getIpAddressFromRequest()
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\CategoryEvent $event
     */
    public function onCategoryDelete(Events\CategoryEvent $event)
    {
        $category = $event->getCategory();
        $log = array(
            "bundle"     => "category",
            "object"     => "category",
            "objectId"   => $category->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $category->getTitle()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
