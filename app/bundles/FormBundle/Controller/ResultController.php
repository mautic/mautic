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
use Mautic\FormBundle\Model\FormModel;

/**
 * Class ResultController.
 */
class ResultController extends CommonFormController
{
    /**
     * @param int $objectId
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($objectId, $page)
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
            $this->setListFilters();
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
     * @param     $formId
     * @param int $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($formId, $objectId = 0)
    {
        $session = $this->get('session');
        $page    = $session->get('mautic.formresult.page', 1);
        $flashes = [];

        if ($this->request->getMethod() == 'POST') {
            $model = $this->getModel('form.submission');
            $ids   = json_decode($this->request->query->get('ids', ''));

            if (!empty($ids)) {
                $formModel = $this->getModel('form');
                $form      = $formModel->getEntity($formId);

                if ($form === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess('form:forms:editown', 'form:forms:editother', $form->getCreatedBy())) {
                    return $this->accessDenied();
                } else {
                    // Make sure IDs are part of this form
                    $deleteIds = $model->getRepository()->validateSubmissions($ids, $formId);

                    // Delete everything we are able to
                    if (!empty($deleteIds)) {
                        $entities = $model->deleteEntities($deleteIds);

                        $flashes[] = [
                            'type'    => 'notice',
                            'msg'     => 'mautic.form.notice.batch_results_deleted',
                            'msgVars' => [
                                '%count%'     => count($entities),
                                'pluralCount' => count($entities),
                            ],
                        ];
                    }
                }
            } else {
                // Find the result
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } else {
                    // Check to see if the user has form edit access
                    $form = $entity->getForm();

                    if (!$this->get('mautic.security')->hasEntityAccess('form:forms:editown', 'form:forms:editother', $form->getCreatedBy())) {
                        return $this->accessDenied();
                    }
                }

                $model->deleteEntity($entity);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => '#'.$entity->getId(),
                    ],
                ];
            }
        } //else don't do anything

        $viewParameters = [
            'objectId' => $form->getId(),
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
}
