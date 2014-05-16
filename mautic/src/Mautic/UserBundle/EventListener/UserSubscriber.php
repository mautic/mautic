<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\UserBundle\Event as Events;
use Mautic\UserBundle\UserEvents;

/**
 * Class UserSubscriber
 *
 * @package Mautic\UserBundle\EventListener
 */
class UserSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_MENU          => array('onBuildMenu', 9997),
            CoreEvents::BUILD_ROUTE         => array('onBuildRoute', 0),
            CoreEvents::GLOBAL_SEARCH       => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST  => array('onBuildCommandList', 0),
            ApiEvents::BUILD_ROUTE          => array('onBuildApiRoute', 0),
            UserEvents::USER_PRE_SAVE       => array('onUserPreSave', 0),
            UserEvents::USER_POST_SAVE      => array('onUserPostSave', 0),
            UserEvents::USER_POST_DELETE    => array('onUserDelete', 0),
            UserEvents::ROLE_PRE_SAVE       => array('onRolePreSave', 0),
            UserEvents::ROLE_POST_SAVE      => array('onRolePostSave', 0),
            UserEvents::ROLE_POST_DELETE    => array('onRoleDelete', 0)
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
        $path = __DIR__ . "/../Resources/config/routing/routing.php";
        $event->addRoutes($path);
    }

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        if ($this->container->get('mautic.security')->isGranted('user:users:view')) {
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
                $userResults['count'] = count($users);
                $event->addResults('mautic.user.user.header.index', $userResults);
            }
        }

        if ($this->container->get('mautic.security')->isGranted('user:roles:view')) {
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
                $roleResults['count'] = count($roles);
                $event->addResults('mautic.user.role.header.index', $roleResults);
            }
        }
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildApiRoute(RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing/api.php";
        $event->addRoutes($path);
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        $security   = $this->container->get("mautic.security");
        if ($security->isGranted('user:users:view')) {
            $event->addCommands(
                'mautic.user.user.header.index',
                $this->container->get('mautic.model.user')->getCommandList()
            );
        }
        if ($security->isGranted('user:roles:view')) {
            $event->addCommands(
                'mautic.user.role.header.index',
                $this->container->get('mautic.model.role')->getCommandList()
            );
        }
    }

    /**
     * Obtain changes to enter into audit log
     *
     * @param Events\UserEvent $event
     */
    public function onUserPreSave(Events\UserEvent $event)
    {
        //stash changes
        $this->userChanges = $event->getChanges();
    }

    /**
     * Add a user entry to the audit log
     *
     * @param Events\UserEvent $event
     */
    public function onUserPostSave(Events\UserEvent $event)
    {
        $user = $event->getUser();

        if (!empty($this->userChanges)) {
            $serializer = $this->container->get('jms_serializer');
            $details    = $serializer->serialize($this->userChanges, 'json');
            $log        = array(
                "bundle"    => "user",
                "object"    => "user",
                "objectId"  => $user->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->container->get('mautic.model.auditlog')->writeToLog($log);
        }
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
     * Obtain changes to enter into audit log
     *
     * @param Events\RoleEvent $event
     */
    public function onRolePreSave(Events\RoleEvent $event)
    {
        //stash changes
        $this->roleChanges = $event->getChanges();
    }

    /**
     * Add a role entry to the audit log
     *
     * @param Events\RoleEvent $event
     */
    public function onRolePostSave(Events\RoleEvent $event)
    {
        $role = $event->getRole();
        if (!empty($this->roleChanges)) {
            $serializer = $this->container->get('jms_serializer');
            $details    = $serializer->serialize($this->roleChanges, 'json');
            $log        = array(
                "bundle"    => "user",
                "object"    => "role",
                "objectId"  => $role->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->container->get('mautic.model.auditlog')->writeToLog($log);
        }
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