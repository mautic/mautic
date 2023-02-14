<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Controller;

use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\OpenIdBundle\Entity\SubjectId;
use Mautic\OpenIdBundle\Exception\AuthorizationRequestFailedException;
use Mautic\OpenIdBundle\Service\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class SecurityController extends AbstractController
{
    private Settings $settings;
    private ClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(Settings $settings, ClientInterface $client, LoggerInterface $logger)
    {
        $this->settings     = $settings;
        $this->client       = $client;
        $this->logger       = $logger;
    }

    public function loginAction(): RedirectResponse
    {
        if (!$this->settings->isEnabled()) {
            return $this->redirectToRoute('login');
        }

        try {
            if ($authenticatedRedirect = $this->client->authenticate()) {
                return $authenticatedRedirect;
            }
        } catch (AuthorizationRequestFailedException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        throw new AuthenticationException('OpenID Connect authentication failed.');
    }

    public function loginCheckAction(): void
    {
        // noop
    }

    public function requiredAction(): Response
    {
        if (!$this->settings->isEnabled() || !($user = $this->getUser())) {
            return $this->redirectToRoute('login');
        }

        if ($this->getDoctrine()->getRepository(SubjectId::class)->findOneBy(['user' => $user])) {
            return $this->redirectToRoute('open_id_login');
        }

        $content = $this->renderView('OpenIdBundle:security:required.html.twig', [
            'parameters' => $this->settings,
        ]);

        return $this->render('OpenIdBundle:Security:require_openid.html.php', ['content' => $content]);
    }
}
