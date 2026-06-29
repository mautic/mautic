<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanySegmentController extends AbstractStandardFormController
{
    public const SESSION_KEY            = 'company_segments';

    private const PERMISSION_EDIT_OTHER = ':editother';

    public function indexAction(CompanySegmentModel $model, Request $request, int $page = 1): Response
    {
        $repository = $model->getRepository();

        // set some permissions
        \assert(null !== $this->security);
        $permissions = $this->security->isGranted(
            [
                'lead:leads:viewown',
                'lead:leads:viewother',
                $this->getPermissionBase().':viewother',
                $this->getPermissionBase().self::PERMISSION_EDIT_OTHER,
                $this->getPermissionBase().':deleteother',
            ],
            'RETURN_ARRAY'
        );

        \assert(is_array($permissions));

        if (!(bool) ($permissions['lead:leads:viewother'] ?? false) && !(bool) ($permissions['lead:leads:viewown'] ?? false)) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $session = $request->getSession();
        $limit   = $session->get('mautic.'.$this->getSessionBase().'.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start   = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.'.$this->getSessionBase().'.filter', ''));
        $session->set('mautic.'.$this->getSessionBase().'.filter', $search);

        // do some default filtering
        $orderBy    = $session->get('mautic.'.$this->getSessionBase().'.orderby', $repository->getTableAlias().'.dateModified');
        $orderByDir = $session->get('mautic.'.$this->getSessionBase().'.orderbydir', $this->getDefaultOrderDirection());

        $filter = [
            'string' => $search,
        ];

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        if (!$permissions[$this->getPermissionBase().':viewother']) {
            $translator      = $this->translator;
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $filter['force'] = $mine;
        }

        /** @var \Doctrine\ORM\Tools\Pagination\Paginator<CompanySegment> $items */
        [$count, $items] = $this->getIndexItems($start, $limit, $filter, $orderBy, $orderByDir);

        $count = !is_numeric($count) ? 0 : (int) $count;
        $limit = !is_numeric($limit) ? 0 : (int) $limit;
        if ($limit <= 0) {
            $limit = 1;
        }

        if (0 !== $count && $count < ($start + 1)) {
            // the number of entities are now less than the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $pages    = (int) ceil($count / $limit);
                $lastPage = 0 !== $pages ? $pages : 1;
            }
            $session->set('mautic.'.$this->getSessionBase().'.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => self::class.'::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_segments_index',
                    'mauticContent' => $this->getJsLoadMethodPrefix(),
                ],
            ]);
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.'.$this->getSessionBase().'.page', $page);

        /** @var array<int, int> $companySegmentIds */
        $companySegmentIds = array_keys(iterator_to_array($items->getIterator()));
        $this->updateCountCompaniesCache($model, $companySegmentIds);
        $companyCounts     = $model->getSegmentCompanyCountFromCache($companySegmentIds);

        $parameters = [
            'items'           => $items,
            'companyCounts'   => $companyCounts,
            'page'            => $page,
            'limit'           => $limit,
            'permissions'     => $permissions,
            'security'        => $this->security,
            'tmpl'            => $tmpl,
            'currentUser'     => $this->user,
            'searchValue'     => $search,
            'translationBase' => $this->getTranslationBase(),
            'permissionBase'  => $this->getPermissionBase(),
            'tableAlias'      => $model->getRepository()->getTableAlias(),
        ];

        return $this->delegateView(
            $this->getViewArguments([
                'viewParameters'  => $parameters,
                'contentTemplate' => '@MauticLead/CompanySegment/index.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_company_segments_index',
                    'route'         => $this->generateUrl('mautic_company_segments_index', ['page' => $page]),
                    'mauticContent' => $this->getJsLoadMethodPrefix(),
                ],
            ],
                'index'
            )
        );
    }

    /**
     * @param array<int, int> $companySegmentIds
     */
    private function updateCountCompaniesCache(CompanySegmentModel $model, array $companySegmentIds): void
    {
        foreach ($companySegmentIds as $id) {
            if (!$model->hasSegmentCompanyCountInCache($id)) {
                $model->setSegmentCompanyCountInCache([$id]);
            }
        }
    }

    public function newAction(CompanySegmentModel $model, Request $request): Response
    {
        \assert(null !== $this->security);
        if (false === $this->security->isGranted($this->getPermissionBase().':viewown')) {
            return $this->accessDenied();
        }

        $companySegment = new CompanySegment();
        // set the page we came from
        $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);
        $action    = $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'new']);

        $form = $model->createForm($companySegment, $this->formFactory, $action);

        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!($cancelled = $this->isFormCancelled($form)) && $valid = $this->isFormValid($form)) {
                $companySegment->setDateModified(new \DateTime());
                $model->saveEntity($companySegment);

                $this->addFlashMessage('mautic.core.notice.created', [
                    '%name%'      => $companySegment->getName().' ('.$companySegment->getAlias().')',
                    '%menu_link%' => 'mautic_company_segments_index',
                    '%url%'       => $this->generateUrl('mautic_company_segments_action', [
                        'objectAction' => 'edit',
                        'objectId'     => $companySegment->getId(),
                    ]),
                ]);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => self::class.'::indexAction',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_company_segments_index',
                        'mauticContent' => $this->getJsLoadMethodPrefix(),
                    ],
                ]);
            }

            if ($valid) {
                $id = $companySegment->getId();
                \assert(null !== $id);

                return $this->editAction($model, $request, $id, true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'translationBase' => $this->getTranslationBase(),
                'permissionBase'  => $this->getPermissionBase(),
                'form'            => $form->createView(),
            ],
            'contentTemplate' => '@MauticLead/CompanySegment/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'route'         => $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'new']),
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ]);
    }

    public function editAction(CompanySegmentModel $model, Request $request, int $objectId, bool $ignorePost = false, bool $isNew = false): Response
    {
        \assert(null !== $this->security);

        $segment = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

        if (!$segment instanceof CompanySegment) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (null === $segment->getId()) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (!$this->security->hasEntityAccess(
            true, $this->getPermissionBase().self::PERMISSION_EDIT_OTHER, $segment->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        $postActionVars = $this->getPostActionVars($request, $objectId);

        if ($isNew) {
            $segment->setNew();
        }

        return $this->createSegmentModifyResponse(
            $model,
            $request,
            $segment,
            $postActionVars,
            $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
            $ignorePost
        );
    }

    /**
     * @return array<mixed>
     */
    private function getPostActionVars(Request $request, ?int $objectId = null): array
    {
        // set the return URL
        if ($objectId > 0) {
            $returnUrl       = $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'view', 'objectId'=> $objectId]);
            $viewParameters  = ['objectAction' => 'view', 'objectId'=> $objectId];
            $contentTemplate = self::class.'::viewAction';
        } else {
            // set the page we came from
            $page            = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
            $returnUrl       = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);
            $viewParameters  = ['page' => $page];
            $contentTemplate = self::class.'::indexAction';
        }

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ];
    }

    /**
     * @param array<mixed> $postActionVars
     */
    private function createSegmentModifyResponse(CompanySegmentModel $segmentModel, Request $request, CompanySegment $segment, array $postActionVars, string $action, bool $ignorePost): Response
    {
        if ($segmentModel->isLocked($segment)) {
            return $this->isLocked($postActionVars, $segment, CompanySegmentModel::class);
        }

        $form = $segmentModel->createForm($segment, $this->formFactory, $action);

        // Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $segmentModel->saveEntity($segment, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $segmentId = $segment->getId();
                    \assert(null !== $segmentId);

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $segment->getName().' ('.$segment->getAlias().')',
                        '%menu_link%' => 'mautic_company_segments_index',
                        '%url%'       => $this->generateUrl('mautic_company_segments_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segmentId,
                        ]),
                    ]);

                    if ($this->getFormButton($form, ['buttons', 'apply'])->isClicked()) {
                        $contentTemplate                     = '@MauticLead/CompanySegment/form.html.twig';
                        $postActionVars['contentTemplate']   = $contentTemplate;
                        $postActionVars['forwardController'] = false;
                        $postActionVars['returnUrl']         = $this->generateUrl('mautic_company_segments_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $segmentId,
                        ]);

                        $form = $segmentModel->createForm($segment, $this->formFactory, $postActionVars['returnUrl']);

                        $postActionVars['viewParameters'] = [
                            'objectAction' => 'edit',
                            'objectId'     => $segmentId,
                            'form'         => $form->createView(),
                        ];

                        return $this->postActionRedirect($postActionVars);
                    }

                    return $this->viewAction($segmentModel, $request, $segmentId);
                }
            } else {
                $segmentModel->unlockEntity($segment);
            }

            if ($cancelled) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            $segmentModel->lockEntity($segment);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'            => $form->createView(),
                'translationBase' => $this->getTranslationBase(),
                'permissionBase'  => $this->getPermissionBase(),
            ],
            'contentTemplate' => '@MauticLead/CompanySegment/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'route'         => $action,
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ]);
    }

    public function viewAction(CompanySegmentModel $model, Request $request, int $objectId): Response
    {
        \assert(null !== $this->security);
        $security = $this->security;

        $segment = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

        if (!$segment instanceof CompanySegment) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (null === $segment->getId()) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (!$this->security->hasEntityAccess(
            'lead:leads:viewown',
            $this->getPermissionBase().':viewother',
            $segment->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'view', 'objectId' => $objectId]),
            'viewParameters' => [
                'segment'      => $segment,
                'segmentCount' => current($model->getSegmentCompanyRepository()->getCompanyCount([$objectId])),
                'permissions'  => $security->isGranted([
                    'lead:leads:editown',
                    $this->getPermissionBase().':viewother',
                    $this->getPermissionBase().self::PERMISSION_EDIT_OTHER,
                    $this->getPermissionBase().':deleteother',
                ], 'RETURN_ARRAY'),
                'security'        => $security,
                'translationBase' => $this->getTranslationBase(),
                'permissionBase'  => $this->getPermissionBase(),
            ],
            'contentTemplate' => '@MauticLead/CompanySegment/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ]);
    }

    protected function getModelName(): string
    {
        return CompanySegmentModel::class;
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return CompanySegmentModel::PROPERTIES_FIELD;
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.company_segments';
    }

    /**
     * @param int|string|mixed|null $objectId
     */
    protected function getSessionBase($objectId = null): string
    {
        return self::SESSION_KEY;
    }

    public function deleteAction(CompanySegmentModel $model, Request $request, int $objectId): Response
    {
        \assert(null !== $this->security);

        $page      = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => [
                'page'            => $page,
                'translationBase' => $this->getTranslationBase(),
                'permissionBase'  => $this->getPermissionBase(),
            ],
            'contentTemplate' => self::class.'::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $segment = $model->getEntity($objectId);

            if (!$segment instanceof CompanySegment || null === $segment->getId()) {
                return $this->notFoundRedirect($page, $objectId);
            }

            if (!$this->security->hasEntityAccess(
                true, $this->getPermissionBase().':deleteother', $segment->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            }

            if ($model->isLocked($segment)) {
                return $this->isLocked($postActionVars, $segment, CompanySegmentModel::class);
            }

            $dependentsCompanySegments = $model->getSegmentsWithDependenciesOnSegment($objectId, 'name');
            if ([] !== $dependentsCompanySegments) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.company_segments.error.cannot.delete',
                    'msgVars' => [
                        '%segments%' => implode(', ', $dependentsCompanySegments),
                    ],
                ];
            } else {
                $dependentsContactSegments = $model->getSegmentsWithDependenciesOnSegment($objectId, 'name', true);
                if ([] !== $dependentsContactSegments) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company_segments.segment.error.cannot.delete',
                        'msgVars' => [
                            '%segments%' => implode(', ', $dependentsContactSegments),
                        ],
                    ];
                } else {
                    $model->deleteEntity($segment);

                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.core.notice.deleted',
                        'msgVars' => [
                            '%name%' => $segment->getName(),
                            '%id%'   => $objectId,
                        ],
                    ];
                }
            }
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    public function batchDeleteAction(CompanySegmentModel $model, Request $request): Response
    {
        \assert(null !== $this->security);
        $page      = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => [
                'page'            => $page,
                'translationBase' => $this->getTranslationBase(),
                'permissionBase'  => $this->getPermissionBase(),
            ],
            'contentTemplate' => self::class.'::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $json = $request->query->get('ids', '{}');
            \assert(is_string($json));
            $ids = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($ids)) {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid ids parameter.');
            }
            $ids = array_values(array_map('intval', array_filter(
                $ids,
                static fn ($v): bool => is_int($v) || (is_string($v) && ctype_digit($v))
            )));

            $canNotBeDeleted     = [];
            $deleteIds           = [];

            // Check each segment for dependencies individually
            foreach ($ids as $objectId) {
                $dependentsCompanySegments = $model->getSegmentsWithDependenciesOnSegment($objectId, 'name');
                $dependentsContactSegments = $model->getSegmentsWithDependenciesOnSegment($objectId, 'name', true);

                if ([] !== $dependentsCompanySegments) {
                    $canNotBeDeleted[$objectId] = $dependentsCompanySegments;
                    $flashes[]                  = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company_segments.error.cannot.delete',
                        'msgVars' => [
                            '%segments%' => implode(', ', $dependentsCompanySegments),
                        ],
                    ];
                    continue;
                }

                if ([] !== $dependentsContactSegments) {
                    $canNotBeDeleted[$objectId] = $dependentsContactSegments;
                    $flashes[]                  = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company_segments.segment.error.cannot.delete',
                        'msgVars' => [
                            '%segments%' => implode(', ', $dependentsContactSegments),
                        ],
                    ];
                    continue;
                }
            }

            $toBeDeleted = array_diff($ids, array_keys($canNotBeDeleted));

            foreach ($toBeDeleted as $objectId) {
                $segment = $model->getEntity($objectId);

                if (!$segment instanceof CompanySegment || null === $segment->getId()) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.company_segments.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    true, $this->getPermissionBase().':deleteother', $segment->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($segment)) {
                    $flashes[] = $this->isLocked($postActionVars, $segment, CompanySegmentModel::class, true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            if (0 !== count($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.company_segments.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    public function cloneAction(CompanySegmentModel $model, Request $request, int $objectId, bool $ignorePost = false): Response
    {
        \assert(null !== $this->security);

        $segment = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.'.$this->getSessionBase().'.page', 1);

        if (!$segment instanceof CompanySegment || null === $segment->getId()) {
            return $this->notFoundRedirect($page, $objectId);
        }

        if (!$this->security->hasEntityAccess(
            true, $this->getPermissionBase().self::PERMISSION_EDIT_OTHER, $segment->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        $postActionVars = $this->getPostActionVars($request, $objectId);

        return $this->createSegmentModifyResponse(
            $model,
            $request,
            clone $segment,
            $postActionVars,
            $this->generateUrl('mautic_company_segments_action', ['objectAction' => 'clone', 'objectId' => $objectId]),
            $ignorePost
        );
    }

    private function notFoundRedirect(int $page, int $objectId): Response
    {
        $returnUrl = $this->generateUrl('mautic_company_segments_index', ['page' => $page]);

        return $this->postActionRedirect([
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => self::class.'::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_company_segments_index',
                'mauticContent' => $this->getJsLoadMethodPrefix(),
            ],
            'flashes' => [
                [
                    'type'    => 'error',
                    'msg'     => 'mautic.company_segments.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ],
            ],
        ]);
    }
}
