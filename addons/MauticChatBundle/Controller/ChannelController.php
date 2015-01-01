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
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ChannelController
 *
 * @package Mautic\DefaultController\Controller
 */
class ChannelController extends FormController
{

    public function indexAction ($channelId)
    {
        $currentUser = $this->factory->getUser();
        $model       = $this->factory->getModel('addon.mauticChat.channel');
        $entity      = $model->getEntity($channelId);

        if ($entity === null) {
            return $this->forward('MauticChatBundle:Default:index');
        }

        //make sure user is part of the chat if it is private
        if ($entity->isPrivate()) {
            $privateMembers = $entity->getPrivateUsers();
            if (!$privateMembers->contains($currentUser)) {
                //access denied
                $this->factory->getSession()->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans(
                        'mautic.core.error.accessdenied',
                        array(),
                        'flashes'
                    )
                );

                return $this->forward('MauticChatBundle:Default:index');
            }
        }

        $messages = $model->getGroupMessages($entity);

        //get the HTML
        return $this->delegateView(array(
            'viewParameters'  => array(
                'messages'            => $messages,
                'me'                  => $currentUser,
                'channel'             => $entity,
                'insertUnreadDivider' => true
            ),
            'contentTemplate' => 'MauticChatBundle:Channel:index.html.php',
            'passthroughVars' => array(
                'target'        => '#ChatConversation'
            )
        ));
    }

    /**
     * Generate channel list
     *
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function listAction ($page = 1)
    {
        $session     = $this->factory->getSession();
        $attrFilters = $session->get('mautic.chat.channel.attr.filters', array());

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();

            if ($this->request->query->has('filter')) {
                $attrFilters = $session->get('mautic.chat.channel.attr.filters', array());
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

                $session->set('mautic.chat.channel.attr.filters', $attrFilters);
            }
        }

        //set limits
        $limit = $session->get('mautic.chat.channel.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        /** @var \MauticAddon\MauticChatBundle\Model\ChannelModel $channelModel */
        $channelModel = $this->factory->getModel('addon.mauticChat.channel');
        $settings = $channelModel->getSettings();

        $orderBy    = $session->get('mautic.chat.channel.orderby', 'c.name');
        $orderByDir = $session->get('mautic.chat.channel.orderbydir', 'ASC');

        $filters = $session->get('mautic.chat.channel.filters', array());

        list($unreadCounts, $unreadIds) = $channelModel->getChannelsWithUnreadMessages(true);

        foreach ($attrFilters as $attr) {
            switch ($attr) {
                case 'newmessages':
                    if (empty($unreadIds)) {
                        $unreadIds = array(0);
                    }

                    $filters[] = array(
                        'column' => 'c.id',
                        'expr'   => 'in',
                        'value'  => $unreadIds
                    );

                    break;
                case 'invisible':
                    if (!empty($settings['visible'])) {
                        $filters[] = array(
                            'column' => 'c.id',
                            'expr'   => 'notIn',
                            'value'  => $settings['visible']
                        );
                    }
                    break;
                case 'silent':
                    if (!empty($settings['silent'])) {
                        $filters[] = array(
                            'column' => 'c.id',
                            'expr'   => 'in',
                            'value'  => $settings['silent']
                        );
                    }
                    break;
                case 'mute':
                    if (!empty($settings['mute'])) {
                        $filters[] = array(
                            'column' => 'c.id',
                            'expr'   => 'in',
                            'value'  => $settings['mute']
                        );
                    }
                    break;
                case 'archived':
                    $filters[] = array(
                        'column' => 'c.isPublished',
                        'expr'   => 'eq',
                        'value'  => false
                    );
                    break;

                case 'subscribed':
                    $filters[] = array(
                        'column' => 'IDENTITY(s.user)',
                        'expr'   => 'eq',
                        'value'  => $this->factory->getUser()->getId()
                    );
                    break;
            }
        }

        //do some default filtering
        $filter = array('string' => '', 'force' => $filters);

        $channels = $this->factory->getModel('addon.mauticChat.channel')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
                'userId'     => $this->factory->getUser()->getId()
            ));

        //Check to see if the number of pages match the number of users
        $count = count($channels);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (floor($limit / $count)) ?: 1;
            $session->set('mautic.chat.channel.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_chatchannel_list', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array(
                    'page' => $lastPage
                ),
                'contentTemplate' => 'MauticChatBundle:Channel:list',
                'passthroughVars' => array(
                    'mauticContent'  => 'chatChannel',
                    'replaceContent' => 'true',
                    'route'          => false
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.chat.channel.page', $page);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'    => $channels,
                'page'     => $page,
                'limit'    => $limit,
                'settings' => $settings,
                'me'       => $this->factory->getUser(),
                'filters'  => $attrFilters,
                'unread'   => $unreadCounts,
                'stats'    => $channelModel->getUserChannelStats()
            ),
            'contentTemplate' => 'MauticChatBundle:Channel:list.html.php',
            'passthroughVars' => array(
                'route'          => $this->generateUrl('mautic_chatchannel_list', array('page' => $page)),
                'mauticContent'  => 'chatChannel',
                'replaceContent' => 'true',
                'route'          => false
            )
        ));
    }

    public function newAction ()
    {
        if (!$this->factory->getSecurity()->isGranted('addon:mauticChat:channels:create')) {
            return $this->modalAccessDenied();
        }

        $model  = $this->factory->getModel('addon.mauticChat.channel');
        $entity = $model->getEntity();

        $action = $this->generateUrl('mautic_chatchannel_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        $closeModal = false;
        $valid      = false;
        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                if ($valid) {
                    $model->saveEntity($entity);
                    $closeModal = true;
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            $vars = array(
                'closeModal' => 1
            );

            if ($valid && !$cancelled) {
                $newChannelResponse = $this->forward('MauticChatBundle:Default:index', array(
                    'ignoreAjax'  => true,
                    'ignoreModal' => true
                ));

                $vars['chatHtml']      = $newChannelResponse->getContent();
                $vars['mauticContent'] = "chatChannel";
            }

            $response = new JsonResponse($vars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        } else {
            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form' => $form->createView()
                ),
                'contentTemplate' => 'MauticChatBundle:Channel:form.html.php'
            ));
        }
    }

    public function editAction ($objectId = 0)
    {
        $model      = $this->factory->getModel('addon.mauticChat.channel');
        $entity     = $model->getEntity($objectId);
        $closeModal = $valid = false;

        //not found
        if ($entity === null) {
            return $this->forward('MauticChatBundle:Default:index');
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            true, false, $entity->getCreatedBy()
        )
        ) {
            return $this->modalAccessDenied();
        }

        $action = $this->generateUrl('mautic_chatchannel_action', array(
            'objectAction' => 'edit',
            'objectId'     => $objectId
        ));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                if ($valid) {
                    $model->saveEntity($entity);

                    $closeModal = true;
                }
            } else {
                $closeModal = true;
            }
        }

        if ($closeModal) {
            $vars = array(
                'closeModal' => 1
            );

            if ($valid && !$cancelled) {
                $newChannelResponse = $this->forward('MauticChatBundle:Default:index', array(
                    'ignoreAjax'  => true,
                    'ignoreModal' => true
                ));

                $vars['chatHtml']      = $newChannelResponse->getContent();
                $vars['mauticContent'] = "chatChannel";
            }

            $response = new JsonResponse($vars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        } else {
            return $this->delegateView(array(
                'viewParameters'  => array(
                    'form' => $form->createView()
                ),
                'contentTemplate' => 'MauticChatBundle:Channel:form.html.php'
            ));
        }
    }
}