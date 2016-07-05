<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CampaignBundle\Entity\Event;
use Symfony\Component\HttpFoundation\JsonResponse;

class EventController extends CommonFormController
{
    private $supportedEventTypes = array('decision', 'action', 'condition');

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
            $event      = $this->request->request->get('campaignevent');
            $type       = $event['type'];
            $eventType  = $event['eventType'];
            $campaignId = $event['campaignId'];

            $event['triggerDate'] = (!empty($event['triggerDate'])) ? $this->factory->getDate($event['triggerDate'])->getDateTime() : null;
        } else {
            $type       = $this->request->query->get('type');
            $eventType  = $this->request->query->get('eventType');
            $campaignId = $this->request->query->get('campaignId');
            $event      = array(
                'type'       => $type,
                'eventType'  => $eventType,
                'campaignId' => $campaignId
            );
        }

        //set the eventType key for events
        if (!in_array($eventType, $this->supportedEventTypes)) {
            return $this->modalAccessDenied();
        }

        //ajax only for form fields
        if (!$type ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->modalAccessDenied();
        }

        //fire the builder event
        $events            = $this->getModel('campaign')->getEvents();
        $form              = $this->get('form.factory')->create('campaignevent', $event, array(
            'action'   => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'new')),
            'settings' => $events[$eventType][$type]
        ));
        $event['settings'] = $events[$eventType][$type];

        $form->get('campaignId')->setData($campaignId);

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new' . hash('sha1', uniqid(mt_rand()));

                    //save the properties to session
                    $modifiedEvents = $session->get('mautic.campaign.' . $campaignId . '.events.modified');
                    $formData       = $form->getData();
                    $event          = array_merge($event, $formData);
                    $event['id']    = $event['tempId'] = $keyId;
                    if (empty($event['name'])) {
                        //set it to the event default
                        $event['name'] = $this->get('translator')->trans($event['settings']['label']);
                    }
                    $modifiedEvents[$keyId] = $event;
                    $session->set('mautic.campaign.' . $campaignId . '.events.modified', $modifiedEvents);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $type);
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal                = false;
            $formThemes                = array('MauticCampaignBundle:FormTheme\Event');
            if (isset($event['settings']['formTheme'])) {
                $formThemes[] = $event['settings']['formTheme'];
            }

            $viewParams['form']        = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', $formThemes);;
            $viewParams['eventHeader'] = $this->get('translator')->trans($event['settings']['label']);
            $viewParams['eventDescription'] = (!empty($event['settings']['description'])) ? $this->get('translator')->trans(
                $event['settings']['description']
            ) : '';
        }

        $viewParams['hideTriggerMode'] = isset($event['settings']['hideTriggerMode']) && $event['settings']['hideTriggerMode'];

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
                'event'      => $event,
                'id'         => $keyId,
                'campaignId' => $campaignId
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
                $dh                       = $this->factory->getHelper('template.date');
                $passthroughVars['label'] = $translator->trans('mautic.campaign.connection.trigger.date.label', array(
                    '%full%' => $dh->toFull($event['triggerDate']),
                    '%time%' => $dh->toTime($event['triggerDate']),
                    '%date%' => $dh->toShort($event['triggerDate'])
                ));
            }
        }

        if ($closeModal) {
            //just close the modal
            $passthroughVars['closeModal'] = 1;
            $response                      = new JsonResponse($passthroughVars);

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
        $session        = $this->factory->getSession();
        $method         = $this->request->getMethod();
        $campaignId     = ($method == "POST") ? $this->request->request->get('campaignevent[campaignId]', '', true) : $this->request->query->get('campaignId');
        $modifiedEvents = $session->get('mautic.campaign.' . $campaignId . '.events.modified', array());
        $success        = 0;
        $valid          = $cancelled = false;
        $event          = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ($event !== null) {
            $type      = $event['type'];
            $eventType = $event['eventType'];
            if (!in_array($eventType, $this->supportedEventTypes)) {
                return $this->modalAccessDenied();
            }

            //ajax only for form fields
            if (!$type ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array(
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create'
                ), 'MATCH_ONE')
            ) {
                return $this->modalAccessDenied();
            }

            //fire the builder event
            $events            = $this->getModel('campaign')->getEvents();
            $form              = $this->get('form.factory')->create('campaignevent', $event, array(
                'action'   => $this->generateUrl('mautic_campaignevent_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
                'settings' => $events[$eventType][$type]
            ));
            $event['settings'] = $events[$eventType][$type];

            $form->get('campaignId')->setData($campaignId);

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //save the properties to session
                        $modifiedEvents = $session->get('mautic.campaign.' . $campaignId . '.events.modified');
                        $formData       = $form->getData();
                        $event          = array_merge($event, $formData);

                        if (empty($event['name'])) {
                            //set it to the event default
                            $event['name'] = $this->get('translator')->trans($event['settings']['label']);
                        }
                        $modifiedEvents[$objectId] = $event;

                        $session->set('mautic.campaign.' . $campaignId . '.events.modified', $modifiedEvents);
                    } else {
                        $success = 0;
                    }
                }
            }

            $viewParams = array('type' => $type);
            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                $closeModal = false;
                $formThemes = array('MauticCampaignBundle:FormTheme\Event');
                if (isset($event['settings']['formTheme'])) {
                    $formThemes[] = $event['settings']['formTheme'];
                }
                $viewParams['form'] = $this->setFormTheme($form, 'MauticCampaignBundle:Campaign:index.html.php', $formThemes);;
                $viewParams['eventHeader'] = $this->get('translator')->trans($event['settings']['label']);
                $viewParams['eventDescription'] = (!empty($event['settings']['description'])) ? $this->get('translator')->trans(
                    $event['settings']['description']
                ) : '';
            }

            $viewParams['hideTriggerMode'] = isset($event['settings']['hideTriggerMode']) && $event['settings']['hideTriggerMode'];

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

                    $passthroughVars['eventId']    = $objectId;
                    $passthroughVars['updateHtml'] = $this->renderView($template, array(
                        'event'      => $event,
                        'id'         => $objectId,
                        'update'     => true,
                        'campaignId' => $campaignId
                    ));
                    $passthroughVars['eventType']  = $eventType;

                    $translator = $this->factory->getTranslator();
                    if ($event['triggerMode'] == 'interval') {
                        $passthroughVars['label'] = $translator->trans('mautic.campaign.connection.trigger.interval.label', array(
                            '%number%' => $event['triggerInterval'],
                            '%unit%'   => $translator->transChoice('mautic.campaign.event.intervalunit.' . $event['triggerIntervalUnit'], $event['triggerInterval'])
                        ));
                    } elseif ($event['triggerMode'] == 'date') {
                        /** @var \Mautic\CoreBundle\Templating\Helper\DateHelper $dh */
                        $dh                       = $this->factory->getHelper('template.date');
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

                return $response;
            } else {
                return $this->ajaxAction(array(
                    'contentTemplate' => 'MauticCampaignBundle:Event:form.html.php',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars
                ));
            }
        } else {
            return $this->modalAccessDenied();
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
        $campaignId     = $this->request->query->get('campaignId');
        $session        = $this->factory->getSession();
        $modifiedEvents = $session->get('mautic.campaign.' . $campaignId . '.events.modified', array());
        $deletedEvents  = $session->get('mautic.campaign.' . $campaignId . '.events.deleted', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $event !== null) {
            $events            = $this->getModel('campaign')->getEvents();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            // Add the field to the delete list
            if (!in_array($objectId, $deletedEvents)) {
                $deletedEvents[] = $objectId;
                $session->set('mautic.campaign.' . $campaignId . '.events.deleted', $deletedEvents);
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
        $campaignId     = $this->request->query->get('campaignId');
        $session        = $this->factory->getSession();
        $modifiedEvents = $session->get('mautic.campaign.' . $campaignId . '.events.modified', array());
        $deletedEvents  = $session->get('mautic.campaign.' . $campaignId . '.events.deleted', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'campaign:campaigns:edit',
                'campaign:campaigns:create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $event !== null) {
            $events            = $this->getModel('campaign')->getEvents();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            //add the field to the delete list
            if (in_array($objectId, $deletedEvents)) {
                $key = array_search($objectId, $deletedEvents);
                unset($deletedEvents[$key]);
                $session->set('mautic.campaign.' . $campaignId . '.events.deleted', $deletedEvents);
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
                    'event'      => $event,
                    'id'         => $objectId,
                    'campaignId' => $campaignId
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response = new JsonResponse($dataArray);

        return $response;
    }
}
