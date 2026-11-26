<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Ai\AiGateway;
use Mautic\CoreBundle\Ai\AiGatewayException;
use Mautic\CoreBundle\Ai\ConnectionTestFeature;
use Mautic\CoreBundle\Ai\ModelWorkload;
use Mautic\CoreBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AiConnectionTestController
{
    public function __construct(
        private UserHelper $users,
        private CsrfTokenManagerInterface $csrf,
        private AiGateway $gateway,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/s/ai/connection-test/{workload}',
        name: 'mautic_ai_connection_test',
        methods: ['POST']
    )]
    public function __invoke(ModelWorkload $workload, Request $request): JsonResponse
    {
        if (!$this->users->getUser(nullIfGuest: true)?->isAdmin() || !$this->csrf->isTokenValid(new CsrfToken('mautic_ai_connection_test', $request->headers->get('X-CSRF-Token', '')))) {
            return new JsonResponse([], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->gateway->request(ConnectionTestFeature::idFor($workload), []);
        } catch (AiGatewayException $exception) {
            $reason = match ($exception->getCode()) {
                AiGatewayException::NOT_CONFIGURED => 'not_configured',
                AiGatewayException::CLOUD_DISABLED => 'cloud_disabled',
                AiGatewayException::INVALID_CREDENTIAL => 'invalid_credential',
                AiGatewayException::INVALID_RESPONSE => 'invalid_response',
                default => 'failed',
            };

            return new JsonResponse(['message' => $this->translator->trans('mautic.core.ai.connection_test.'.$reason)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['message' => $this->translator->trans('mautic.core.ai.connection_test.success')]);
    }
}
