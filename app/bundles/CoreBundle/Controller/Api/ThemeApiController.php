<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class ThemeApiController.
 */
class ThemeApiController extends CommonApiController
{
    /**
     * @var Mautic\CoreBundle\Helper\ThemeHelper
     */
    protected $themeHelper;

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->themeHelper = $this->container->get('mautic.helper.theme');

        parent::initialize($event);
    }

    /**
     * Accepts the zip file and installs the theme from it.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newAction()
    {
        if (!$this->security->isGranted('core:themes:create')) {
            return $this->accessDenied();
        }

        $response  = ['success' => false];
        $themeZip  = $this->getRequest()->files->get('file');
        $extension = $themeZip->getClientOriginalExtension();

        if (!$themeZip) {
            return $this->returnError(
                $this->translator->trans('mautic.core.theme.upload.empty', [], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        } elseif ($extension !== 'zip') {
            return $this->returnError(
                $this->translator->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $fileName  = InputHelper::filename($themeZip->getClientOriginalName());
            $themeName = basename($fileName, '.zip');
            $dir       = $this->get('mautic.helper.paths')->getSystemPath('themes', true);

            if (!empty($themeZip)) {
                try {
                    $themeZip->move($dir, $fileName);
                    $response['success'] = $this->themeHelper->install($dir.'/'.$fileName);
                } catch (\Exception $e) {
                    return $this->returnError(
                        $this->translator->trans($e->getMessage(), [], 'validators'),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            } else {
                return $this->returnError(
                    $this->translator->trans('mautic.dashboard.upload.filenotfound', [], 'validators'),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }

    /**
     * Get zip file of a theme.
     *
     * @param string $theme dir name
     *
     * @return BinaryFileResponse
     */
    public function getAction($theme)
    {
        if (!$this->security->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        try {
            $themeZip = $this->themeHelper->zip($theme);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$themeZip) {
            return $this->returnError(
                $this->translator->trans(
                    'mautic.core.dir.not.accesssible',
                    ['%dir%' => $theme]
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $response = new BinaryFileResponse($themeZip);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    /**
     * List the folders (themes) in the /themes directory.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction()
    {
        if (!$this->security->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        try {
            $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $view = $this->view(['themes' => $themes]);

        return $this->handleView($view);
    }

    /**
     * Delete a theme.
     *
     * @param string $theme
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAction($theme)
    {
        if (!$this->security->isGranted('core:themes:delete')) {
            return $this->accessDenied();
        }

        try {
            $this->themeHelper->delete($theme);
            $response = ['success' => true];
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }
}
