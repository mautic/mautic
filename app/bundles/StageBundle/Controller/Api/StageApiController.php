<?php

namespace Mautic\StageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Stage>
 */
class StageApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * {@inheritdoc}
     */
    public function initialize(ControllerEvent $event)
    {
        $stageModel = $this->getModel('stage');

        if (!$stageModel instanceof StageModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model            = $stageModel;
        $this->entityClass      = Stage::class;
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

        if (!$this->security->isGranted('stage:stages:view')) {
            return $this->accessDenied();
        }

        $leadModel = $this->getModel('lead');
        \assert($leadModel instanceof LeadModel);
        $leadModel->addToStages($contact, $stage)->saveEntity($contact);

        return $this->handleView($this->view(['success' => 1], Response::HTTP_OK));
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

        $contact = $this->checkLeadAccess($contactId, 'edit');

        if ($contact instanceof Response) {
            return $contact;
        }

        if (!$this->security->isGranted('stage:stages:view')) {
            return $this->accessDenied();
        }

        $leadModel = $this->getModel('lead');
        \assert($leadModel instanceof LeadModel);
        $leadModel->removeFromStages($contact, $stage)->saveEntity($contact);

        return $this->handleView($this->view(['success' => 1], Response::HTTP_OK));
    }
}
