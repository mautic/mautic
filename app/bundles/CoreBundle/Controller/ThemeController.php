<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ThemeController
 */
class ThemeController extends FormController
{

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {

        $themeHelper = $this->container->get('mautic.helper.theme');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted([
            'core:themes:view',
            'core:themes:create',
            'core:themes:edit',
            'core:themes:delete'
        ], "RETURN_ARRAY");

        if (!$permissions['core:themes:view']) {
            return $this->accessDenied();
        }

        $themes = $themeHelper->getInstalledThemes('all', true);

        $action = $this->generateUrl('mautic_themes_action', ['objectAction' => 'install']);
        $form   = $this->get('form.factory')->create('theme_upload', [], ['action' => $action]);

        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    if (!empty($fileData)) {
                        $fileData->move($directories['user'], $fileData->getClientOriginalName());
                    } else {
                        $form->addError(
                            new FormError(
                                $this->factory->getTranslator()->trans('mautic.dashboard.upload.filenotfound', [], 'validators')
                            )
                        );
                    }
                }
            }
        }

        return $this->delegateView([
            'viewParameters'  => [
                'items'       => $themes,
                'form'        => $form->createView(),
                'permissions' => $permissions,
                'security'    => $this->factory->getSecurity()
            ],
            'contentTemplate' => 'MauticCoreBundle:Theme:list.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_themes_index',
                'mauticContent' => 'theme',
                'route'         => $this->generateUrl('mautic_themes_index')
            ]
        ]);
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        // $model       = $this->getModel('asset');
        // $security    = $this->factory->getSecurity();
        // $activeAsset = $model->getEntity($objectId);
        // $request     = $this->request;

        // //set the asset we came from
        // $page = $this->factory->getSession()->get('mautic.asset.page', 1);

        // $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'details') : 'details';

        // // Init the date range filter form
        // $dateRangeValues = $this->request->get('daterange', []);
        // $action          = $this->generateUrl('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        // $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);

        // if ($activeAsset === null) {
        //     //set the return URL
        //     $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);

        //     return $this->postActionRedirect([
        //         'returnUrl'       => $returnUrl,
        //         'viewParameters'  => ['page' => $page],
        //         'contentTemplate' => 'MauticAssetBundle:Asset:index',
        //         'passthroughVars' => [
        //             'activeLink'    => '#mautic_asset_index',
        //             'mauticContent' => 'asset'
        //         ],
        //         'flashes'         => [
        //             [
        //                 'type'    => 'error',
        //                 'msg'     => 'mautic.asset.asset.error.notfound',
        //                 'msgVars' => ['%id%' => $objectId]
        //             ]
        //         ]
        //     ]);
        // } elseif (!$this->factory->getSecurity()->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $activeAsset->getCreatedBy())) {
        //     return $this->accessDenied();
        // }

        // // Audit Log
        // $logs = $this->getModel('core.auditLog')->getLogForObject('asset', $activeAsset->getId(), $activeAsset->getDateAdded());

        // return $this->delegateView([
        //     'returnUrl'       => $action,
        //     'viewParameters'  => [
        //         'activeAsset'      => $activeAsset,
        //         'tmpl'             => $tmpl,
        //         'permissions'      => $security->isGranted([
        //             'asset:assets:viewown',
        //             'asset:assets:viewother',
        //             'asset:assets:create',
        //             'asset:assets:editown',
        //             'asset:assets:editother',
        //             'asset:assets:deleteown',
        //             'asset:assets:deleteother',
        //             'asset:assets:publishown',
        //             'asset:assets:publishother'
        //         ], "RETURN_ARRAY"),
        //         'stats'            => [
        //             'downloads' => [
        //                 'total'     => $activeAsset->getDownloadCount(),
        //                 'unique'    => $activeAsset->getUniqueDownloadCount(),
        //                 'timeStats' => $model->getDownloadsLineChartData(
        //                     null, 
        //                     new \DateTime($dateRangeForm->get('date_from')->getData()), 
        //                     new \DateTime($dateRangeForm->get('date_to')->getData()), 
        //                     null, 
        //                     ['asset_id' => $activeAsset->getId()]
        //                 )
        //             ]
        //         ],
        //         'security'         => $security,
        //         'assetDownloadUrl' => $model->generateUrl($activeAsset, true),
        //         'logs'             => $logs,
        //         'dateRangeForm'    => $dateRangeForm->createView()
        //     ],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:' . $tmpl . '.html.php',
        //     'passthroughVars' => [
        //         'activeLink'    => '#mautic_asset_index',
        //         'mauticContent' => 'asset'
        //     ]
        // ]);
    }

    /**
     * Show a preview of the file
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function previewAction ($objectId)
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        // $model       = $this->getModel('asset');
        // $activeAsset = $model->getEntity($objectId);

        // if ($activeAsset === null || !$this->factory->getSecurity()->hasEntityAccess('asset:assets:viewown', 'asset:assets:viewother', $activeAsset->getCreatedBy())) {
        //     return $this->modalAccessDenied();
        // }

        // return $this->delegateView([
        //     'viewParameters'  => [
        //         'activeAsset'      => $activeAsset,
        //         'assetDownloadUrl' => $model->generateUrl($activeAsset)
        //     ],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:preview.html.php',
        //     'passthroughVars' => [
        //         'route' => false
        //     ]
        // ]);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        // $model = $this->getModel('asset');

        // /** @var \Mautic\AssetBundle\Entity\Asset $entity */
        // $entity  = $model->getEntity();
        // $entity->setMaxSize(Asset::convertSizeToBytes($this->factory->getParameter('max_size') . 'M')); // convert from MB to B
        // $method  = $this->request->getMethod();
        // $session = $this->factory->getSession();

        // if (!$this->factory->getSecurity()->isGranted('asset:assets:create')) {
        //     return $this->accessDenied();
        // }

        // $maxSize    =  $model->getMaxUploadSize();
        // $extensions = '.' . implode(', .', $this->factory->getParameter('allowed_extensions'));

        // $maxSizeError = $this->get('translator')->trans('mautic.asset.asset.error.file.size', [
        //     '%fileSize%' => '{{filesize}}',
        //     '%maxSize%'  => '{{maxFilesize}}'
        // ], 'validators');

        // $extensionError = $this->get('translator')->trans('mautic.asset.asset.error.file.extension.js', [
        //     '%extensions%' => $extensions
        // ], 'validators');

        // // Create temporary asset ID
        // $tempId = ($method == 'POST') ? $this->request->request->get('asset[tempId]', '', true) : uniqid('tmp_');
        // $entity->setTempId($tempId);

        // // Set the page we came from
        // $page   = $session->get('mautic.asset.page', 1);
        // $action = $this->generateUrl('mautic_asset_action', ['objectAction' => 'new']);

        // // Get upload folder
        // $uploaderHelper = $this->container->get('oneup_uploader.templating.uploader_helper');
        // $uploadEndpoint = $uploaderHelper->endpoint('asset');

        // //create the form
        // $form = $model->createForm($entity, $this->get('form.factory'), $action);

        // ///Check for a submitted form and process it
        // if ($method == 'POST') {
        //     $valid = false;
        //     if (!$cancelled = $this->isFormCancelled($form)) {
        //         if ($valid = $this->isFormValid($form)) {

        //             $entity->setUploadDir($this->factory->getParameter('upload_dir'));
        //             $entity->preUpload();
        //             $entity->upload();

        //             //form is valid so process the data
        //             $model->saveEntity($entity);

        //             //remove the asset from request
        //             $this->request->files->remove('asset');

        //             $this->addFlash('mautic.core.notice.created', [
        //                 '%name%'      => $entity->getTitle(),
        //                 '%menu_link%' => 'mautic_asset_index',
        //                 '%url%'       => $this->generateUrl('mautic_asset_action', [
        //                     'objectAction' => 'edit',
        //                     'objectId'     => $entity->getId()
        //                 ])
        //             ]);

        //             if (!$form->get('buttons')->get('save')->isClicked()) {
        //                 //return edit view so that all the session stuff is loaded
        //                 return $this->editAction($entity->getId(), true);
        //             }

        //             $viewParameters = [
        //                 'objectAction' => 'view',
        //                 'objectId'     => $entity->getId()
        //             ];
        //             $returnUrl      = $this->generateUrl('mautic_asset_action', $viewParameters);
        //             $template       = 'MauticAssetBundle:Asset:view';
        //         }
        //     } else {
        //         $viewParameters = ['page' => $page];
        //         $returnUrl      = $this->generateUrl('mautic_asset_index', $viewParameters);
        //         $template       = 'MauticAssetBundle:Asset:index';
        //     }

        //     if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
        //         return $this->postActionRedirect([
        //             'returnUrl'       => $returnUrl,
        //             'viewParameters'  => $viewParameters,
        //             'contentTemplate' => $template,
        //             'passthroughVars' => [
        //                 'activeLink'    => 'mautic_asset_index',
        //                 'mauticContent' => 'asset'
        //             ]
        //         ]);
        //     }
        // }

        // // Check for integrations to cloud providers
        // /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        // $integrationHelper = $this->factory->getHelper('integration');

        // $integrations = $integrationHelper->getIntegrationObjects(null, ['cloud_storage']);

        // return $this->delegateView([
        //     'viewParameters'  => [
        //         'form'             => $form->createView(),
        //         'activeAsset'      => $entity,
        //         'assetDownloadUrl' => $model->generateUrl($entity),
        //         'integrations'     => $integrations,
        //         'startOnLocal'     => $entity->getStorageLocation() == 'local',
        //         'uploadEndpoint'   => $uploadEndpoint,
        //         'maxSize'          => $maxSize,
        //         'maxSizeError'     => $maxSizeError,
        //         'extensions'       => $extensions,
        //         'extensionError'   => $extensionError
        //     ],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:form.html.php',
        //     'passthroughVars' => [
        //         'activeLink'    => '#mautic_asset_index',
        //         'mauticContent' => 'asset',
        //         'route'         => $this->generateUrl('mautic_asset_action', [
        //             'objectAction' => 'new'
        //         ])
        //     ]
        // ]);
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        // $model = $this->getModel('asset');

        // /** @var \Mautic\AssetBundle\Entity\Asset $entity */
        // $entity     = $model->getEntity($objectId);
        // $entity->setMaxSize(Asset::convertSizeToBytes($this->factory->getParameter('max_size') . 'M')); // convert from MB to B
        // $session    = $this->factory->getSession();
        // $page       = $this->factory->getSession()->get('mautic.asset.page', 1);
        // $method     = $this->request->getMethod();
        // $maxSize    = $model->getMaxUploadSize();
        // $extensions = '.' . implode(', .', $this->factory->getParameter('allowed_extensions'));

        // $maxSizeError = $this->get('translator')->trans('mautic.asset.asset.error.file.size', [
        //     '%fileSize%' => '{{filesize}}',
        //     '%maxSize%'  => '{{maxFilesize}}'
        // ], 'validators');

        // $extensionError = $this->get('translator')->trans('mautic.asset.asset.error.file.extension.js', [
        //     '%extensions%' => $extensions
        // ], 'validators');

        // //set the return URL
        // $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);

        // // Get upload folder
        // $uploaderHelper = $this->container->get('oneup_uploader.templating.uploader_helper');
        // $uploadEndpoint = $uploaderHelper->endpoint('asset');

        // $postActionVars = [
        //     'returnUrl'       => $returnUrl,
        //     'viewParameters'  => ['page' => $page],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:index',
        //     'passthroughVars' => [
        //         'activeLink'    => 'mautic_asset_index',
        //         'mauticContent' => 'asset'
        //     ]
        // ];

        // //not found
        // if ($entity === null) {
        //     return $this->postActionRedirect(
        //         array_merge($postActionVars, [
        //             'flashes' => [
        //                 [
        //                     'type'    => 'error',
        //                     'msg'     => 'mautic.asset.asset.error.notfound',
        //                     'msgVars' => ['%id%' => $objectId]
        //                 ]
        //             ]
        //         ])
        //     );
        // } elseif (!$this->factory->getSecurity()->hasEntityAccess(
        //     'asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()
        // )
        // ) {
        //     return $this->accessDenied();
        // } elseif ($model->isLocked($entity)) {
        //     //deny access if the entity is locked
        //     return $this->isLocked($postActionVars, $entity, 'asset.asset');
        // }

        // // Create temporary asset ID
        // $tempId = ($method == 'POST') ? $this->request->request->get('asset[tempId]', '', true) : uniqid('tmp_');
        // $entity->setTempId($tempId);

        // //Create the form
        // $action = $this->generateUrl('mautic_asset_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        // $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        // ///Check for a submitted form and process it
        // if (!$ignorePost && $method == 'POST') {
        //     $valid = false;
        //     if (!$cancelled = $this->isFormCancelled($form)) {
        //         if ($valid = $this->isFormValid($form)) {
        //             $entity->setUploadDir($this->factory->getParameter('upload_dir'));
        //             $entity->preUpload();
        //             $entity->upload();

        //             //form is valid so process the data
        //             $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

        //             //remove the asset from request
        //             $this->request->files->remove('asset');

        //             $this->addFlash('mautic.core.notice.updated', [
        //                 '%name%'      => $entity->getTitle(),
        //                 '%menu_link%' => 'mautic_asset_index',
        //                 '%url%'       => $this->generateUrl('mautic_asset_action', [
        //                     'objectAction' => 'edit',
        //                     'objectId'     => $entity->getId()
        //                 ])
        //             ]);

        //             $returnUrl  = $this->generateUrl('mautic_asset_action', [
        //                 'objectAction' => 'view',
        //                 'objectId'     => $entity->getId()
        //             ]);
        //             $viewParams = ['objectId' => $entity->getId()];
        //             $template   = 'MauticAssetBundle:Asset:view';
        //         }
        //     } else {
        //         //clear any modified content
        //         $session->remove('mautic.asestbuilder.' . $objectId . '.content');
        //         //unlock the entity
        //         $model->unlockEntity($entity);

        //         $returnUrl  = $this->generateUrl('mautic_asset_index', ['page' => $page]);
        //         $viewParams = ['page' => $page];
        //         $template   = 'MauticAssetBundle:Asset:index';
        //     }

        //     if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
        //         return $this->postActionRedirect(
        //             array_merge($postActionVars, [
        //                 'returnUrl'       => $returnUrl,
        //                 'viewParameters'  => $viewParams,
        //                 'contentTemplate' => $template
        //             ])
        //         );
        //     }
        // } else {
        //     //lock the entity
        //     $model->lockEntity($entity);
        // }

        // // Check for integrations to cloud providers
        // /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        // $integrationHelper = $this->factory->getHelper('integration');

        // $integrations = $integrationHelper->getIntegrationObjects(null, ['cloud_storage']);

        // return $this->delegateView([
        //     'viewParameters'  => [
        //         'form'             => $form->createView(),
        //         'activeAsset'      => $entity,
        //         'assetDownloadUrl' => $model->generateUrl($entity),
        //         'integrations'     => $integrations,
        //         'startOnLocal'     => $entity->getStorageLocation() == 'local',
        //         'uploadEndpoint'   => $uploadEndpoint,
        //         'maxSize'          => $maxSize,
        //         'maxSizeError'     => $maxSizeError,
        //         'extensions'       => $extensions,
        //         'extensionError'   => $extensionError
        //     ],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:form.html.php',
        //     'passthroughVars' => [
        //         'activeLink'    => '#mautic_asset_index',
        //         'mauticContent' => 'asset',
        //         'route'         => $this->generateUrl('mautic_asset_action', [
        //             'objectAction' => 'edit',
        //             'objectId'     => $entity->getId()
        //         ])
        //     ]
        // ]);
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        // $model  = $this->getModel('asset');
        // $entity = $model->getEntity($objectId);

        // if ($entity != null) {
        //     if (!$this->factory->getSecurity()->isGranted('asset:assets:create') ||
        //         !$this->factory->getSecurity()->hasEntityAccess(
        //             'asset:assets:viewown', 'asset:assets:viewother', $entity->getCreatedBy()
        //         )
        //     ) {
        //         return $this->accessDenied();
        //     }

        //     $clone = clone $entity;
        //     $clone->setDownloadCounts(0);
        //     $clone->setUniqueDownloadCounts(0);
        //     $clone->setRevision(0);
        //     $clone->setIsPublished(false);
        //     $model->saveEntity($clone);
        //     $objectId = $clone->getId();
        // }

        // return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        // $page      = $this->factory->getSession()->get('mautic.asset.page', 1);
        // $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);
        // $flashes   = [];

        // $postActionVars = [
        //     'returnUrl'       => $returnUrl,
        //     'viewParameters'  => ['page' => $page],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:index',
        //     'passthroughVars' => [
        //         'activeLink'    => 'mautic_asset_index',
        //         'mauticContent' => 'asset'
        //     ]
        // ];

        // if ($this->request->getMethod() == 'POST') {
        //     /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        //     $model  = $this->getModel('asset');
        //     $entity = $model->getEntity($objectId);

        //     if ($entity === null) {
        //         $flashes[] = [
        //             'type'    => 'error',
        //             'msg'     => 'mautic.asset.asset.error.notfound',
        //             'msgVars' => ['%id%' => $objectId]
        //         ];
        //     } elseif (!$this->factory->getSecurity()->hasEntityAccess(
        //         'asset:assets:deleteown',
        //         'asset:assets:deleteother',
        //         $entity->getCreatedBy()
        //     )
        //     ) {
        //         return $this->accessDenied();
        //     } elseif ($model->isLocked($entity)) {
        //         return $this->isLocked($postActionVars, $entity, 'asset.asset');
        //     }

        //     $entity->removeUpload();
        //     $model->deleteEntity($entity);

        //     $flashes[] = [
        //         'type'    => 'notice',
        //         'msg'     => 'mautic.core.notice.deleted',
        //         'msgVars' => [
        //             '%name%' => $entity->getTitle(),
        //             '%id%'   => $objectId
        //         ]
        //     ];
        // } //else don't do anything

        // return $this->postActionRedirect(
        //     array_merge($postActionVars, [
        //         'flashes' => $flashes
        //     ])
        // );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction ()
    {
        // $page      = $this->factory->getSession()->get('mautic.asset.page', 1);
        // $returnUrl = $this->generateUrl('mautic_asset_index', ['page' => $page]);
        // $flashes   = [];

        // $postActionVars = [
        //     'returnUrl'       => $returnUrl,
        //     'viewParameters'  => ['page' => $page],
        //     'contentTemplate' => 'MauticAssetBundle:Asset:index',
        //     'passthroughVars' => [
        //         'activeLink'    => 'mautic_asset_index',
        //         'mauticContent' => 'asset'
        //     ]
        // ];

        // if ($this->request->getMethod() == 'POST') {
        //     /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        //     $model     = $this->getModel('asset');
        //     $ids       = json_decode($this->request->query->get('ids', '{}'));
        //     $deleteIds = [];

        //     // Loop over the IDs to perform access checks pre-delete
        //     foreach ($ids as $objectId) {
        //         $entity = $model->getEntity($objectId);

        //         if ($entity === null) {
        //             $flashes[] = [
        //                 'type'    => 'error',
        //                 'msg'     => 'mautic.asset.asset.error.notfound',
        //                 'msgVars' => ['%id%' => $objectId]
        //             ];
        //         } elseif (!$this->factory->getSecurity()->hasEntityAccess(
        //             'asset:assets:deleteown', 'asset:assets:deleteother', $entity->getCreatedBy()
        //         )
        //         ) {
        //             $flashes[] = $this->accessDenied(true);
        //         } elseif ($model->isLocked($entity)) {
        //             $flashes[] = $this->isLocked($postActionVars, $entity, 'asset', true);
        //         } else {
        //             $deleteIds[] = $objectId;
        //         }
        //     }

        //     // Delete everything we are able to
        //     if (!empty($deleteIds)) {
        //         $entities = $model->deleteEntities($deleteIds);

        //         $flashes[] = [
        //             'type'    => 'notice',
        //             'msg'     => 'mautic.asset.asset.notice.batch_deleted',
        //             'msgVars' => [
        //                 '%count%' => count($entities)
        //             ]
        //         ];
        //     }
        // } //else don't do anything

        // return $this->postActionRedirect(
        //     array_merge($postActionVars, [
        //         'flashes' => $flashes
        //     ])
        // );
    }
}
