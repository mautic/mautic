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

        if (!$this->security->isGranted('stage:stages:view')) {
            return $this->accessDenied();
        }

        $this->getModel('lead')->addToStages($contact, $stage)->saveEntity($contact);

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

        $contact = $this->checkLeadAccess($contactId, 'edit');

        if ($contact instanceof Response) {
            return $contact;
        }

        if (!$this->security->isGranted('stage:stages:view')) {
            return $this->accessDenied();
        }

        $this->getModel('lead')->removeFromStages($contact, $stage)->saveEntity($contact);

        return $this->handleView($this->view(['success' => 1], Codes::HTTP_OK));
    }
}
