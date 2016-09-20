<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\UserBundle\Model\RoleModel;
use Mautic\UserBundle\Model\UserModel;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\UserBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var RoleModel
     */
    protected $userRoleModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param UserModel $userModel
     * @param RoleModel $roleModel
     */
    public function __construct(UserModel $userModel, RoleModel $roleModel)
    {
        $this->userModel = $userModel;
        $this->userRoleModel = $roleModel;
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

    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        if ($this->security->isGranted('user:users:view')) {
            $users = $this->userModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));

            if (count($users) > 0) {
                $userResults = array();
                $canEdit     = $this->security->isGranted('user:users:edit');
                foreach ($users as $user) {
                    $userResults[] = $this->templating->renderResponse(
                        'MauticUserBundle:SubscribedEvents\Search:global_user.html.php',
                        array(
                            'user'    => $user,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($users) > 5) {
                    $userResults[] = $this->templating->renderResponse(
                        'MauticUserBundle:SubscribedEvents\Search:global_user.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($users) - 5)
                        )
                    )->getContent();
                }
                $userResults['count'] = count($users);
                $event->addResults('mautic.user.users', $userResults);
            }
        }

        if ($this->security->isGranted('user:roles:view')) {
            $roles = $this->userRoleModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $str
                ));
            if (count($roles)) {
                $roleResults = array();
                $canEdit     = $this->security->isGranted('user:roles:edit');

                foreach ($roles as $role) {
                    $roleResults[] = $this->templating->renderResponse(
                        'MauticUserBundle:SubscribedEvents\Search:global_role.html.php',
                        array(
                            'role'    => $role,
                            'canEdit' => $canEdit
                        )
                    )->getContent();
                }
                if (count($roles) > 5) {
                    $roleResults[] = $this->templating->renderResponse(
                        'MauticUserBundle:SubscribedEvents\Search:global_role.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($roles) - 5)
                        )
                    )->getContent();
                }
                $roleResults['count'] = count($roles);
                $event->addResults('mautic.user.roles', $roleResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted('user:users:view')) {
            $event->addCommands(
                'mautic.user.users',
                $this->userModel->getCommandList()
            );
        }
        if ($this->security->isGranted('user:roles:view')) {
            $event->addCommands(
                'mautic.user.roles',
                $this->userRoleModel->getCommandList()
            );
        }
    }

}