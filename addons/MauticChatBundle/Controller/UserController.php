<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\UserBundle\Entity\User;

/**
 * Class UserController
 *
 * @package Mautic\UserController\Controller
 */
class UserController extends FormController
{

    /**
     * Generate user list
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function listAction($page = 1)
    {
        $session = $this->factory->getSession();
        $attrFilters = $session->get('mautic.chat.user.attr.filters', array());

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();

            if ($this->request->query->has('filter')) {
                $attrFilters = $session->get('mautic.chat.user.attr.filters', array());
                $attr        = $this->request->query->get('filter');
                $enabled     = InputHelper::boolean($this->request->query->get('enabled'));

                if ($enabled && !in_array($attr, $attrFilters)) {
                    $attrFilters[] = $attr;
                } elseif (!$enabled && in_array($attr, $attrFilters)) {
                    $key = array_search($attr, $attrFilters);
                    if ($key !== false) {
                        unset($attrFilters[$key]);
                    }
                }

                $session->set('mautic.chat.user.attr.filters', $attrFilters);
            }
        }

        //set limits
        $limit = $session->get('mautic.chat.user.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        /** @var \MauticAddon\MauticChatBundle\Model\ChatModel $model */
        $model    = $this->factory->getModel('addon.mauticChat.chat');
        $settings = $model->getSettings();

        $orderBy    = $session->get('mautic.chat.user.orderby', 'u.lastName, u.firstName, u.username');
        $orderByDir = $session->get('mautic.chat.user.orderbydir', 'ASC');

        $filters = $session->get('mautic.chat.user.filters', array());

        $filters[] = array(
            'column' => 'u.isPublished',
            'expr'   => 'eq',
            'value'  => true
        );

        $filters[] = array(
            'column' => 'u.id',
            'expr'   => 'neq',
            'value'  => $this->factory->getUser()->getId()
        );

        list($unreadCounts, $unreadIds)  = $model->getUnreadCounts(true);

        foreach ($attrFilters as $attr) {
            switch ($attr) {
                case 'newmessages':
                    if (empty($unreadIds)) {
                        $unreadIds = array(0);
                    }

                    $filters[] = array(
                        'column' => 'u.id',
                        'expr'   => 'in',
                        'value'  => $unreadIds
                    );

                    break;
                case 'invisible':
                    if (!empty($settings['visible'])) {
                        $filters[] = array(
                            'column' => 'u.id',
                            'expr'   => 'notIn',
                            'value'  => $settings['visible']
                        );
                    }
                    break;
                case 'silent':
                    if (!empty($settings['silent'])) {
                        $filters[] = array(
                            'column' => 'u.id',
                            'expr'   => 'in',
                            'value'  => $settings['silent']
                        );
                    }
                    break;
                case 'mute':
                    if (!empty($settings['mute'])) {
                        $filters[] = array(
                            'column' => 'u.id',
                            'expr'   => 'in',
                            'value'  => $settings['mute']
                        );
                    }
                    break;
            }
        }


        //do some default filtering
        $filter = array('string' => '', 'force' => $filters);

        $users = $this->factory->getModel('user')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        //Check to see if the number of pages match the number of users
        $count = count($users);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (floor($limit / $count)) ?: 1;
            $session->set('mautic.chat.user.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_chat_list', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array(
                    'page' => $lastPage
                ),
                'contentTemplate' => 'MauticChatBundle:User:list',
                'passthroughVars' => array(
                    'replaceContent' => 'true',
                    'route' => false
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.chat.user.page', $page);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'       => $users,
                'page'        => $page,
                'limit'       => $limit,
                'settings'    => $settings,
                'filters'     => $attrFilters,
                'unread'      => $unreadCounts
            ),
            'contentTemplate' => 'MauticChatBundle:User:list.html.php',
            'passthroughVars' => array(
                'route'          => $this->generateUrl('mautic_chat_list', array('page' => $page)),
                'replaceContent' => 'true',
                'route' => false
            )
        ));
    }
}