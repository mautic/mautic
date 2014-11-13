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
use Mautic\CampaignBundle\Entity\Event;
use Symfony\Component\HttpFoundation\JsonResponse;

class EventController extends CommonFormController
{
    private $supportedEventTypes = array('decision', 'systemaction', 'action');

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
            $event     = $this->request->request->get('campaignevent');
            $type      = $event['type'];
            $eventType = $event['eventType'];
        } else {
            $type         = $this->request->query->get('type');
            $eventType    = $this->request->query->get('eventType');
            $event        = array(
                'type'         => $type,
                'eventType'    => $eventType
            );
        }

        //set the eventType key for events
        if (!in_array($eventType, $this->supportedEventTypes)) {
            return $this->accessDenied();
        }

        //ajax only for form fields
        if (!$type ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the builder event
        $events            = $this->factory->getModel('campaign')->getEvents();
        $form              = $this->get('form.factory')->create('campaignevent', $event, array(
            'action'       => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'new')),
            'settings'     => $events[$eventType][$type]
        ));
        $event['settings'] = $events[$eventType][$type];

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new' . hash('sha1', uniqid(mt_rand()));

                    //save the properties to session
                    $addEvents   = $session->get('mautic.campaigns.add');
                    $formData    = $form->getData();
                    $event       = array_merge($event, $formData);
                    $event['id'] = $event['tempId'] = $keyId;
                    if (empty($event['name'])) {
                        //set it to the event default
                        $event['name'] = $this->get('translator')->trans($event['settings']['label']);
                    }
                    $addEvents[$keyId] = $event;
                    $session->set('mautic.campaigns.add', $addEvents);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $type);
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal                 = false;
            $formView                   = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', 'MauticCampaignBundle:EventForm');
            $viewParams['form']         = $formView;
            $header                     = $event['settings']['label'];
            $viewParams['actionHeader'] = $this->get('translator')->trans($header);
        }

        $passthroughVars = array(
            'mauticContent' => 'campaignEvent',
            'success'       => $success,
            'route'         => false
        );

        if (!empty($keyId)) {
            //prevent undefined errors
            $entity = new Event();
            $blank  = $entity->convertToArray();
            $event  = array_merge($blank, $event);

            $template = (empty($event['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                : $event['settings']['template'];

            $passthroughVars['eventId']   = $keyId;
            $passthroughVars['eventHtml'] = $this->renderView($template, array(
                'event'  => $event,
                'id'     => $keyId,
            ));
            $passthroughVars['eventType'] = $eventType;
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
        $session       = $this->factory->getSession();
        $method        = $this->request->getMethod();
        $addEvents     = $session->get('mautic.campaigns.add', array());
        $success       = 0;
        $valid         = $cancelled = false;
        $event         = (array_key_exists($objectId, $addEvents)) ? $addEvents[$objectId] : null;

        if ($event !== null) {
            $type      = $event['type'];
            $eventType = $event['eventType'];
            if (!in_array($eventType, $this->supportedEventTypes)) {
                return $this->accessDenied();
            }

            //ajax only for form fields
            if (!$type ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array(
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create'
                ), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            //fire the builder event
            $events            = $this->factory->getModel('campaign')->getEvents();
            $form              = $this->get('form.factory')->create('campaignevent', $event, array(
                'action'   => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
                'settings' => $events[$eventType][$type]
            ));
            $event['settings'] = $events[$eventType][$type];

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //save the properties to session
                        $addEvents   = $session->get('mautic.campaigns.add');
                        $formData    = $form->getData();
                        $event       = array_merge($event, $formData);

                        if (empty($event['name'])) {
                            //set it to the event default
                            $event['name'] = $this->get('translator')->trans($event['settings']['label']);
                        }
                        $addEvents[$objectId] = $event;
                        $session->set('mautic.campaigns.add', $addEvents);
                    } else {
                        $success = 0;
                    }
                }
            }

            $viewParams = array('type' => $type);
            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                $closeModal                 = false;
                $formView                   = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', 'MauticCampaignBundle:EventForm');
                $viewParams['form']         = $formView;
                $header                     = $event['settings']['label'];
                $viewParams['actionHeader'] = $this->get('translator')->trans($header);
            }

            $passthroughVars = array(
                'mauticContent' => 'campaignEvent',
                'success'       => $success,
                'route'         => false
            );

            if ($closeModal) {
                if ($success) {

                    //prevent undefined errors
                    $entity = new Event();
                    $blank  = $entity->convertToArray();
                    $event  = array_merge($blank, $event);

                    $template = (empty($event['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                        : $event['settings']['template'];

                    $passthroughVars['eventId']   = $objectId;
                    $passthroughVars['updateHtml'] = $this->renderView($template, array(
                        'event'  => $event,
                        'id'     => $objectId,
                        'update' => true
                    ));
                    $passthroughVars['eventType'] = $eventType;

                    $translator = $this->factory->getTranslator();

                    if ($event['triggerMode'] == 'interval') {
                        $passthroughVars['label'] = $translator->trans('mautic.campaign.connection.trigger.interval.label', array(
                            '%number%' => $event['triggerInterval'],
                            '%unit%'   => $translator->transChoice('mautic.campaign.event.intervalunit.' . $event['triggerIntervalUnit'], $event['triggerInterval'])
                        ));
                    } elseif ($event['triggerMode'] == 'date') {
                        /** @var \Mautic\CoreBundle\Templating\Helper\DateHelper $dh */
                        $dh    = $this->container->get('mautic.core.template.helper.date');
                        $passthroughVars['label'] = $translator->trans('mautic.campaign.connection.trigger.date.label', array(
                            '%full%' => $dh->toFull($event['triggerDate']),
                            '%time%' => $dh->toTime($event['triggerDate']),
                            '%date%' => $dh->toShort($event['triggerDate'])
                        ));
                    }
                }
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
        $session    = $this->factory->getSession();
        $saveEvents = $session->get('mautic.campaigns.add', array());
        $delete     = $session->get('mautic.campaigns.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $saveEvents)) ? $saveEvents[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $event !== null) {
            $events            = $this->factory->getModel('campaign')->getEvents();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.campaigns.remove', $delete);
            }

            $dataArray = array(
                'mauticContent' => 'campaignEvent',
                'success'       => 1,
                'route'         => false,
                'eventId'       => $objectId,
                'deleted'       => 1,
                'event'         => $event
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

        $event = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $event !== null) {
            $events            = $this->factory->getModel('campaign')->getEvents();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.campaigns.remove', $delete);
            }

            $template = (empty($event['settings']['template'])) ? 'MauticCampaignBundle:Event:generic.html.php'
                : $event['settings']['template'];

            //prevent undefined errors
            $entity = new Event();
            $blank  = $entity->convertToArray();
            $event  = array_merge($blank, $event);

            $dataArray = array(
                'mauticContent' => 'campaignEvent',
                'success'       => 1,
                'route'         => false,
                'eventId'       => $objectId,
                'eventHtml'     => $this->renderView($template, array(
                    'event'        => $event,
                    'id'           => $objectId
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