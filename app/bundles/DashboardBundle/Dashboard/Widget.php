<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Dashboard;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\DashboardBundle\Model\DashboardModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class Widget
{
    const FORMAT_HUMAN   = 'M j, Y';
    const FORMAT_MYSQL   = 'Y-m-d';

    /**
     * @var DashboardModel
     */
    private $dashboardModel;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param DashboardModel $dashboardModel
     * @param UserHelper     $userHelper
     * @param Session        $session
     */
    public function __construct(DashboardModel $dashboardModel, UserHelper $userHelper, Session $session)
    {
        $this->dashboardModel = $dashboardModel;
        $this->userHelper     = $userHelper;
        $this->session        = $session;
    }

    /**
     * Get ready widget to populate in template.
     *
     * @param int $widgetId
     *
     * @return bool|\Mautic\DashboardBundle\Entity\Widget
     */
    public function get($widgetId)
    {
        /** @var \Mautic\DashboardBundle\Entity\Widget $widget */
        $widget = $this->dashboardModel->getEntity($widgetId);

        if (!$widget->getId()) {
            throw new NotFoundHttpException('Not found.');
        }

        if ($widget->getCreatedBy() !== $this->userHelper->getUser()->getId()) {
            // Unauthorized access
            throw new AccessDeniedException();
        }

        $filter = $this->dashboardModel->getDefaultFilter();

        $this->dashboardModel->populateWidgetContent($widget, $filter);

        return $widget;
    }

    /**
     * Set filter from POST to session.
     *
     * @param Request $request
     *
     * @throws \Exception
     */
    public function setFilter(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        $dateRangeFilter = $request->get('daterange', []);

        if (!empty($dateRangeFilter['date_from'])) {
            $from = new \DateTime($dateRangeFilter['date_from']);
            $this->session->set('mautic.dashboard.date.from', $from->format(self::FORMAT_MYSQL));
        }

        if (!empty($dateRangeFilter['date_to'])) {
            $to = new \DateTime($dateRangeFilter['date_to']);
            $this->session->set('mautic.dashboard.date.to', $to->format(self::FORMAT_MYSQL));
        }

        $this->dashboardModel->clearDashboardCache();
    }
}
