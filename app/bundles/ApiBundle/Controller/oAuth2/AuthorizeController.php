<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Controller\oAuth2;

use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class AuthorizeController extends \FOS\OAuthServerBundle\Controller\AuthorizeController
{
    private readonly TokenStorageInterface $tokenStorage;

    #[Required]
    public function autowireAuthorizeController(
        TokenStorageInterface $tokenStorage,
    ): void {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array<string , mixed> $data Various data to be passed to the twig template
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderAuthorize(array $data, Environment $twig): Response
    {
        $response = $twig->render(
            '@MauticApi/Authorize/oAuth2/authorize.html.twig',
            $data
        );

        return new Response($response);
    }

    #[Route(
        '/oauth/v2/authorize',
        name: 'fos_oauth_server_authorize',
        methods: ['GET|POST'],
        priority: -214
    )]
    public function authorizeAction(Request $request, AuthorizeFormHandler $formHandler, Environment $twig): Response
    {
        // The parent bundle does not care about token being empty.
        if (null === $this->tokenStorage->getToken()) {
            throw new AccessDeniedException('This user does not have access to this section. No token.');
        }

        return parent::authorizeAction($request, $formHandler, $twig);
    }
}
