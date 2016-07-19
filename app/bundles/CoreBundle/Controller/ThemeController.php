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
use Symfony\Component\Form\FormError;

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

        $dir    = $this->factory->getSystemPath('themes', true);
        $action = $this->generateUrl('mautic_themes_index');
        $form   = $this->get('form.factory')->create('theme_upload', [], ['action' => $action]);

        if ($this->request->getMethod() == 'POST') {
            if (isset($form) && !$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();
                    $fileName = $fileData->getClientOriginalName();
                    if (!empty($fileData)) {
                        try {
                            $fileData->move($dir, $fileName);
                            $themeHelper->install($dir.'/'.$fileName);
                        } catch (\Exception $e) {
                            $form->addError(
                                new FormError(
                                    $this->factory->getTranslator()->trans($e->getMessage(), [], 'validators')
                                )
                            );
                        }
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
                'items'       => $themeHelper->getInstalledThemes('all', true),
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
     * Deletes the theme
     *
     * @param string $themeName
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($themeName)
    {
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $this->generateUrl('mautic_themes_index'),
            'contentTemplate' => 'MauticCoreBundle:theme:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_themes_index',
                'mauticContent' => 'theme'
            ]
        ];

        if ($this->request->getMethod() == 'POST') {
            $themeHelper = $this->container->get('mautic.helper.theme');

            if (!$themeHelper->exists($themeName)) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.core.theme.error.notfound',
                    'msgVars' => ['%theme%' => $themeName]
                ];
            } elseif (!$this->factory->getSecurity()->isGranted('core:themes:delete')) {
                return $this->accessDenied();
            } else {

                try {
                    $theme = $themeHelper->getTheme($themeName);
                    $themeHelper->delete($themeName);
                } catch (\Exception $e) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.core.error.delete.error',
                        'msgVars' => ['%error%' => $e->getMessage()]
                    ];
                }

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $theme->getName(),
                        '%id%'   => $themeName
                    ]
                ];
            }
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes
            ])
        );
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
