<?php

namespace Mautic\CoreBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<object>
 */
class ThemeApiController extends CommonApiController
{
    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        protected ThemeHelper $themeHelper,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Accepts the zip file and installs the theme from it.
     *
     * @return Response
     */
    public function newAction(Request $request, PathsHelper $pathsHelper)
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
        } elseif ('zip' !== $extension) {
            return $this->returnError(
                $this->translator->trans('mautic.core.not.allowed.file.extension', ['%extension%' => $extension], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $fileName  = InputHelper::filename($themeZip->getClientOriginalName());
            $themeName = basename($fileName, '.zip');
            $dir       = $pathsHelper->getSystemPath('themes', true);

            if (!empty($themeZip)) {
                try {
                    $themeZip->move($dir, $fileName);
                    $response['success'] = $this->themeHelper->install($dir.'/'.$fileName);
                } catch (\Exception $e) {
                    return $this->returnError(
                        $this->translator->trans($e->getMessage(), [], 'validators')
                    );
                }
            } else {
                return $this->returnError(
                    $this->translator->trans('mautic.dashboard.upload.filenotfound', [], 'validators')
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
