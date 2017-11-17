<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class CampaignApiController.
 */
class CampaignApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('campaign');
        $this->entityClass      = 'Mautic\CampaignBundle\Entity\Campaign';
        $this->entityNameOne    = 'campaign';
        $this->entityNameMulti  = 'campaigns';
        $this->serializerGroups = ['campaignDetails', 'campaignEventDetails', 'categoryList', 'publishDetails', 'leadListList', 'formList'];

        parent::initialize($event);
    }

    /**
     * Adds a lead to a campaign.
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $leadModel = $this->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead == null) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $this->model->addLead($entity, $leadId);

            $view = $this->view(['success' => 1], Codes::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Removes given lead from a campaign.
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $lead = $this->checkLeadAccess($leadId, 'edit');
            if ($lead instanceof Response) {
                return $lead;
            }

            $this->model->removeLead($entity, $leadId);

            $view = $this->view(['success' => 1], Codes::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead &$entity
     * @param                                $parameters
     * @param                                $form
     * @param string                         $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method = $this->request->getMethod();

        if ($method === 'POST' || $method === 'PUT') {
            if (empty($parameters['events'])) {
                $msg = $this->get('translator')->trans('mautic.campaign.form.events.notempty', [], 'validators');

                return $this->returnError($msg, Codes::HTTP_BAD_REQUEST);
            } elseif (empty($parameters['lists']) && empty($parameters['forms'])) {
                $msg = $this->get('translator')->trans('mautic.campaign.form.sources.notempty', [], 'validators');

                return $this->returnError($msg, Codes::HTTP_BAD_REQUEST);
            }
        }

        $deletedSources = ['lists' => [], 'forms' => []];
        $deletedEvents  = [];
        $currentSources = [
            'lists' => isset($parameters['lists']) ? $this->modifyCampaignEventArray($parameters['lists']) : [],
            'forms' => isset($parameters['forms']) ? $this->modifyCampaignEventArray($parameters['forms']) : [],
        ];

        // delete events and sources which does not exist in the PUT request
        if ($method === 'PUT') {
            $requestEventIds   = [];
            $requestSegmentIds = [];
            $requestFormIds    = [];

            foreach ($parameters['events'] as $key => $requestEvent) {
                if (!isset($requestEvent['id'])) {
                    return $this->returnError('$campaign[events]['.$key.']["id"] is missing', Codes::HTTP_BAD_REQUEST);
                }
                $requestEventIds[] = $requestEvent['id'];
            }

            foreach ($entity->getEvents() as $currentEvent) {
                if (!in_array($currentEvent->getId(), $requestEventIds)) {
                    $deletedEvents[] = $currentEvent->getId();
                }
            }

            if (isset($parameters['lists'])) {
                foreach ($parameters['lists'] as $requestSegment) {
                    if (!isset($requestSegment['id'])) {
                        return $this->returnError('$campaign[lists]['.$key.']["id"] is missing', Codes::HTTP_BAD_REQUEST);
                    }
                    $requestSegmentIds[] = $requestSegment['id'];
                }
            }

            foreach ($entity->getLists() as $currentSegment) {
                if (!in_array($currentSegment->getId(), $requestSegmentIds)) {
                    $deletedSources['lists'][$currentSegment->getId()] = 'ignore';
                }
            }

            if (isset($parameters['forms'])) {
                foreach ($parameters['forms'] as $requestForm) {
                    if (!isset($requestForm['id'])) {
                        return $this->returnError('$campaign[forms]['.$key.']["id"] is missing', Codes::HTTP_BAD_REQUEST);
                    }
                    $requestFormIds[] = $requestForm['id'];
                }
            }

            foreach ($entity->getForms() as $currentForm) {
                if (!in_array($currentForm->getId(), $requestFormIds)) {
                    $deletedSources['forms'][$currentForm->getId()] = 'ignore';
                }
            }
        }

        // Set lead sources
        $this->model->setLeadSources($entity, $currentSources, $deletedSources);

        // Build and set Event entities
        if (isset($parameters['events']) && isset($parameters['canvasSettings'])) {
            $this->model->setEvents($entity, $parameters['events'], $parameters['canvasSettings'], $deletedEvents);
        }

        // Persist to the database before building connection so that IDs are available
        $this->model->saveEntity($entity);

        // Update canvas settings with new event IDs then save
        if (isset($parameters['canvasSettings'])) {
            $this->model->setCanvasSettings($entity, $parameters['canvasSettings']);
        }

        if ($method === 'PUT' && !empty($deletedEvents)) {
            $this->getModel('campaign.event')->deleteEvents($entity->getEvents()->toArray(), $deletedEvents);
        }
    }

    /**
     * Change the array structure.
     *
     * @param array $events
     *
     * @return array
     */
    public function modifyCampaignEventArray($events)
    {
        $updatedEvents = [];

        if ($events && is_array($events)) {
            foreach ($events as $event) {
                if (!empty($event['id'])) {
                    $updatedEvents[$event['id']] = 'ignore';
                }
            }
        }

        return $updatedEvents;
    }

    /**
     * Obtains a list of campaign contacts.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactsAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'view')) {
            return $this->accessDenied();
        }

        $where = InputHelper::clean($this->request->query->get('where', []));
        $order = InputHelper::clean($this->request->query->get('order', []));
        $start = (int) $this->request->query->get('start', 0);
        $limit = (int) $this->request->query->get('limit', 100);

        $where[] = [
            'col'  => 'campaign_id',
            'expr' => 'eq',
            'val'  => $id,
        ];

        $where[] = [
            'col'  => 'manually_removed',
            'expr' => 'eq',
            'val'  => 0,
        ];

        return $this->forward(
            'MauticCoreBundle:Api\StatsApi:list',
            [
                'table'     => 'campaign_leads',
                'itemsName' => 'contacts',
                'order'     => $order,
                'where'     => $where,
                'start'     => $start,
                'limit'     => $limit,
            ]
        );
    }
}
