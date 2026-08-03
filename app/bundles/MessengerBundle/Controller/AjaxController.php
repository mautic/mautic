<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\MessengerBundle\Service\TestMessageFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final class AjaxController extends CommonAjaxController
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected \Mautic\CoreBundle\Factory\ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly MessageBusInterface $bus,
        private readonly TestMessageFactory $messageFactory,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function sendTestMessageAction(
        Request $request,
    ): Response {
        try {
            $message = $this->messageFactory->crateMessageByDsnKey((string) $request->request->get('key'));
        } catch (\InvalidArgumentException) {
            return $this->notFound();
        }

        $data = [
            'success' => 1,
            'message' => $this->translator->trans('mautic.core.success'),
        ];

        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            $data['success'] = 0;
            $data['message'] = $this->translator->trans('mautic.messenger.config.dsn.test_message_failed', ['%message%' => $e->getMessage()]);
        }

        return $this->sendJsonResponse($data);
    }
}
