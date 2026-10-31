<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use FM\ElfinderBundle\Controller\ElFinderController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ElFinderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CorePermissions $corePermissions,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof ElFinderController) {
            return;
        }

        if ($this->corePermissions->isGranted('core:file_manager:full')) {
            return;
        }

        $request = $event->getRequest();
        $message = $this->translator->trans('mautic.core.url.error.403', ['%url%' => $request->getRequestUri()]);

        throw new AccessDeniedHttpException($message);
    }
}
