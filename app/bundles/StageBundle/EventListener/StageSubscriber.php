<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\StageBundle\Event as Events;
use Mautic\StageBundle\StageEvents;

/**
 * Class StageSubscriber
 */
class StageSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            StageEvents::STAGE_POST_SAVE     => array('onStagePostSave', 0),
            StageEvents::STAGE_POST_DELETE   => array('onStageDelete', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\StageEvent $event
     */
    public function onStagePostSave(Events\StageEvent $event)
    {
        $stage = $event->getStage();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "stage",
                "object"    => "stage",
                "objectId"  => $stage->getId(),
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
     * @param Events\StageEvent $event
     */
    public function onStageDelete(Events\StageEvent $event)
    {
        $stage = $event->getStage();
        $log = array(
            "bundle"     => "stage",
            "object"     => "stage",
            "objectId"   => $stage->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $stage->getName()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

}
