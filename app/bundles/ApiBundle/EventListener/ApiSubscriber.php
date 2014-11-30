<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;


use Mautic\CoreBundle\CoreEvents;
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
            CoreEvents::GLOBAL_SEARCH       => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST  => array('onBuildCommandList', 0),
            ApiEvents::CLIENT_POST_SAVE     => array('onClientPostSave', 0),
            ApiEvents::CLIENT_POST_DELETE   => array('onClientDelete', 0),
            //CoreEvents::BUILD_ROUTE         => array('onBuildRoute', 5),
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->security->isGranted('api:clients:view')) {
            $str = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $clients = $this->factory->getModel('api.client')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($clients) > 0) {
                $clientResults = array();
                $canEdit     = $this->security->isGranted('api:clients:edit');
                foreach ($clients as $client) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticApiBundle:Search:client.html.php',
                        array(
                            'client'  => $client,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($clients) > 5) {
                    $clientResults[] = $this->templating->renderResponse(
                        'MauticApiBundle:Search:client.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($clients) - 5)
                        )
                    )->getContent();
                }
                $clientResults['count'] = count($clients);
                $event->addResults('mautic.api.client.header.gs', $clientResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $security   = $this->security;
        if ($security->isGranted('api:clients:view')) {
            $event->addCommands(
                'mautic.api.client.header.index',
                $this->factory->getModel('api.client')->getCommandList()
            );
        }
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
                'ipAddress' => $this->request->server->get('REMOTE_ADDR')
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
            'ipAddress'  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}
