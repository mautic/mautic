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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\Notification;

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
        $repo          = $this->getModel($this->getModelName())->getRepository();
        preg_match_all('/(\w+):([\w\s]*\w\b(?!:))/', $filter['string'], $matches);
        $alias         = $repo->getTableAlias();
        $matches       = reset($matches);
        /** @var EntityManager $em */
        $em         = $this->container->get(EntityManager::class);
        $fieldNames = $em->getClassMetadata(Notification::class)->getFieldNames();
        foreach ($matches as $item) {
            $parsed            = explode(':', $item);
            if (in_array($parsed[0], $fieldNames)) {
                $filter['where'][] = [
                    'col'  => $alias.'.'.$parsed[0],
                    'expr' => 'like',
                    'val'  => '%'.$parsed[1].'%',
                ];
            }
        }

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
