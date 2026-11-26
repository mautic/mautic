<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Ai\AiGateway;
use Mautic\CoreBundle\Ai\AiGatewayException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Ai\EmailSubjectAdvisor;
use Mautic\EmailBundle\Entity\EmailRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailSubjectAdvisorController
{
    public function __construct(
        private CsrfTokenManagerInterface $csrf,
        private CorePermissions $permissions,
        private EmailRepository $emails,
        private AiGateway $gateway,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/s/emails/{id}/subject-suggestion',
        name: 'mautic_email_subject_suggestion',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
        priority: 10
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        if ($this->permissions->isAnonymous() || !$this->csrf->isTokenValid(new CsrfToken('mautic_ajax_post', $request->headers->get('X-CSRF-Token', '')))) {
            return new JsonResponse([], Response::HTTP_FORBIDDEN);
        }

        $email = $this->emails->find($id);
        if (null === $email || !$this->permissions->hasEntityAccess(EmailSubjectAdvisor::EDIT_OWN, EmailSubjectAdvisor::EDIT_OTHER, $email->getCreatedBy())) {
            return new JsonResponse([], Response::HTTP_FORBIDDEN);
        }

        $raw     = $request->request->all()['subject'] ?? null;
        $subject = is_string($raw) ? trim($raw, ' ') : '';
        if (!EmailSubjectAdvisor::acceptsSubject($subject)) {
            return new JsonResponse(['message' => $this->translator->trans('mautic.email.subject_advisor.invalid')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $suggestion = $this->gateway->request(EmailSubjectAdvisor::ID, ['subject' => $subject]);
        } catch (AiGatewayException $exception) {
            $reason = match ($exception->getCode()) {
                AiGatewayException::NOT_CONFIGURED, AiGatewayException::CLOUD_DISABLED, AiGatewayException::PROVIDER_LOCATION_DENIED => 'unavailable',
                default => 'failed',
            };

            return new JsonResponse(['message' => $this->translator->trans('mautic.email.subject_advisor.'.$reason)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($suggestion, headers: ['Cache-Control' => 'no-store']);
    }
}
