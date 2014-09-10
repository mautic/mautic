<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CampaignBundle\Entity\CampaignEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class CampaignEventController extends CommonFormController
{
    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $success = 0;
        $valid   = $cancelled = false;
        $method  = $this->request->getMethod();
        $session = $this->factory->getSession();

        if ($method == 'POST') {
            $triggerEvent = $this->request->request->get('campaignevent');
            $eventType    = $triggerEvent['type'];
        } else {
            $eventType    = $this->request->query->get('type');
            $triggerEvent = array('type' => $eventType);
        }

        //ajax only for form fields
        if (!$eventType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the builder event
        $events = $this->factory->getModel('campaign')->getEvents();
        $form   = $this->get('form.factory')->create('campaignevent', $triggerEvent, array(
            'action'   => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'new')),
            'settings' => $events[$eventType]
        ));
        $triggerEvent['settings'] = $events[$eventType];

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new' . uniqid();

                    //save the properties to session
                    $events             = $session->get('mautic.campaigns.add');
                    $formData           = $form->getData();
                    $triggerEvent       = array_merge($triggerEvent, $formData);
                    $triggerEvent['id'] = $keyId;
                    if (empty($triggerEvent['name'])) {
                        //set it to the event default
                        $triggerEvent['name'] = $this->get('translator')->trans($triggerEvent['settings']['label']);
                    }
                    $events[$keyId] = $triggerEvent;
                    $session->set('mautic.campaigns.add', $events);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $eventType);
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal                 = false;
            $formView                   = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', 'MauticCampaignBundle:EventForm');
            $viewParams['form']         = $formView;
            $header                     = $triggerEvent['settings']['label'];
            $viewParams['actionHeader'] = $this->get('translator')->trans($header);
        }

        $passthroughVars = array(
            'mauticContent' => 'campaignEvent',
            'success'       => $success,
            'route'         => false
        );

        if (!empty($keyId)) {
            //prevent undefined errors
            $entity       = new CampaignEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $template = (empty($triggerEvent['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                : $triggerEvent['settings']['template'];

            $passthroughVars['eventId']   = $keyId;
            $passthroughVars['eventHtml'] = $this->renderView($template, array(
                'inForm' => true,
                'event'  => $triggerEvent,
                'id'     => $keyId
            ));
        }

        if ($closeModal) {
            //just close the modal
            $passthroughVars['closeModal'] = 1;
            $response                      = new JsonResponse($passthroughVars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        } else {
            return $this->ajaxAction(array(
                'contentTemplate' => 'MauticCampaignBundle:Event:form.html.php',
                'viewParameters'  => $viewParams,
                'passthroughVars' => $passthroughVars
            ));
        }
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        $session      = $this->factory->getSession();
        $method       = $this->request->getMethod();
        $events       = $session->get('mautic.campaigns.add', array());
        $success      = 0;
        $valid        = $cancelled = false;
        $triggerEvent = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ($triggerEvent !== null) {
            $eventType = $triggerEvent['type'];

            //ajax only for form fields
            if (!$eventType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array(
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create'
                ), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            //fire the builder event
            $events = $this->factory->getModel('campaign')->getEvents();
            $form   = $this->get('form.factory')->create('campaignevent', $triggerEvent, array(
                'action'   => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'new')),
                'settings' => $events[$eventType]
            ));
            $triggerEvent['settings'] = $events[$eventType];

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //form is valid so process the data

                        //save the properties to session
                        $session  = $this->factory->getSession();
                        $events   = $session->get('mautic.campaigns.add');
                        $formData = $form->getData();
                        //overwrite with updated data
                        $triggerEvent = array_merge($events[$objectId], $formData);
                        if (empty($triggerEvent['name'])) {
                            //set it to the event default
                            $triggerEvent['name'] = $this->get('translator')->trans($triggerEvent['settings']['label']);
                        }
                        $events[$objectId] = $triggerEvent;
                        $session->set('mautic.campaigns.add', $events);

                        //generate HTML for the field
                        $keyId = $objectId;
                    }
                }
            }

            $viewParams = array('type' => $eventType);
            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                $closeModal                 = false;
                $formView                   = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', 'MauticCampaignBundle:EventForm');
                $viewParams['form']         = $formView;
                $viewParams['actionHeader'] = $this->get('translator')->trans($triggerEvent['settings']['label']);
            }

            $passthroughVars = array(
                'mauticContent' => 'campaignEvent',
                'success'       => $success,
                'route'         => false
            );

            if (!empty($keyId)) {
                $passthroughVars['eventId'] = $keyId;

                //prevent undefined errors
                $entity       = new CampaignEvent();
                $blank        = $entity->convertToArray();
                $triggerEvent = array_merge($blank, $triggerEvent);
                $template     = (empty($triggerEvent['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                    : $triggerEvent['settings']['template'];

                $passthroughVars['eventId']   = $keyId;
                $passthroughVars['eventHtml'] = $this->renderView($template, array(
                    'inForm' => true,
                    'event'  => $triggerEvent,
                    'id'     => $keyId
                ));
            }

            if ($closeModal) {
                //just close the modal
                $passthroughVars['closeModal'] = 1;
                $response                      = new JsonResponse($passthroughVars);
                $response->headers->set('Content-Length', strlen($response->getContent()));

                return $response;
            } else {
                return $this->ajaxAction(array(
                    'contentTemplate' => 'MauticCampaignBundle:Event:form.html.php',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars
                ));
            }
        } else {
            $response = new JsonResponse(array('success' => 0));
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        }
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        $session = $this->factory->getSession();
        $events  = $session->get('mautic.campaigns.add', array());
        $delete  = $session->get('mautic.campaigns.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $triggerEvent = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $triggerEvent !== null) {
            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.campaigns.remove', $delete);
            }

            $template = (empty($triggerEvent['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                : $triggerEvent['settings']['template'];

            //prevent undefined errors
            $entity       = new CampaignEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $dataArray = array(
                'mauticContent'  => 'campaignEvent',
                'success'        => 1,
                'target'         => '#triggerEvent' . $objectId,
                'route'          => false,
                'eventId'        => $objectId,
                'replaceContent' => true,
                'eventHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'event'   => $triggerEvent,
                    'id'      => $objectId,
                    'deleted' => true
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));

        return $response;
    }

    /**
     * Undeletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function undeleteAction ($objectId)
    {
        $session = $this->factory->getSession();
        $events  = $session->get('mautic.campaigns.add', array());
        $delete  = $session->get('mautic.campaigns.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $triggerEvent = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $triggerEvent !== null) {

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.campaigns.remove', $delete);
            }

            $template = (empty($triggerEvent['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                : $triggerEvent['settings']['template'];

            //prevent undefined errors
            $entity       = new CampaignEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $dataArray = array(
                'mauticContent'  => 'campaignEvent',
                'success'        => 1,
                'target'         => '#triggerEvent' . $objectId,
                'route'          => false,
                'eventId'        => $objectId,
                'replaceContent' => true,
                'eventHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'event'   => $triggerEvent,
                    'id'      => $objectId,
                    'deleted' => false
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));

        return $response;
    }
}