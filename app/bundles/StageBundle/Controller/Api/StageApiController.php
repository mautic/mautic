<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PointApiController.
 */
class StageApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('stage');
        $this->entityClass      = 'Mautic\StageBundle\Entity\Stage';
        $this->entityNameOne    = 'stage';
        $this->entityNameMulti  = 'stages';
        $this->serializerGroups = ['stageDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * Adds a contact to a list.
     *
     * @param int $id        Stage ID
     * @param int $contactId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addContactAction($id, $contactId)
    {
        $stage = $this->model->getEntity($id);

        if (null === $stage) {
            return $this->notFound();
        }

        $contact = $this->checkLeadAccess($contactId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Does the lead exist and the user has permission to edit
        $canEditContact = $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getOwner());
        $canViewStage   = $this->security->isGranted('stage:stages:view');

        if (!$canEditContact || !$canViewStage) {
            return $this->accessDenied();
        }

        $this->getModel('lead')->addToStages($contact, $stage);

        return $this->handleView($this->view(['success' => 1], Codes::HTTP_OK));
    }

    /**
     * Removes given contact from a list.
     *
     * @param int $id        Stage ID
     * @param int $contactId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeContactAction($id, $contactId)
    {
        $stage = $this->model->getEntity($id);

        if (null === $stage) {
            return $this->notFound();
        }

        $leadModel = $this->getModel('lead');
        $contact   = $leadModel->getEntity($contactId);

        // Does the lead exist and the user has permission to edit
        $canEditContact = $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getOwner());
        $canViewStage   = $this->security->isGranted('stage:stages:view');

        if ($contact == null) {
            return $this->notFound();
        } elseif (!$canEditContact || !$canViewStage) {
            return $this->accessDenied();
        }

        $leadModel->removeFromStages($contact, $stage);

        return $this->handleView($this->view(['success' => 1], Codes::HTTP_OK));
    }
}
