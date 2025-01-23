<?php

namespace Mautic\AssetBundle\Controller;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends FormController
{
    /**
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, CoreParametersHelper $parametersHelper, AssetModel $assetModel, int $page = 1)
    {
        // set some permissions
        $permissions = $this->security->isGranted([
            'asset:assets:viewown',
            'asset:assets:viewother',
            'asset:assets:create',
            'asset:assets:editown',
            'asset:assets:editother',
            'asset:assets:deleteown',
            'asset:assets:deleteother',
            'asset:assets:publishown',
            'asset:assets:publishother',
        ], 'RETURN_ARRAY');

        if (!$permissions['asset:assets:viewown'] && !$permissions['asset:assets:viewother']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $limit = $request->getSession()->get('mautic.asset.limit', $parametersHelper->get('default_assetlimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $request->getSession()->get('mautic.asset.filter', ''));
        $request->getSession()->set('mautic.asset.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        if (!$permissions['asset:assets:viewother']) {
            $filter['force'][] =
                ['column' => 'a.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        $orderBy    = $request->getSession()->get('mautic.asset.orderby', 'a.dateModified');
        $orderByDir = $request->getSession()->get('mautic.asset.orderbydir', $this->getDefaultOrderDirection());

        $assets = $assetModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($assets);
        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current asset so redirect to the last asset
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $request->getSession()->set('mautic.asset.asset', $lastPage);
            $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['asset' => $lastPage],
                'contentTemplate' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_asset_index',
                    'mauticContent' => 'asset',
                ],
            ]);
        }

        // set what asset currently on so that we can return here after form submission/cancellation
        $request->getSession()->set('mautic.asset.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        // retrieve a list of categories
        $categories = $assetModel->getLookupResults('category', '', 0);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $assets,
                'categories'  => $categories,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $assetModel,
                'tmpl'        => $tmpl,
                'page'        => $page,
                'security'    => $this->security,
            ],
            'contentTemplate' => '@MauticAsset/Asset/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'asset',
                'route'         => $this->generateUrl('mautic_asset_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function viewAction(Request $request, AssetModel $model, $objectId)
    {
        $activeAsset = $model->getEntity($objectId);

        // set the asset we came from
        $page = $request->getSession()->get('mautic.asset.page', 1);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'details') : 'details';

        // Init the date range filter form
        $dateRangeValues = $request->get('daterange', []);
        $action          = $this->generateUrl('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->formFactory->create(DateRangeType::class, $dateRangeValues, ['action' => $action]);

        if (null === $activeAsset) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_asset_index',
                    'mauticContent' => 'asset',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.asset.asset.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$this->security->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $activeAsset->getCreatedBy())) {
            return $this->accessDenied();
        }

        // Audit Log
        $auditLogModel = $this->getModel('core.auditlog');
        \assert($auditLogModel instanceof AuditLogModel);
        $logs          = $auditLogModel->getLogForObject('asset', $activeAsset->getId(), $activeAsset->getDateAdded());

        return $this->delegateView([
            'returnUrl'      => $action,
            'viewParameters' => [
                'activeAsset' => $activeAsset,
                'tmpl'        => $tmpl,
                'permissions' => $this->security->isGranted([
                    'asset:assets:viewown',
                    'asset:assets:viewother',
                    'asset:assets:create',
                    'asset:assets:editown',
                    'asset:assets:editother',
                    'asset:assets:deleteown',
                    'asset:assets:deleteother',
                    'asset:assets:publishown',
                    'asset:assets:publishother',
                ], 'RETURN_ARRAY'),
                'stats' => [
                    'downloads' => [
                        'total'     => $activeAsset->getDownloadCount(),
                        'unique'    => $activeAsset->getUniqueDownloadCount(),
                        'timeStats' => $model->getDownloadsLineChartData(
                            null,
                            new \DateTime($dateRangeForm->get('date_from')->getData()),
                            new \DateTime($dateRangeForm->get('date_to')->getData()),
                            null,
                            ['asset_id' => $activeAsset->getId()]
                        ),
                    ],
                ],
                'security'         => $this->security,
                'assetDownloadUrl' => $model->generateUrl($activeAsset, true),
                'logs'             => $logs,
                'dateRangeForm'    => $dateRangeForm->createView(),
            ],
            'contentTemplate' => '@MauticAsset/Asset/'.$tmpl.'.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'asset',
            ],
        ]);
    }

    /**
     * Show a preview of the file.
     *
     * @param int $objectId
     *
     * @return JsonResponse|Response
     */
    public function previewAction(Request $request, AssetModel $model, $objectId)
    {
        $activeAsset = $model->getEntity($objectId);

        if (null === $activeAsset || !$this->security->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $activeAsset->getCreatedBy())) {
            return $this->modalAccessDenied();
        }

        $download = $request->query->get('download', 0);

        // Display the file directly in the browser just for selected extensions
        $defaultStream = in_array($activeAsset->getExtension(), $this->coreParametersHelper->get('streamed_extensions')) ? '1' : null;
        $stream        = $request->query->get('stream', $defaultStream);

        if ('1' === $download || '1' === $stream) {
            try {
                // set the uploadDir
                $activeAsset->setUploadDir($this->coreParametersHelper->get('upload_dir'));
                $contents = $activeAsset->getFileContents();
            } catch (\Exception) {
                return $this->notFound();
            }

            $response = new Response();
            $response->headers->set('Content-Type', $activeAsset->getFileMimeType());
            if ('1' === $download) {
                $response->headers->set('Content-Disposition', 'attachment;filename="'.$activeAsset->getOriginalFileName());
            }
            $response->setContent($contents);

            return $response;
        }

        return $this->delegateView([
            'viewParameters' => [
                'activeAsset'      => $activeAsset,
                'assetDownloadUrl' => $model->generateUrl($activeAsset),
            ],
            'contentTemplate' => '@MauticAsset/Asset/preview.html.twig',
            'passthroughVars' => [
                'route' => false,
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, CoreParametersHelper $parametersHelper, UploaderHelper $uploaderHelper, IntegrationHelper $integrationHelper, AssetModel $model, $entity = null)
    {
        if (null == $entity) {
            $entity = $model->getEntity();
        }

        $entity->setMaxSize(FileHelper::convertMegabytesToBytes($this->coreParametersHelper->get('max_size')));

        $method  = $request->getMethod();
        $session = $request->getSession();

        if (!$this->security->isGranted('asset:assets:create')) {
            return $this->accessDenied();
        }

        $maxSize    = $model->getMaxUploadSize();
        $extensions = '.'.implode(', .', $this->coreParametersHelper->get('allowed_extensions'));

        $maxSizeError = $this->translator->trans('mautic.asset.asset.error.file.size', [
            '%fileSize%' => '{{filesize}}',
            '%maxSize%'  => '{{maxFilesize}}',
        ], 'validators');

        $extensionError = $this->translator->trans('mautic.asset.asset.error.file.extension.js', [
            '%extensions%' => $extensions,
        ], 'validators');

        // Create temporary asset ID
        $asset  = $request->request->all()['asset'] ?? [];
        $tempId = 'POST' === $method ? ($asset['tempId'] ?? '') : uniqid('tmp_');
        $entity->setTempId($tempId);

        // Set the page we came from
        $page   = $session->get('mautic.asset.page', 1);
        $action = $this->generateUrl('mautic_asset_action', ['objectAction' => 'new']);

        $uploadEndpoint = $uploaderHelper->endpoint('asset');

        // create the form
        $form = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if ('POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $entity->setUploadDir($parametersHelper->get('upload_dir'));
                    $entity->preUpload();
                    $entity->upload();
                    $entity->setDateModified(new \DateTime());
                    // form is valid so process the data
                    $model->saveEntity($entity);

                    // remove the asset from request
                    $request->files->remove('asset');

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_asset_index',
                        '%url%'       => $this->generateUrl('mautic_asset_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if (!$this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        // return edit view so that all the session stuff is loaded
                        return $this->editAction($request, $uploaderHelper, $integrationHelper, $model, $entity->getId(), true);
                    }

                    $viewParameters = [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(),
                    ];
                    $returnUrl = $this->generateUrl('mautic_asset_action', $viewParameters);
                    $template  = 'Mautic\AssetBundle\Controller\AssetController::viewAction';
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_asset_index', $viewParameters);
                $template       = 'Mautic\AssetBundle\Controller\AssetController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_asset_index',
                        'mauticContent' => 'asset',
                    ],
                ]);
            }
        }

        // Check for integrations to cloud providers
        $integrations = $integrationHelper->getIntegrationObjects(null, ['cloud_storage']);

        return $this->delegateView([
            'viewParameters' => [
                'form'             => $form->createView(),
                'activeAsset'      => $entity,
                'assetDownloadUrl' => $model->generateUrl($entity),
                'integrations'     => $integrations,
                'startOnLocal'     => $entity->isLocal(),
                'uploadEndpoint'   => $uploadEndpoint,
                'maxSize'          => $maxSize,
                'maxSizeError'     => $maxSizeError,
                'extensions'       => $extensions,
                'extensionError'   => $extensionError,
            ],
            'contentTemplate' => '@MauticAsset/Asset/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'asset',
                'route'         => $this->generateUrl('mautic_asset_action', [
                    'objectAction' => 'new',
                ]),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, UploaderHelper $uploaderHelper, IntegrationHelper $integrationHelper, AssetModel $model, $objectId, $ignorePost = false)
    {
        $entity = $model->getEntity($objectId);

        if (!$this->security->hasEntityAccess('asset:assets:editown', 'asset:assets:editother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        $entity->setMaxSize(FileHelper::convertMegabytesToBytes($this->coreParametersHelper->get('max_size')));

        $session    = $request->getSession();
        $page       = $session->get('mautic.asset.page', 1);
        $method     = $request->getMethod();
        $maxSize    = $model->getMaxUploadSize();
        $extensions = '.'.implode(', .', $this->coreParametersHelper->get('allowed_extensions'));

        $maxSizeError = $this->translator->trans('mautic.asset.asset.error.file.size', [
            '%fileSize%' => '{{filesize}}',
            '%maxSize%'  => '{{maxFilesize}}',
        ], 'validators');

        $extensionError = $this->translator->trans('mautic.asset.asset.error.file.extension.js', [
            '%extensions%' => $extensions,
        ], 'validators');

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);

        $uploadEndpoint = $uploaderHelper->endpoint('asset');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_asset_index',
                'mauticContent' => 'asset',
            ],
        ];

        // not found
        if (null === $entity) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.asset.asset.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$this->security->hasEntityAccess(
            'asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'asset.asset');
        }

        // Create temporary asset ID
        $asset  = $request->request->all()['asset'] ?? [];
        $tempId = 'POST' === $method ? ($asset['tempId'] ?? '') : uniqid('tmp_');
        $entity->setTempId($tempId);

        // Create the form
        $action = $this->generateUrl('mautic_asset_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' == $method) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $entity->setUploadDir($this->coreParametersHelper->get('upload_dir'));
                    $entity->preUpload();
                    $entity->upload();

                    // form is valid so process the data
                    $model->saveEntity($entity, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    // remove the asset from request
                    $request->files->remove('asset');

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_asset_index',
                        '%url%'       => $this->generateUrl('mautic_asset_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    $returnUrl = $this->generateUrl('mautic_asset_action', [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(),
                    ]);
                    $viewParams = ['objectId' => $entity->getId()];
                    $template   = 'Mautic\AssetBundle\Controller\AssetController::viewAction';
                }
            } else {
                // clear any modified content
                $session->remove('mautic.asestbuilder.'.$objectId.'.content');
                // unlock the entity
                $model->unlockEntity($entity);

                $returnUrl  = $this->generateUrl('mautic_asset_index', ['page' => $page]);
                $viewParams = ['page' => $page];
                $template   = 'Mautic\AssetBundle\Controller\AssetController::indexAction';
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => $template,
                    ])
                );
            }
        } else {
            // lock the entity
            $model->lockEntity($entity);
        }

        // Check for integrations to cloud providers
        $integrations = $integrationHelper->getIntegrationObjects(null, ['cloud_storage']);

        return $this->delegateView([
            'viewParameters' => [
                'form'             => $form->createView(),
                'activeAsset'      => $entity,
                'assetDownloadUrl' => $model->generateUrl($entity),
                'integrations'     => $integrations,
                'startOnLocal'     => $entity->isLocal(),
                'uploadEndpoint'   => $uploadEndpoint,
                'maxSize'          => $maxSize,
                'maxSizeError'     => $maxSizeError,
                'extensions'       => $extensions,
                'extensionError'   => $extensionError,
            ],
            'contentTemplate' => '@MauticAsset/Asset/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'asset',
                'route'         => $this->generateUrl('mautic_asset_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, CoreParametersHelper $parametersHelper, UploaderHelper $uploaderHelper, IntegrationHelper $integrationHelper, AssetModel $model, $objectId)
    {
        $entity = $model->getEntity($objectId);
        $clone  = null;

        if (null != $entity) {
            if (!$this->security->isGranted('asset:assets:create')
                || !$this->security->hasEntityAccess(
                    'asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setDownloadCount(0);
            $clone->setUniqueDownloadCount(0);
            $clone->setRevision(0);
            $clone->setIsPublished(false);
        }

        return $this->newAction($request, $parametersHelper, $uploaderHelper, $integrationHelper, $model, $clone);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function deleteAction(Request $request, AssetModel $model, $objectId)
    {
        $page      = $request->getSession()->get('mautic.asset.page', 1);
        $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_asset_index',
                'mauticContent' => 'asset',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.asset.asset.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->hasEntityAccess(
                'asset:assets:deleteown',
                'asset:assets:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'asset.asset');
            }

            $entity->removeUpload();
            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId,
                ],
            ];
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request, AssetModel $model): Response
    {
        $page      = $request->getSession()->get('mautic.asset.page', 1);
        $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_asset_index',
                'mauticContent' => 'asset',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.asset.asset.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->hasEntityAccess(
                    'asset:assets:deleteown', 'asset:assets:deleteother', $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'asset', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.asset.asset.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Renders the container for the remote file browser.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function remoteAction(Request $request, IntegrationHelper $integrationHelper): Response
    {
        // Check for integrations to cloud providers
        $integrations = $integrationHelper->getIntegrationObjects(null, ['cloud_storage']);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        return $this->delegateView([
            'viewParameters' => [
                'integrations' => $integrations,
                'tmpl'         => $tmpl,
            ],
            'contentTemplate' => '@MauticAsset/Remote/browse.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'asset',
                'route'         => $this->generateUrl('mautic_asset_index', ['page' => $request->getSession()->get('mautic.asset.page', 1)]),
            ],
        ]);
    }

    public function getModelName(): string
    {
        return 'asset';
    }

    protected function getDefaultOrderDirection(): string
    {
        return 'DESC';
    }
}
