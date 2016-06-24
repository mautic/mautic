<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event as Events;

/**
 * Class DynamicContentSubscriber
 *
 * @package Mautic\DynamicContentBundle\EventListener
 */
class DynamicContentSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            DynamicContentEvents::POST_SAVE    => ['onPostSave', 0],
            DynamicContentEvents::POST_DELETE  => ['onDelete', 0]
        ];
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onPostSave(Events\DynamicContentEvent $event)
    {
        $entity = $event->getDynamicContent();
        if ($details = $event->getChanges()) {
            $log = [
                "bundle"    => "dynamicContent",
                "object"    => "dynamicContent",
                "objectId"  => $entity->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details
            ];
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onDelete(Events\DynamicContentEvent $event)
    {
        $entity = $event->getDynamicContent();
        $log = [
            "bundle"     => "dynamicContent",
            "object"     => "dynamicContent",
            "objectId"   => $entity->getId(),
            "action"     => "delete",
            "details"    => ['name' => $entity->getName()]
        ];
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}