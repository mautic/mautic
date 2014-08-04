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

    public function newAction()
    {
        if (!$this->factory->getSecurity()->isGranted('chat:channel:create')) {
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

                    return $this->delegateView(array(
                        'viewParameters'  => array(
                            'channel'     => $entity,
                            'contentOnly' => false
                        ),
                        'contentTemplate' => 'MauticChatBundle:Channel:index.html.php',
                        'passthroughVars' => array(
                            'mauticContent' => 'chatchannel'
                        )
                    ));
                }
            } else {
                return $this->delegateView(array(
                    'viewParameters'  => array(
                        'contentOnly' => false
                    ),
                    'contentTemplate' => 'MauticChatBundle:Default:index.html.php',
                    'passthroughVars' => array(
                        'mauticContent' => 'chat'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'        => $form->createView(),
                'contentOnly' => false
            ),
            'contentTemplate' => 'MauticChatBundle:Channel:form.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'chatchannel'
            )
        ));
    }

    public function editAction($objectId = 0)
    {
        $model  = $this->factory->getModel('chat.channel');
        $entity = $model->getEntity($objectId);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_chat_index');

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'contentTemplate' => 'MauticChatBundle:Default:index',
                'passthroughVars' => array(
                    'mauticContent' => 'chat'
                )
            ));
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

                    return $this->postActionRedirect(array(
                        'viewParameters'  => array('channel' => $entity),
                        'contentTemplate' => 'MauticChatBundle:Channel:index.html.php',
                        'passthroughVars' => array(
                            'mauticContent' => 'chatchannel'
                        )
                    ));
                }
            } else {
                return $this->postActionRedirect(array(
                    'contentTemplate' => 'MauticChatBundle:Default:index.html.php',
                    'passthroughVars' => array(
                        'mauticContent' => 'chatchannel'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array('form' => $form->createView()),
            'contentTemplate' => 'MauticChatBundle:Channel:form.html.php',
            'passthroughVars' => array(
                'mauticContent' => 'chatchannel'
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
                'mauticContent' => 'chat'
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