<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;

/**
 * Class ChannelController
 *
 * @package Mautic\DefaultController\Controller
 */
class ChannelController extends FormController
{

    public function indexAction($channelId)
    {
        $currentUser = $this->factory->getUser();
        $model       = $this->factory->getModel('chat.channel');
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
                'mauticContent' => 'chatchannel',
                'target'        => '#ChatConversation'
            )
        ));
    }

    public function newAction()
    {
        if (!$this->factory->getSecurity()->isGranted('chat:channels:create')) {
            return $this->accessDenied();
        }

        $model  = $this->factory->getModel('chat.channel');
        $entity = $model->getEntity();

        $action = $this->generateUrl('mautic_chatchannel_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                $valid = $this->isFormValid($form);
                if ($valid) {
                    $model->saveEntity($entity);

                    return $this->forward('MauticChatBundle:Channel:index', array(
                        'channelId' => $entity->getId()
                    ));
                }
            } else {
                return $this->forward('MauticChatBundle:Default:index');
            }
        }

        $formView = $this->setFormTheme($form, 'MauticChatBundle:Channel:form.html.php', 'MauticChatBundle:FormChannel');

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'        => $formView,
                'contentOnly' => false
            ),
            'contentTemplate' => 'MauticChatBundle:Channel:form.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'chatchannel',
                'target'        => '#ChatList'
            )
        ));
    }

    public function editAction($objectId = 0)
    {
        $model  = $this->factory->getModel('chat.channel');
        $entity = $model->getEntity($objectId);

        //not found
        if ($entity === null) {
            return $this->forward('MauticChatBundle:Default:index');
        }  elseif (!$this->factory->getSecurity()->hasEntityAccess(
            true, 'chat:channels:editother', $entity->getCreatedBy()
        )) {
            return $this->accessDenied();
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

                    return $this->forward('MauticChatBundle:Channel:index', array(
                        'channelId' => $entity->getId()
                    ));
                }
            } else {
                return $this->forward('MauticChatBundle:Default:index');
            }
        }

        $formView = $this->setFormTheme($form, 'MauticChatBundle:Channel:form.html.php', 'MauticChatBundle:FormChannel');

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $formView),
            'contentTemplate' => 'MauticChatBundle:Channel:form.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'chatchannel',
                'target'        => '#ChatConversation'
            )
        ));
    }

    /**
     * Archive the channel
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function archiveAction($objectId) {
        $returnUrl   = $this->generateUrl('mautic_chat_index');
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticChatBundle:Default:index',
            'passthroughVars' => array(
                'mauticContent' => 'chat',
                'target'        => '#ChatConversation'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('chat.channel');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                true, 'chat:channels:archiveother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            }

            $model->archiveChannel($entity);
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }
}