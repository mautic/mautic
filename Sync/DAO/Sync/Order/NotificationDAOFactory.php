<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;

class NotificationDAOFactory
{
    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @param string $message
     *
     * @return NotificationDAO
     */
    public function create(ObjectChangeDAO $objectChangeDAO, string $message): NotificationDAO
    {
        return new NotificationDAO($objectChangeDAO, $message);
    }
}