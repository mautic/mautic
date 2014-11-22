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
            CategoryEvents::CATEGORY_POST_SAVE     => array('onCategoryPostSave', 0),
            CategoryEvents::CATEGORY_POST_DELETE   => array('onCategoryDelete', 0)
        );
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
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
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
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
