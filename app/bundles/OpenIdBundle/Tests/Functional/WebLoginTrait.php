<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Functional;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

trait WebLoginTrait
{
    /**
     * @inerhitDoc
     */
    protected static $container;

    protected KernelBrowser $client;

    private function logInWithPassword(string $username = 'linked_admin'): void
    {
        $session = self::$container->get('session');
        $user    = self::$container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        $token = new UsernamePasswordToken($user, null, 'main', ['ROLE_ADMIN']);
        $session->set('_security_mautic', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    private function logInWithOpenID(string $username = 'linked_admin'): void
    {
        $session = self::$container->get('session');
        $user    = self::$container->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]);
        \assert($user instanceof User);

        $token = new PostAuthenticationGuardToken($user, 'open_id', $user->getRoles());
        $token->setAuthenticated(true);
        $session->set('_security_mautic', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
