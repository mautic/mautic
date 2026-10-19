<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use FM\ElfinderBundle\Loader\ElFinderLoaderInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ElFinderController extends \FM\ElfinderBundle\Controller\ElFinderController
{
    public function __construct(
        private readonly CorePermissions $corePermissions,
        private readonly TranslatorInterface $translator,
        Environment $twig,
        #[Autowire(param: 'fm_elfinder')]
        array $params,
        #[Autowire(service: 'fm_elfinder.loader')]
        ElFinderLoaderInterface $loader,
    ) {
        parent::__construct($twig, $params, $loader);
    }

    public function showAction(Request $request): Response
    {
        if (!$this->corePermissions->isGranted('core:file_manager:full')) {
            $message = $this->translator->trans('mautic.core.url.error.403', ['%url%' => $request->getRequestUri()]);
            throw new AccessDeniedHttpException($message);
        }

        return parent::show($request, 'default', '');
    }
}
