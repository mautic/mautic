<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class SubmissionApiController.
 */
class SubmissionApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('form.submission');
        $this->entityClass      = Submission::class;
        $this->entityNameOne    = 'submission';
        $this->entityNameMulti  = 'submissions';
        $this->permissionBase   = 'forms:form';
        $this->serializerGroups = ['submissionDetails', 'formList', 'ipAddressList', 'leadBasicList', 'pageList'];

        parent::initialize($event);
    }

    /**
     * Obtains a list of entities as defined by the API URL.
     *
     * @param int $formId
     *
     * @return Response
     */
    public function getEntitiesAction($formId = null)
    {
        $form = $this->getFormOrResponseWithError($formId);

        if ($form instanceof Response) {
            return $form;
        }

        $this->extraGetEntitiesArguments = array_merge(
            $this->extraGetEntitiesArguments,
            [
                'form'            => $form,
                'flatten_results' => true,
                'return_entities' => true,
            ]
        );

        return parent::getEntitiesAction();
    }

    /**
     * Obtains a list of entities for specific form and contact.
     *
     * @param int $formId
     * @param int $contactId
     *
     * @return Response
     */
    public function getEntitiesForContactAction($formId, $contactId)
    {
        $filter = [
            'filter' => [
                'where' => [
                    [
                        'col'  => 's.lead_id',
                        'expr' => 'eq',
                        'val'  => (int) $contactId,
                    ],
                ],
            ],
        ];

        $this->extraGetEntitiesArguments = array_merge($this->extraGetEntitiesArguments, $filter);

        return $this->getEntitiesAction($formId);
    }

    /**
     * Obtains a specific entity as defined by the API URL.
     *
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function getEntityAction($formId = null, $submissionId = null)
    {
        $form = $this->getFormOrResponseWithError($formId);

        if ($form instanceof Response) {
            return $form;
        }

        return parent::getEntityAction($submissionId);
    }

    /**
     * Tries to fetch the form and returns Response if
     * - Form not found
     * - User doesn't have permission to view it.
     *
     * Returns Form on success
     *
     * @param int $formId
     *
     * @return Response|Form
     */
    protected function getFormOrResponseWithError($formId)
    {
        $formModel = $this->getModel('form');
        $form      = $formModel->getEntity($formId);

        if (!$form) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($form, 'view')) {
            return $this->accessDenied();
        }

        return $form;
    }
}
