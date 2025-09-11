<?php

namespace Mautic\FormBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Model\SubmissionResultLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResultController extends CommonFormController
{
    public function __construct(FormFactoryInterface $formFactory, FormFieldHelper $fieldHelper, ManagerRegistry $doctrine, ModelFactory $modelFactory, UserHelper $userHelper, CoreParametersHelper $coreParametersHelper, EventDispatcherInterface $dispatcher, Translator $translator, FlashBag $flashBag, RequestStack $requestStack, CorePermissions $security)
    {
        $this->setStandardParameters(
            'form.submission', // model name
            'form:forms', // permission base
            'mautic_form', // route base
            'mautic.formresult', // session base
            'mautic.form.result', // lang string base
            '@MauticForm/Result', // template base
            'mautic_form', // activeLink
            'formresult' // mauticContent
        );

        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request, PageHelperFactoryInterface $pageHelperFacotry, int $objectId, int $page = 1)
    {
        /** @var FormModel $formModel */
        $formModel      = $this->getModel('form.form');
        $form           = $formModel->getEntity($objectId);
        $session        = $request->getSession();
        $formPage       = $session->get('mautic.form.page', 1);
        $returnUrl      = $this->generateUrl('mautic_form_index', ['page' => $formPage]);
        $viewOnlyFields = $formModel->getCustomComponents()['viewOnlyFields'];

        if (null === $form) {
            // redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $form->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        if ('POST' === $request->getMethod()) {
            $this->setListFilters($request->query->get('name'));
        }

        $pageHelper        = $pageHelperFacotry->make('mautic.formresult.'.$objectId, $page);

        // set limits
        $limit = $pageHelper->getLimit();
        $start = $pageHelper->getStart();

        // Set order direction to desc if not set
        if (!$session->get('mautic.formresult.'.$objectId.'.orderbydir', null)) {
            $session->set('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        }

        $orderBy    = $session->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.formresult.'.$objectId.'.filters', []);
        $model      = $this->getModel('form.submission');

        if ($request->query->has('result')) {
            // Force ID
            $filters['s.id'] = ['column' => 's.id', 'expr' => 'like', 'value' => (int) $request->query->get('result'), 'strict' => false];
            $session->set("mautic.formresult.$objectId.filters", $filters);
        }

        // get the results
        $entities = $model->getEntities(
            [
                'start'          => $start,
                'limit'          => $limit,
                'filter'         => ['force' => $filters],
                'orderBy'        => $orderBy,
                'orderByDir'     => $orderByDir,
                'form'           => $form,
                'withTotalCount' => true,
                'viewOnlyFields' => $viewOnlyFields,
                'simpleResults'  => true,
            ]
        );

        $count   = $entities['count'];
        $results = $entities['results'];
        unset($entities);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            $lastPage = $pageHelper->countPage($count);
            $pageHelper->rememberPage($lastPage);
            $returnUrl = $this->generateUrl('mautic_form_results', ['objectId' => $objectId, 'page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\ResultController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'formresult',
                    ],
                ]
            );
        }

        // set what page currently on so that we can return here if need be
        $pageHelper->rememberPage($page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'items'          => $results,
                    'filters'        => $filters,
                    'form'           => $form,
                    'viewOnlyFields' => $viewOnlyFields,
                    'page'           => $page,
                    'totalCount'     => $count,
                    'limit'          => $limit,
                    'tmpl'           => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                    'canDelete'      => $this->security->hasEntityAccess(
                        'form:forms:editown',
                        'form:forms:editother',
                        $form->getCreatedBy()
                    ),
                    'enableExportPermission'=> $this->security->isAdmin() || $this->security->isGranted('form:export:enable', 'MATCH_ONE'),
                ],
                'contentTemplate' => '@MauticForm/Result/list.html.twig',
                'passthroughVars' => [
                    'activeLink'    => 'mautic_form_index',
                    'mauticContent' => 'formresult',
                    'route'         => $this->generateUrl(
                        'mautic_form_results',
                        [
                            'objectId' => $objectId,
                            'page'     => $page,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * @return BinaryFileResponse
     */
    public function downloadFileAction(int $submissionId, string $field, FormUploader $formUploader)
    {
        /** @var SubmissionResultLoader $submissionResultLoader */
        $submissionResultLoader = $this->getModel('form.submission_result_loader');
        $submission             = $submissionResultLoader->getSubmissionWithResult($submissionId);

        if (!$submission) {
            throw $this->createNotFoundException();
        }

        $results     = $submission->getResults();
        $fieldEntity = $submission->getFieldByAlias($field);

        if (empty($results[$field]) || null === $fieldEntity) {
            throw $this->createNotFoundException();
        }

        if (empty($fieldEntity->getProperties()['public']) && !$this->security->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $submission->getForm()->getCreatedBy())
        ) {
            return $this->accessDenied();
        }

        $fileName = $results[$field];
        $file     = $formUploader->getCompleteFilePath($fieldEntity, $fileName);

        $fs = new Filesystem();
        if (!$fs->exists($file)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($file);
        $response::trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }

    public function downloadFileByFileNameAction(string $fieldId, string $fileName, FieldModel $fieldModel, FormUploader $formUploader): Response
    {
        $fieldEntity = $fieldModel->getEntity($fieldId);

        if (empty($fieldEntity->getProperties()['public']) && !$this->security->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $fieldEntity->getForm()->getCreatedBy())
        ) {
            return $this->accessDenied();
        }

        $file = $formUploader->getCompleteFilePath($fieldEntity, $fileName);

        $fs   = new Filesystem();
        if (!$fs->exists($file)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($file);
        $response::trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }

    /**
     * @param int    $objectId
     * @param string $format
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function exportAction(Request $request, $objectId, $format = 'csv')
    {
        $formModel = $this->getModel('form.form');
        $form      = $formModel->getEntity($objectId);
        $session   = $request->getSession();
        $formPage  = $session->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $formPage]);

        if (!$this->security->isAdmin() && !$this->security->isGranted('form:export:enable', 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        if (null === $form) {
            // redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'Mautic\FormBundle\Controller\FormController::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'form',
                    ],
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            );
        } elseif (!$this->security->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $form->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        $orderBy    = $session->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.formresult.'.$objectId.'.filters', []);

        $args = [
            'limit'      => false,
            'filter'     => ['force' => $filters],
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
            'form'       => $form,
        ];

        /** @var SubmissionModel $model */
        $model = $this->getModel('form.submission');

        return $model->exportResults($format, $form, $args);
    }

    /**
     * Delete a form result.
     *
     * @return array|Response
     */
    public function deleteAction(Request $request)
    {
        $formId   = $request->get('formId', 0);
        $objectId = $request->get('objectId', 0);
        $session  = $request->getSession();
        $page     = $session->get('mautic.formresult.'.$formId.'.page', 1);
        $flashes  = [];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('form.submission');
            \assert($model instanceof SubmissionModel);

            // Find the result
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess('form:forms:editown', 'form:forms:editother', $entity->getCreatedBy())) {
                return $this->accessDenied();
            } else {
                $id = $entity->getId();
                $model->deleteEntity($entity);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => '#'.$id,
                    ],
                ];
            }
        } // else don't do anything

        $viewParameters = [
            'objectId' => $formId,
            'page'     => $page,
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_form_results', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\FormBundle\Controller\ResultController::indexAction',
                'passthroughVars' => [
                    'mauticContent' => 'formresult',
                ],
                'flashes' => $flashes,
            ]
        );
    }

    public function markSpamAction(Request $request, Configurator $configurator): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $formId   = $request->get('formId', 0);
        $objectId = $request->get('objectId', 0);
        $session  = $request->getSession();
        $page     = $session->get('mautic.formresult.'.$formId.'.page', 1);
        $flashes  = [];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('form.submission');
            \assert($model instanceof SubmissionModel);

            /** @var Submission|null $submission */
            $submission = $model->getEntity($objectId);

            if (null === $submission) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess('form:forms:editown', 'form:forms:editother', $submission->getCreatedBy())) {
                return $this->accessDenied();
            } else {
                $domain = $this->getDomainFromSubmission($submission);

                if ($domain) {
                    $formatted     = '*@'.$domain;
                    $domains       = $this->coreParametersHelper->get('do_not_submit_emails', []);
                    $isNewDomain   = !in_array($formatted, $domains, true);

                    if ($isNewDomain) {
                        $domains[] = $formatted;
                        $configurator->mergeParameters(['do_not_submit_emails' => $domains]);
                        $configurator->write();
                    }

                    $this->enableDonotSubmitValidationOnUnconfiguredForms();

                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.form.result.markspam.success',
                        'msgVars' => ['%domain%' => $domain],
                    ];
                } else {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => 'mautic.form.result.markspam.error',
                    ];
                }
            }
        }

        $viewParameters = [
            'objectId' => $formId,
            'page'     => $page,
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_form_results', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\\FormBundle\\Controller\\ResultController::indexAction',
                'passthroughVars' => [
                    'mauticContent' => 'formresult',
                ],
                'flashes' => $flashes,
            ]
        );
    }

    public function batchMarkSpamAction(Request $request, Configurator $configurator): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $formId  = $this->getFormIdFromRequest();
        $session = $request->getSession();
        $page    = $session->get('mautic.formresult.'.$formId.'.page', 1);
        $flashes = [];

        if (Request::METHOD_POST === $request->getMethod()) {
            $model = $this->getModel('form.submission');
            \assert($model instanceof SubmissionModel);

            $ids           = json_decode($request->query->get('ids', '[]'), true);
            $domainsToSave = [];

            foreach ($ids as $id) {
                /** @var Submission|null $submission */
                $submission = $model->getEntity($id);
                if (null === $submission) {
                    continue;
                }
                if (!$this->security->hasEntityAccess('form:forms:editown', 'form:forms:editother', $submission->getCreatedBy())) {
                    $flashes[] = $this->accessDenied(true);
                    continue;
                }
                $domain = $this->getDomainFromSubmission($submission);
                if ($domain) {
                    $domainsToSave['*@'.$domain] = true;
                }
            }

            if ($domainsToSave) {
                $existing      = $this->coreParametersHelper->get('do_not_submit_emails', []);
                $hasNewDomains = false;

                foreach (array_keys($domainsToSave) as $d) {
                    if (!in_array($d, $existing, true)) {
                        $existing[]    = $d;
                        $hasNewDomains = true;
                    }
                }
                $configurator->mergeParameters(['do_not_submit_emails' => $existing]);
                $configurator->write();

                $this->enableDonotSubmitValidationOnUnconfiguredForms();

                $flashes[] = [
                    'type' => 'notice',
                    'msg'  => 'mautic.form.result.markspam.batch.success',
                ];
            } else {
                $flashes[] = [
                    'type' => 'notice',
                    'msg'  => 'mautic.form.result.markspam.batch.none',
                ];
            }
        }

        $viewParameters = [
            'objectId' => $formId,
            'page'     => $page,
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_form_results', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\\FormBundle\\Controller\\ResultController::indexAction',
                'passthroughVars' => [
                    'mauticContent' => 'formresult',
                ],
                'flashes' => $flashes,
            ]
        );
    }

    private function getDomainFromSubmission(Submission $submission): ?string
    {
        $results = $submission->getResults();
        $form    = $submission->getForm();
        foreach ($form->getFields() as $field) {
            if ('email' === $field->getType()) {
                $alias = $field->getAlias();
                if (!empty($results[$alias]) && str_contains($results[$alias], '@')) {
                    return strtolower(substr(strrchr($results[$alias], '@'), 1));
                }
            }
        }

        return null;
    }

    private function enableDonotSubmitValidationOnUnconfiguredForms(): void
    {
        /** @var FormModel $formModel */
        $formModel  = $this->getModel('form.form');
        $connection = $this->doctrine->getConnection();

        $fieldsToUpdate = $connection->executeQuery(
            'SELECT ff.id, ff.form_id, ff.validation 
             FROM form_fields ff 
             WHERE ff.type = ? 
             AND (ff.validation IS NULL OR ff.validation = ? OR ff.validation = ?)',
            ['email', '[]', '{}']
        )->fetchAllAssociative();

        if (empty($fieldsToUpdate)) {
            return;
        }

        $fieldIds = [];
        foreach ($fieldsToUpdate as $fieldData) {
            $validation = json_decode($fieldData['validation'] ?? '[]', true) ?: [];
            if (!array_key_exists('donotsubmit', $validation)) {
                $fieldIds[] = $fieldData['id'];
            }
        }

        if (empty($fieldIds)) {
            return;
        }

        $placeholders = str_repeat('?,', count($fieldIds) - 1).'?';
        $connection->executeStatement(
            "UPDATE form_fields 
             SET validation = JSON_SET(COALESCE(validation, '{}'), '$.donotsubmit', true) 
             WHERE id IN ($placeholders)",
            $fieldIds
        );

        $updatedFormIds = array_unique(array_column($fieldsToUpdate, 'form_id'));

        foreach ($updatedFormIds as $formId) {
            $form = $formModel->getEntity($formId);
            if ($form) {
                $form->setCachedHtml('');
                $formModel->saveEntity($form);
                $formModel->generateHtml($form);
            }
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return $this->batchDeleteStandard($request);
    }

    protected function getModelName(): string
    {
        return 'form.submission';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_form_results';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_form_results_action';
    }

    /**
     * Set the main form ID as the objectId.
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $formId = $this->getFormIdFromRequest($parameters);
        switch ($route) {
            case 'mautic_form_results_action':
                $parameters['formId'] = $formId;
                break;
            case 'mautic_form_results':
                $parameters['objectId'] = $formId;
                break;
        }

        return parent::generateUrl($route, $parameters, $referenceType);
    }

    public function getPostActionRedirectArguments(array $args, $action): array
    {
        switch ($action) {
            case 'batchDelete':
            case 'batchMarkSpam':
                $formId                             = $this->getFormIdFromRequest();
                $args['viewParameters']['objectId'] = $formId;
                break;
        }

        return $args;
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    protected function getFormIdFromRequest($parameters = [])
    {
        $request = $this->getCurrentRequest();
        if ($request->attributes->has('formId')) {
            $formId = $request->attributes->get('formId');
        } elseif ($request->request->has('formId')) {
            $formId = $request->request->get('formId');
        } else {
            $objectId = $parameters['objectId'] ?? 0;
            $formId   = $parameters['formId'] ?? $request->query->get('formId', $objectId);
        }

        return $formId;
    }
}
