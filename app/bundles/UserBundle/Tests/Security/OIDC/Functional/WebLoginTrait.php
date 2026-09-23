<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

trait WebLoginTrait
{
    protected KernelBrowser $client;

    /**
     * Log in with password (form login) on 'main' firewall.
     *
     * Note: Mautic uses a shared firewall context 'mautic' for all firewalls.
     * We pass 'mautic' as context so the session token is stored correctly.
     * But this means getFirewallName() returns 'mautic', not 'main'.
     * For OIDC tests that need to distinguish, we manually create tokens.
     */
    private function logInWithPassword(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $user      = $container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        // Create a PostAuthenticationToken with 'main' firewall name
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());

        // Get the session and store token under the shared context 'mautic'
        $session = $container->get('session.factory')->createSession();
        $session->set('_security_mautic', serialize($token));
        $session->save();

        // Set session cookie so it persists across requests
        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        // Also set token storage for immediate use
        $container->get('security.untracked_token_storage')->setToken($token);
    }

    /**
     * Log in with OpenID on 'open_id' firewall.
     *
     * Note: Mautic uses a shared firewall context 'mautic' for all firewalls.
     * We manually create a PostAuthenticationToken with 'open_id' as firewall name
     * and store it under _security_mautic so it persists across requests.
     */
    private function logInWithOpenID(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $user      = $container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        // Create a PostAuthenticationToken with 'open_id' firewall name
        $token = new PostAuthenticationToken($user, 'open_id', $user->getRoles());

        // Get the session and store token under the shared context 'mautic'
        $session = $container->get('session.factory')->createSession();
        $session->set('_security_mautic', serialize($token));
        $session->save();

        // Set session cookie so it persists across requests
        $cookie = new \Symfony\Component\BrowserKit\Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        // Also set token storage for immediate use
        $container->get('security.untracked_token_storage')->setToken($token);
    }

    private function logOut(): void
    {
        $container = $this->client->getContainer();

        // Clear token storage
        $container->get('security.token_storage')->setToken(null);

        // Clear cookies to ensure fresh session
        $this->client->getCookieJar()->clear();
    }
}
