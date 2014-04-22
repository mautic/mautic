<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;


use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\UserBundle\Event as Events;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UserSubscriber
 *
 * @package Mautic\UserBundle\EventListener
 */
class UserSubscriber implements EventSubscriberInterface
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
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_MENU     => array('onBuildMenu', 9997),
            CoreEvents::BUILD_ROUTE    => array('onBuildRoute', 0),
            CoreEvents::GLOBAL_SEARCH  => array('onGlobalSearch', 0),
            UserEvents::USER_POST_SAVE => array('onUserPostSave', 0),
            UserEvents::USER_DELETE    => array('onUserDelete', 0),
            UserEvents::ROLE_POST_SAVE => array('onRolePostSave', 0),
            UserEvents::ROLE_DELETE    => array('onRoleDelete', 0)
        );
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MauticEvents\MenuEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/menu.php";
        $items = include $path;
        $event->addMenuItems($items);
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(MauticEvents\RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing.php";
        $event->addRoutes($path);
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        if ($this->container->get('mautic.security')->isGranted('user:users:view')) {
            $str   = $event->getSearchString();
            $users = $this->container->get('mautic.model.user')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($users) > 0) {
                $userResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('user:users:edit');
                foreach ($users as $user) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:user.html.php',
                        array(
                            'user'    => $user,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($users) > 5) {
                    $userResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:user.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($users) - 5)
                        )
                    )->getContent();
                }
                $event->addResults('mautic.user.user.header.index', $userResults);
            }
        }

        if ($this->container->get('mautic.security')->isGranted('user:roles:view')) {
            $str   = $event->getSearchString();
            $roles = $this->container->get('mautic.model.role')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            if (count($roles)) {
                $roleResults = array();
                $canEdit     = $this->container->get('mautic.security')->isGranted('user:roles:edit');

                foreach ($roles as $role) {
                    $roleResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:role.html.php',
                        array(
                            'role'    => $role,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($roles) > 5) {
                    $roleResults[] = $this->container->get('templating')->renderResponse(
                        'MauticUserBundle:Search:role.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($roles) - 5)
                        )
                    )->getContent();
                }
                $event->addResults('mautic.user.role.header.index', $roleResults);
            }
        }
    }

    /**
     * Add a user entry to the audit log
     *
     * @param Events\UserEvent $event
     */
    public function onUserPostSave(Events\UserEvent $event)
    {
        $user = $event->getUser();

        $serializer = $this->container->get('jms_serializer');
        $details    = $serializer->serialize($user, 'json');

        $log = array(
            "bundle"     => "user",
            "object"     => "user",
            "objectId"   => $user->getId(),
            "action"     => ($event->isNew()) ? "create" : "update",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }

    /**
     * Add a user delete entry to the audit log
     *
     * @param Events\UserEvent $event
     */
    public function onUserDelete(Events\UserEvent $event)
    {
        $user = $event->getUser();

        $serializer = $this->container->get('jms_serializer');
        $details    = $serializer->serialize($user, 'json');

        $log = array(
            "bundle"     => "user",
            "object"     => "user",
            "objectId"   => $user->getId(),
            "action"     => "delete",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }

    /**
     * Add a role entry to the audit log
     *
     * @param Events\RoleEvent $event
     */
    public function onRolePostSave(Events\RoleEvent $event)
    {
        $role = $event->getRole();

        $serializer = $this->container->get('jms_serializer');
        $details    = $serializer->serialize($role, 'json');

        $log = array(
            "bundle"     => "user",
            "object"     => "role",
            "objectId"   => $role->getId(),
            "action"     => ($event->isNew()) ? "create" : "update",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }

    /**
     * Add a role delete entry to the audit log
     *
     * @param Events\UserEvent $event
     */
    public function onRoleDelete(Events\RoleEvent $event)
    {
        $role = $event->getRole();

        $serializer = $this->container->get('jms_serializer');
        $details    = $serializer->serialize($role, 'json');

        $log = array(
            "bundle"     => "user",
            "object"     => "role",
            "objectId"   => $role->getId(),
            "action"     => "delete",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->container->get('mautic.model.auditlog')->writeToLog($log);
    }
}