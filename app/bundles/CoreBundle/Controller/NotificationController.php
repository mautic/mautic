<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

class NotificationController extends AbstractStandardFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page)
    {
        return $this->indexStandard($page);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrderColumn()
    {
        return 'dateAdded';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrderDirection()
    {
        return 'DESC';
    }

    /**
     * Get the route base for getIndexRoute() and getActionRoute() if they do not meet the mautic_*_index and mautic_*_action standards.
     *
     * @return mixed
     */
    protected function getRouteBase()
    {
        return 'mautic_user_notification';
    }

    /**
     * @param       $start
     * @param       $limit
     * @param       $filter
     * @param       $orderBy
     * @param       $orderByDir
     * @param array $args
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        return parent::getIndexItems(
            $start,
            $limit,
            $filter,
            $orderBy,
            $orderByDir,
            $args
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getControllerBase()
    {
        return 'MauticCoreBundle:Notification';
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'core.notification';
    }
}
