<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionResultLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ResultController.
 */
class ResultController extends CommonFormController
{
    public function __construct()
    {
        $this->setStandardParameters(
            'form.submission', // model name
            'form:forms', // permission base
            'mautic_form', // route base
            'mautic.formresult', // session base
            'mautic.form.result', // lang string base
            'MauticFormBundle:Result', // template base
            'mautic_form', // activeLink
            'formresult' // mauticContent
        );
    }

    /**
     * @param int $objectId
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($objectId, $page = 1)
    {
        /** @var FormModel $formModel */
        $formModel      = $this->getModel('form.form');
        $form           = $formModel->getEntity($objectId);
        $session        = $this->get('session');
        $formPage       = $session->get('mautic.form.page', 1);
        $returnUrl      = $this->generateUrl('mautic_form_index', ['page' => $formPage]);
        $viewOnlyFields = $formModel->getCustomComponents()['viewOnlyFields'];

        if ($form === null) {
            //redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'MauticFormBundle:Form:index',
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $form->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters($this->request->query->get('name'));
        }

        //set limits
        $limit = $session->get('mautic.formresult.'.$objectId.'.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));

        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // Set order direction to desc if not set
        if (!$session->get('mautic.formresult.'.$objectId.'.orderbydir', null)) {
            $session->set('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        }

        $orderBy    = $session->get('mautic.formresult.'.$objectId.'.orderby', 's.date_submitted');
        $orderByDir = $session->get('mautic.formresult.'.$objectId.'.orderbydir', 'DESC');
        $filters    = $session->get('mautic.formresult.'.$objectId.'.filters', []);

        $model = $this->getModel('form.submission');

        if ($this->request->query->has('result')) {
            // Force ID
            $filters['s.id'] = ['column' => 's.id', 'expr' => 'like', 'value' => (int) $this->request->query->get('result'), 'strict' => false];
            $session->set("mautic.formresult.$objectId.filters", $filters);
        }

        //get the results
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
            ]
        );

        $count   = $entities['count'];
        $results = $entities['results'];
        unset($entities);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $session->set('mautic.formresult.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_form_results', ['objectId' => $objectId, 'page' => $lastPage]);

            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $lastPage],
                    'contentTemplate' => 'MauticFormBundle:Result:index',
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_form_index',
                        'mauticContent' => 'formresult',
                    ],
                ]
            );
        }

        //set what page currently on so that we can return here if need be
        $session->set('mautic.formresult.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

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
                    'tmpl'           => $tmpl,
                    'canDelete'      => $this->get('mautic.security')->hasEntityAccess(
                        'form:forms:editown',
                        'form:forms:editother',
                        $form->getCreatedBy()
                    ),
                ],
                'contentTemplate' => 'MauticFormBundle:Result:list.html.php',
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
     * @param int    $submissionId
     * @param string $field
     *
     * @return BinaryFileResponse
     */
    public function downloadFileAction($submissionId, $field)
    {
        /** @var SubmissionResultLoader $submissionResultLoader */
        $submissionResultLoader = $this->getModel('form.submission_result_loader');
        $submission             = $submissionResultLoader->getSubmissionWithResult($submissionId);

        if (!$submission) {
            throw $this->createNotFoundException();
        }

        if (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $submission->getForm()->getCreatedBy())
        ) {
            return $this->accessDenied();
        }

        $results = $submission->getResults();

        if (empty($results[$field])) {
            throw $this->createNotFoundException();
        }

        /** @var FormUploader $formUploader */
        $formUploader = $this->get('mautic.form.helper.form_uploader');

        $fileName = $results[$field];
        $file     = $formUploader->getCompleteFilePath($fileName);

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

    /**
     * @param int    $objectId
     * @param string $format
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Exception
     */
    public function exportAction($objectId, $format = 'csv')
    {
        $formModel = $this->getModel('form.form');
        $form      = $formModel->getEntity($objectId);
        $session   = $this->get('session');
        $formPage  = $session->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', ['page' => $formPage]);

        if ($form === null) {
            //redirect back to form list
            return $this->postActionRedirect(
                [
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $formPage],
                    'contentTemplate' => 'MauticFormBundle:Form:index',
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
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
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

        /** @var \Mautic\FormBundle\Model\SubmissionModel $model */
        $model = $this->getModel('form.submission');

        return $model->exportResults($format, $form, $args);
    }

    /**
     * Delete a form result.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction()
    {
        $formId   = $this->request->get('formId', 0);
        $objectId = $this->request->get('objectId', 0);
        $session  = $this->get('session');
        $page     = $session->get('mautic.formresult.page', 1);
        $flashes  = [];

        if ($this->request->getMethod() == 'POST') {
            $model = $this->getModel('form.submission');

                // Find the result
                $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess('form:forms:editown', 'form:forms:editother', $entity->getCreatedBy())) {
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
        } //else don't do anything

        $viewParameters = [
            'objectId' => $formId,
            'page'     => $page,
        ];

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_form_results', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'MauticFormBundle:Result:index',
                'passthroughVars' => [
                    'mauticContent' => 'formresult',
                ],
                'flashes' => $flashes,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return $this->batchDeleteStandard();
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'form.submission';
    }

    /**
     * @return string
     */
    protected function getIndexRoute()
    {
        return 'mautic_form_results';
    }

    /**
     * @return string
     */
    protected function getActionRoute()
    {
        return 'mautic_form_results_action';
    }

    /**
     * Set the main form ID as the objectId.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $referenceType
     */
    public function generateUrl($route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
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

    /**
     * @param array $args
     * @param       $action
     */
    public function getPostActionRedirectArguments(array $args, $action)
    {
        switch ($action) {
            case 'batchDelete':
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
        if ($this->request->attributes->has('formId')) {
            $formId = $this->request->attributes->get('formId');
        } elseif ($this->request->request->has('formId')) {
            $formId = $this->request->request->get('formId');
        } else {
            $objectId = isset($parameters['objectId']) ? $parameters['objectId'] : 0;
            $formId   = (isset($parameters['formId'])) ? $parameters['formId'] : $this->request->query->get('formId', $objectId);
        }

        return $formId;
    }
}
