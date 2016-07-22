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
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
                    $fileName = InputHelper::filename($fileData->getClientOriginalName());

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
                'items'       => $themeHelper->getInstalledThemes('all', true, true),
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
     * Download a theme
     *
     * @param string $themeName
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function downloadAction($themeName)
    {
        $themeHelper = $this->container->get('mautic.helper.theme');
        $flashes     = [];
        $error       = false;

        if (!$this->factory->getSecurity()->isGranted('core:themes:edit')) {
            return $this->accessDenied();
        }

        if (!$themeHelper->exists($themeName)) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.theme.error.notfound',
                'msgVars' => ['%theme%' => $themeName]
            ];
            $error = true;
        }

        
        try {
            $zipPath = $themeHelper->zip($themeName);
        } catch (\Exception $e) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => $e->getMessage()
            ];
            $error = true;
        }
        
        if (!$error && !$zipPath) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.permission.issue'
            ];
            $error = true;
        }

        if ($error) {
            return $this->postActionRedirect(
                array_merge($this->getIndexPostActionVars(), [
                    'flashes' => $flashes
                ])
            );
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', filesize($zipPath));

        $stream = $this->request->get('stream', 0);

        if (!$stream) {
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$themeName.'.zip"');
        }

        $response->setContent(file_get_contents($zipPath));

        return $response;
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
        $flashes = [];

        if ($this->request->getMethod() == 'POST') {
            $flashes = $this->deleteTheme($themeName);
        }

        return $this->postActionRedirect(
            array_merge($this->getIndexPostActionVars(), [
                'flashes' => $flashes
            ])
        );
    }

    /**
     * Deletes a group of themes
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction ()
    {
        $flashes = [];

        if ($this->request->getMethod() == 'POST') {
            $themeNames = json_decode($this->request->query->get('ids', '{}'));

            foreach ($themeNames as $themeName) {
                $flashes = $this->deleteTheme($themeName);
            }
        }

        return $this->postActionRedirect(
            array_merge($this->getIndexPostActionVars(), [
                'flashes' => $flashes
            ])
        );
    }

    public function deleteTheme($themeName) {
        $flashes     = [];
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

        return $flashes;
    }

    public function getIndexPostActionVars()
    {
        return [
            'returnUrl'       => $this->generateUrl('mautic_themes_index'),
            'contentTemplate' => 'MauticCoreBundle:theme:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_themes_index',
                'mauticContent' => 'theme'
            ]
        ];
    }
}
