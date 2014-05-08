<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;


use Mautic\CoreBundle\CoreEvents;
use Mautic\ApiBundle\ApiEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\ApiBundle\Event as Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ApiSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class ApiSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param ContainerInterface $container
     */
    public function __construct (ContainerInterface $container, RequestStack $request_stack)
    {
        $this->container = $container;
        $this->request   = $request_stack->getCurrentRequest();
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::BUILD_MENU          => array('onBuildMenu', 9998),
            CoreEvents::BUILD_ROUTE         => array('onBuildRoute', 0),
            CoreEvents::GLOBAL_SEARCH       => array('onGlobalSearch', 0),
            ApiEvents::CLIENT_POST_SAVE     => array('onClientPostSave', 0),
            ApiEvents::CLIENT_POST_DELETE   => array('onClientDelete', 0)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu (MauticEvents\MenuEvent $event)
    {
        $path  = __DIR__ . "/../Resources/config/menu.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute (MauticEvents\RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }

    /**
     * @param GlobalSearchEvent $event
     */
    public function onGlobalSearch (MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->container->get('mautic.security')->isGranted('api:clients:view')) {
            $str     = $event->getSearchString();
            if (empty($str)) {
                return;
            }

            $clients = $this->container->get('mautic.model.client')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($clients) > 0) {
                $clientResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('api:clients:edit');
                foreach ($clients as $client) {
                    $clientResults[] = $this->container->get('templating')->renderResponse(
                        'MauticApiBundle:Search:client.html.php',
                        array(
                            'client'  => $client,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($clients) > 5) {
                    $clientResults[] = $this->container->get('templating')->renderResponse(
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
     * Add a client change entry to the audit log
     *
     * @param Events\ClientEvent $event
     */
    public function onClientPostSave(Events\ClientEvent $event)
    {
        $client = $event->getClient();

        //because JMS Serializer doesn't work correctly with the Client entity since it extends FosOAuthServerBundle's
        //base client, we have to manually set the fields so as to prevent sensitive details from getting added to the
        //log

        $serializer = $this->container->get('jms_serializer');
        $data       = array(
            "id" => $client->getId(),
            "name" => $client->getName(),
            "redirectUris" => $client->getRedirectUris()
        );
        $details    = $serializer->serialize($data, 'json');
        $log = array(
            "bundle"     => "api",
            "object"     => "client",
            "objectId"   => $client->getId(),
            "action"     => ($event->isNew()) ? "create" : "update",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }

    /**
     * Add a role delete entry to the audit log
     *
     * @param Events\Events $event
     */
    public function onClientDelete(Events\ClientEvent $event)
    {
        $client = $event->getClient();

        //because JMS Serializer doesn't work correctly with the Client entity since it extends FosOAuthServerBundle's
        //base client, we have to manually set the fields so as to prevent sensitive details from getting added to the
        //log

        $serializer = $this->container->get('jms_serializer');
        $data       = array(
            "id" => $client->getId(),
            "name" => $client->getName(),
            "redirectUris" => $client->getRedirectUris()
        );
        $details    = $serializer->serialize($data, 'json');

        $log = array(
            "bundle"     => "api",
            "object"     => "client",
            "objectId"   => $client->getId(),
            "action"     => "delete",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }
}