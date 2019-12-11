<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ListApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('lead.list');
        $this->entityClass      = LeadList::class;
        $this->entityNameOne    = 'list';
        $this->entityNameMulti  = 'lists';
        $this->serializerGroups = ['leadListDetails', 'userList', 'publishDetails', 'ipAddress'];

        parent::initialize($event);
    }

    /**
     * Obtains a list of smart lists for the user.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getListsAction()
    {
        $lists   = $this->getModel('lead.list')->getUserLists();
        $view    = $this->view($lists, Response::HTTP_OK);
        $context = $view->getContext()->setGroups(['leadListList']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Adds a lead to a list.
     *
     * @param int $id     List ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        $contact = $this->checkLeadAccess($leadId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Does the user have access to the list
        $lists = $this->model->getUserLists();
        if (!isset($lists[$id])) {
            return $this->accessDenied();
        }

        $this->getModel('lead')->addToLists($leadId, $entity);

        $view = $this->view(['success' => 1], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Adds a leads to a list.
     *
     * @param int $id segement ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addLeadsAction($id)
    {
        $contactIds = $this->request->request->get('ids');
        if (null === $contactIds) {
            return $this->returnError('mautic.core.error.badrequest', Response::HTTP_BAD_REQUEST);
        }

        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        // Does the user have access to the list
        $lists = $this->model->getUserLists();
        if (!isset($lists[$id])) {
            return $this->accessDenied();
        }

        $responseDetail = [];
        foreach ($contactIds as $contactId) {
            $contact = $this->checkLeadAccess($contactId, 'edit');
            if ($contact instanceof Response) {
                $responseDetail[$contactId] = ['success' => false];
            } else {
                /* @var \Mautic\LeadBundle\Entity\Lead $contact */
                $this->getModel('lead')->addToLists($contact, $entity);
                $responseDetail[$contact->getId()] = ['success' => true];
            }
        }

        $view = $this->view(['success' => 1, 'details' => $responseDetail], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Removes given contact from a list.
     *
     * @param int $id     List ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        $contact = $this->checkLeadAccess($leadId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Does the user have access to the list
        $lists = $this->model->getUserLists();
        if (!isset($lists[$id])) {
            return $this->accessDenied();
        }

        $this->getModel('lead')->removeFromLists($leadId, $entity);

        $view = $this->view(['success' => 1], Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Checks if user has permission to access retrieved entity.
     *
     * @param mixed  $entity
     * @param string $action view|create|edit|publish|delete
     *
     * @return bool
     */
    protected function checkEntityAccess($entity, $action = 'view')
    {
        if ('create' == $action || 'edit' == $action || 'view' == $action) {
            return $this->security->isGranted('lead:leads:viewown');
        } elseif ('delete' == $action) {
            return $this->factory->getSecurity()->hasEntityAccess(
                true, 'lead:lists:deleteother', $entity->getCreatedBy()
            );
        }

        return parent::checkEntityAccess($entity, $action);
    }
}
