<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class EmailApiController.
 */
class EmailApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('email');
        $this->entityClass      = 'Mautic\EmailBundle\Entity\Email';
        $this->entityNameOne    = 'email';
        $this->entityNameMulti  = 'emails';
        $this->permissionBase   = 'email:emails';
        $this->serializerGroups = ['emailDetails', 'categoryList', 'publishDetails', 'assetList', 'formList', 'leadListList'];
    }

    /**
     * Obtains a list of emails.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->security->isGranted('email:emails:viewother')) {
            $this->listFilters[] =
                [
                    'column' => 'e.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->user->getId(),
                ];
        }

        //get parent level only
        $this->listFilters[] = [
            'column' => 'e.variantParent',
            'expr'   => 'isNull',
        ];

        return parent::getEntitiesAction();
    }

    /**
     * Sends the email to it's assigned lists.
     *
     * @param int $id Email ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'view')) {
                return $this->accessDenied();
            }

            $lists = $this->request->request->get('lists', null);
            $limit = $this->request->request->get('limit', null);

            list($count, $failed) = $this->model->sendEmailToLists($entity, $lists, $limit);

            $view = $this->view(
                [
                    'success'          => 1,
                    'sentCount'        => $count,
                    'failedRecipients' => $failed,
                ],
                Codes::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Sends the email to a specific lead.
     *
     * @param int $id     Email ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'view')) {
                return $this->accessDenied();
            }

            $leadModel = $this->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead == null) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $post   = $this->request->request->all();
            $tokens = (!empty($post['tokens'])) ? $post['tokens'] : [];

            $cleantokens = array_map(
                function ($v) {
                    return InputHelper::clean($v);
                },
                $tokens
            );

            $leadFields = array_merge(['id' => $leadId], $leadModel->flattenFields($lead->getFields()));

            if ($this->get('mautic.helper.mailer')->applyFrequencyRules($lead)) {
                $this->model->sendEmail(
                    $entity,
                    $leadFields,
                    [
                        'source' => ['api', 0],
                        'tokens' => $cleantokens,
                    ]
                );
            }

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
        $method          = $this->request->getMethod();
        $segmentModel    = $this->getModel('lead.list');
        $requestSegments = isset($parameters['lists']) ? $parameters['lists'] : [];
        $currentSegments = [];
        $deletedSegments = [];

        foreach ($entity->getLists() as $currentSegment) {
            $currentSegments[] = $currentSegment->getId();

            // delete events and sources which does not exist in the PUT request
            if ($method === 'PUT' && !in_array($currentSegment->getId(), $requestSegments)) {
                $event->removeList($currentSegment);
            }
        }

        // Add new segments
        foreach ($requestSegments as $requestSegment) {
            if (!in_array($requestSegment, $currentSegments)) {
                $segment = $segmentModel->getEntity($requestSegment);
                $event->addList($segment);
            }
        }
    }
}
