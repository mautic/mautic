<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Form\Type\ThemeUploadType;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends FormController
{
    /**
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request, ThemeHelperInterface $themeHelper, BuilderIntegrationsHelper $builderIntegrationsHelper, PathsHelper $pathsHelper)
    {
        // set some permissions
        $permissions = $this->security->isGranted([
            'core:themes:view',
            'core:themes:create',
            'core:themes:edit',
            'core:themes:delete',
        ], 'RETURN_ARRAY');

        if (!$permissions['core:themes:view']) {
            return $this->accessDenied();
        }

        $dir    = $pathsHelper->getSystemPath('themes', true);
        $action = $this->generateUrl('mautic_themes_index');
        $form   = $this->formFactory->create(ThemeUploadType::class, [], ['action' => $action]);

        if ('POST' === $request->getMethod()) {
            if (!$this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $fileData = $form['file']->getData();

                    if (!$fileData) {
                        $form->addError(
                            new FormError(
                                $this->translator->trans('mautic.core.theme.upload.empty', [], 'validators')
                            )
                        );
                    } else {
                        $fileName  = InputHelper::filename($fileData->getClientOriginalName());
                        $themeName = basename($fileName, '.zip');

                        if (!empty($fileData)) {
                            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                            if ('zip' === $extension) {
                                try {
                                    $fileData->move($dir, $fileName);
                                    $themeHelper->install($dir.'/'.$fileName);
                                    $this->addFlashMessage('mautic.core.theme.installed', ['%name%' => $themeName]);
                                } catch (\Exception $e) {
                                    $form->addError(
                                        new FormError(
                                            $this->translator->trans($e->getMessage(), [], 'validators')
                                        )
                                    );
                                }
                            } else {
                                $form->addError(
                                    new FormError(
                                        $this->translator->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators')
                                    )
                                );
                            }
                        } else {
                            $form->addError(
                                new FormError(
                                    $this->translator->trans('mautic.dashboard.upload.filenotfound', [], 'validators')
                                )
                            );
                        }
                    }
                } else {
                    $form->addError(new FormError($form->getErrors(true)));
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'items'         => $themeHelper->getInstalledThemes('all', true, true),
                'builders'      => $builderIntegrationsHelper->getBuilderNames(),
                'defaultThemes' => $themeHelper->getDefaultThemes(),
                'form'          => $form->createView(),
                'permissions'   => $permissions,
                'security'      => $this->security,
            ],
            'contentTemplate' => '@MauticCore/Theme/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_themes_index',
                'mauticContent' => 'theme',
                'route'         => $this->generateUrl('mautic_themes_index'),
            ],
        ]);
    }

    /**
     * Download a theme.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function downloadAction(Request $request, ThemeHelperInterface $themeHelper, string $objectId)
    {
        $flashes = [];
        $error   = false;

        if (!$this->security->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        $themeName = $objectId;
        if (!$themeHelper->exists($themeName)) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.error.notfound',
                'msgVars' => ['%theme%' => $themeName],
            ];
            $error = true;
        }

        try {
            $zipPath = $themeHelper->zip($themeName);
        } catch (\Exception $e) {
            $flashes[] = [
                'type' => 'error',
                'msg'  => $e->getMessage(),
            ];
            $error = true;
        }

        if (!$error && !$zipPath) {
            $flashes[] = [
                'type' => 'error',
                'msg'  => 'mautic.core.permission.issue',
            ];
            $error = true;
        }

        if ($error) {
            return $this->postActionRedirect(
                array_merge($this->getIndexPostActionVars(), [
                    'flashes' => $flashes,
                ])
            );
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', (string) filesize($zipPath));

        $stream = $request->get('stream', 0);

        if (!$stream) {
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$themeName.'.zip"');
        }

        $response->setContent(file_get_contents($zipPath));

        return $response;
    }

    /**
     * Deletes the theme.
     */
    public function deleteAction(Request $request, ThemeHelperInterface $themeHelper, string $objectId): Response
    {
        $flashes = [];

        $themeName = $objectId;
        if ('POST' === $request->getMethod()) {
            $flashes = $this->deleteTheme($themeHelper, $themeName);
        }

        return $this->postActionRedirect(
            array_merge($this->getIndexPostActionVars(), [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of themes.
     */
    public function batchDeleteAction(Request $request, ThemeHelperInterface $themeHelper): Response
    {
        $flashes = [];
        $error   = [];

        if ('POST' === $request->getMethod()) {
            $themeNames = json_decode($request->query->get('ids', '{}'));

            foreach ($themeNames as $themeName) {
                $flash = $this->deleteTheme($themeHelper, $themeName)[0];

                if ('error' === $flash['type']) {
                    $error[] = $flash;
                } else {
                    $flashes[] = $flash;
                }
            }

            if (count($flashes) > 1) {
                $flashNumber = count($flashes);
                unset($flashes);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.theme.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => $flashNumber,
                    ],
                ];
            }

            if ($error) {
                $flashes = array_merge($flashes, $error);
            }
        }

        return $this->postActionRedirect(
            array_merge($this->getIndexPostActionVars(), [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a theme.
     *
     * @return array
     */
    public function deleteTheme(ThemeHelperInterface $themeHelper, $themeName)
    {
        $flashes = [];

        if (!$themeHelper->exists($themeName)) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.error.notfound',
                'msgVars' => ['%theme%' => $themeName],
            ];
        } elseif (!$this->security->isGranted('core:themes:delete')) {
            return $this->accessDenied();
        } elseif (in_array($themeName, $themeHelper->getDefaultThemes())) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.cannot.be.removed',
                'msgVars' => ['%theme%' => $themeName],
            ];
        } else {
            try {
                $theme = $themeHelper->getTheme($themeName);
                $themeHelper->delete($themeName);
            } catch (\Exception $e) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.core.error.delete.error',
                    'msgVars' => ['%error%' => $e->getMessage()],
                ];
            }

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $theme->getName(),
                    '%id%'   => $themeName,
                ],
            ];
        }

        return $flashes;
    }

    /**
     * A helper method to keep the code DRY.
     */
    public function getIndexPostActionVars(): array
    {
        return [
            'returnUrl'       => $this->generateUrl('mautic_themes_index'),
            'contentTemplate' => 'Mautic\CoreBundle\Controller\ThemeController::indexAction',
            'passthroughVars' => [
                'activeLink'    => 'mautic_themes_index',
                'mauticContent' => 'theme',
            ],
        ];
    }

    /**
     * Change default theme's visibility.
     */
    public function visibilityAction(string $objectId, Request $request, CorePermissions $corePermissions, ThemeHelperInterface $themeHelper): Response
    {
        if (!$corePermissions->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        $flashes = [];

        if (Request::METHOD_POST === $request->getMethod()) {
            $flashes = $this->visibility($objectId, $themeHelper);
        }

        return $this->postActionRedirect(
            array_merge($this->getIndexPostActionVars(), [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * @return array<mixed>
     */
    private function visibility(string $themeName, ThemeHelperInterface $themeHelper): array
    {
        if (!$themeHelper->exists($themeName)) {
            return [
                [
                    'type'    => 'error',
                    'msg'     => 'mautic.core.theme.error.notfound',
                    'msgVars' => ['%theme%' => $themeName],
                ],
            ];
        }

        if (!in_array($themeName, $themeHelper->getDefaultThemes())) {
            return [
                [
                    'type'    => 'error',
                    'msg'     => 'mautic.core.theme.cannot.change.visibility',
                    'msgVars' => ['%theme%' => $themeName],
                ],
            ];
        }

        $flashes = [];

        try {
            $theme = $themeHelper->getTheme($themeName);
            $themeHelper->toggleVisibility($themeName);
            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.theme.visibility.changed',
                'msgVars' => ['%theme%' => $theme->getName()],
            ];
        } catch (IOException) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.visibility.error',
                'msgVars' => ['%error%' => 'Failed to change the theme visibility'],
            ];
        } catch (BadConfigurationException) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.visibility.error',
                'msgVars' => ['%error%' => sprintf('Theme %s not configured properly: builder property in the config.json', $themeName)],
            ];
        } catch (FileNotFoundException) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.visibility.error',
                'msgVars' => ['%error%' => sprintf('Theme %s not found', $themeName)],
            ];
        }

        return $flashes;
    }
}
