<?php

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends CommonApiController<object>
 */
final class ThemeApiController extends CommonApiController
{
    private readonly ThemeHelper $themeHelper;
    private PathsHelper $pathsHelper;

    #[Required]
    public function autowireThemeApiController(
        ThemeHelper $themeHelper,
        PathsHelper $pathsHelper,
    ): void {
        $this->themeHelper = $themeHelper;
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * Accepts the zip file and installs the theme from it.
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        if (!$this->security->isGranted('core:themes:create')) {
            return $this->accessDenied();
        }

        $response  = ['success' => false];
        $themeZip  = $request->files->get('file');
        $extension = $themeZip->getClientOriginalExtension();

        if (!$themeZip) {
            return $this->returnError(
                $this->translator->trans('mautic.core.theme.upload.empty', [], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        }
        if ('zip' !== $extension) {
            return $this->returnError(
                $this->translator->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        }
        $fileName  = InputHelper::filename($themeZip->getClientOriginalName());
        $dir       = $this->pathsHelper->getSystemPath('themes', true);

        try {
            $themeZip->move($dir, $fileName);
            $response['success'] = $this->themeHelper->install($dir.'/'.$fileName);
        } catch (\Exception $e) {
            return $this->returnError(
                $this->translator->trans($e->getMessage(), [], 'validators')
            );
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }

    /**
     * Get zip file of a theme.
     *
     * @param string $theme dir name
     *
     * @return Response
     */
    public function getAction($theme)
    {
        if (!$this->security->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        try {
            $themeZip = $this->themeHelper->zip($theme);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        if (!$themeZip) {
            return $this->returnError(
                $this->translator->trans(
                    'mautic.core.dir.not.accesssible',
                    ['%dir%' => $theme]
                )
            );
        }

        $response = new BinaryFileResponse($themeZip);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    /**
     * List the folders (themes) in the /themes directory.
     *
     * @return Response
     */
    public function listAction()
    {
        if (!$this->security->isGranted('core:themes:view')) {
            return $this->accessDenied();
        }

        try {
            $themes = $this->themeHelper->getInstalledThemes('all', true, false, false);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        $view = $this->view(['themes' => $themes]);

        return $this->handleView($view);
    }

    /**
     * Delete a theme.
     *
     * @param string $theme
     *
     * @return Response
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
            return $this->returnError($e->getMessage());
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }
}
