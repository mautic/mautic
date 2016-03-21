<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ReportEvents::REPORT_POST_SAVE   => array('onReportPostSave', 0),
            ReportEvents::REPORT_POST_DELETE => array('onReportDelete', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param ReportEvent $event
     */
    public function onReportPostSave(ReportEvent $event)
    {
        $report = $event->getReport();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "report",
                "object"    => "report",
                "objectId"  => $report->getId(),
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
     * @param ReportEvent $event
     */
    public function onReportDelete(ReportEvent $event)
    {
        $report = $event->getReport();
        $log = array(
            "bundle"     => "report",
            "object"     => "report",
            "objectId"   => $report->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $report->getName()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
