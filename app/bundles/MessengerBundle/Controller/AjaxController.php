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
    private MessageBusInterface $bus;
    private TestMessageFactory $messageFactory;

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

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(
        MessageBusInterface $bus,
        TestMessageFactory $messageFactory,
    ): void {
        $this->bus = $bus;
        $this->messageFactory = $messageFactory;
    }
}
