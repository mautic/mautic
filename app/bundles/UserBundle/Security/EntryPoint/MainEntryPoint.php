<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\EntryPoint;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class MainEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator, private bool $samlEnabled)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // as per https://docs.mautic.org/en/5.x/authentication/authentication.html#logging-in
        // log in always as SAML for all requests.
        // todo: task for testers: enable saml, and check if regular login page is available
        $route = (string) $request->attributes->get('_route');
        if ($this->samlEnabled && 'login' !== $route && 'mautic_user_logincheck' !== $route) {
            return new RedirectResponse($this->urlGenerator->generate('lightsaml_sp.login'));
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }
}
