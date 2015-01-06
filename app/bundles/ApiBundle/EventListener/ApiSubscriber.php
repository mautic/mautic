<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\ApiEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\ApiBundle\Event as Events;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class ApiSubscriber
 */
class ApiSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ApiEvents::CLIENT_POST_SAVE     => array('onClientPostSave', 0),
            ApiEvents::CLIENT_POST_DELETE   => array('onClientDelete', 0),
            //CoreEvents::BUILD_ROUTE         => array('onBuildRoute', 5),
        );
    }

    /**
     * Add a client change entry to the audit log
     *
     * @param Events\ClientEvent $event
     */
    public function onClientPostSave(Events\ClientEvent $event)
    {
        $client = $event->getClient();
        if ($details = $event->getChanges()) {
            $log        = array(
                'bundle'    => 'api',
                'object'    => 'client',
                'objectId'  => $client->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->factory->getIpAddressFromRequest()
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a role delete entry to the audit log
     *
     * @param Events\ClientEvent $event
     */
    public function onClientDelete(Events\ClientEvent $event)
    {
        $client = $event->getClient();
        $log = array(
            'bundle'     => 'api',
            'object'     => 'client',
            'objectId'   => $client->deletedId,
            'action'     => 'delete',
            'details'    => array('name' => $client->getName()),
            'ipAddress'  => $this->factory->getIpAddressFromRequest()
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
