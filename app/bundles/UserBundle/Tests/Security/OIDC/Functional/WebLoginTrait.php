<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Functional;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

trait WebLoginTrait
{
    protected KernelBrowser $client;

    private function logInWithPassword(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $session   = $container->get('session');
        $user      = $container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        $token = new UsernamePasswordToken($user, 'main', ['ROLE_ADMIN']);
        $session->set('_security_mautic', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    private function logInWithOpenID(string $username = 'linked_admin'): void
    {
        $container = $this->client->getContainer();
        $session   = $container->get('session');
        $user      = $container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        $token = new PostAuthenticationToken($user, 'open_id', $user->getRoles());
        $session->set('_security_mautic', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
