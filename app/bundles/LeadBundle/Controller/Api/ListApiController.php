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

use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class ListApiController.
 */
class ListApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('lead.list');
        $this->entityClass      = 'Mautic\LeadBundle\Entity\LeadList';
        $this->entityNameOne    = 'list';
        $this->entityNameMulti  = 'lists';
        $this->permissionBase   = 'lead:lists';
        $this->serializerGroups = ['leadListDetails', 'userList', 'publishDetails', 'ipAddress'];
    }

    /**
     * Obtains a list of smart lists for the user.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getListsAction()
    {
        $lists   = $this->getModel('lead.list')->getUserLists();
        $view    = $this->view($lists, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(['leadListList']);
        $view->setSerializationContext($context);

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

        $leadModel = $this->getModel('lead');
        $contact   = $leadModel->getEntity($leadId);

        // Does the contact exist and the user has permission to edit
        if ($contact == null) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        // Does the user have access to the list
        $lists = $this->model->getUserLists();
        if (!isset($lists[$id])) {
            return $this->accessDenied();
        }

        $leadModel->addToLists($leadId, $entity);

        $view = $this->view(['success' => 1], Codes::HTTP_OK);

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

        $leadModel = $this->getModel('lead');
        $contact   = $leadModel->getEntity($leadId);

        // Does the lead exist and the user has permission to edit
        if ($contact == null) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        // Does the user have access to the list
        $lists = $this->model->getUserLists();
        if (!isset($lists[$id])) {
            return $this->accessDenied();
        }

        $leadModel->removeFromLists($leadId, $entity);

        $view = $this->view(['success' => 1], Codes::HTTP_OK);

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
        if ($action == 'create' || $action == 'edit' || $action == 'view') {
            return $this->security->isGranted('lead:leads:viewown');
        } elseif ($action == 'delete') {
            return $this->factory->getSecurity()->hasEntityAccess(
                true, 'lead:lists:deleteother', $entity->getCreatedBy()
            );
        }

        return parent::checkEntityAccess($entity, $action);
    }
}
