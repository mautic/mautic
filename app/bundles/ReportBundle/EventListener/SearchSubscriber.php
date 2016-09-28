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
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\ReportBundle\Model\ReportModel;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\ReportBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var ReportModel
     */
    protected $reportModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param UserHelper  $userHelper
     * @param ReportModel $reportModel
     */
    public function __construct(UserHelper $userHelper, ReportModel $reportModel)
    {
        $this->userHelper  = $userHelper;
        $this->reportModel = $reportModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter     = array("string" => $str, "force" => array());

        $permissions = $this->security->isGranted(
            array('report:reports:viewown', 'report:reports:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['report:reports:viewown'] || $permissions['report:reports:viewother']) {
            if (!$permissions['report:reports:viewother']) {
                $filter['force'][] = array(
                    'column' => 'IDENTITY(r.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId()
                );
            }

            $items = $this->reportModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            $count = count($items);
            if ($count > 0) {
                $results = array();

                foreach ($items as $item) {
                    $results[] = $this->templating->renderResponse(
                        'MauticReportBundle:SubscribedEvents\Search:global.html.php',
                        array('item' => $item)
                    )->getContent();
                }
                if ($count > 5) {
                    $results[] = $this->templating->renderResponse(
                        'MauticReportBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => ($count - 5)
                        )
                    )->getContent();
                }
                $results['count'] = $count;
                $event->addResults('mautic.report.reports', $results);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('report:reports:viewown', 'report:reports:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.report.reports',
                $this->reportModel->getCommandList()
            );
        }
    }
}