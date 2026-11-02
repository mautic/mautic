<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

trait LoginUserWithSamlTrait
{
    private function loginUserWithSaml(User $user): void
    {
        $firewallContext = 'mautic';
        $token           = new TestBrowserToken($user->getRoles(), $user, $firewallContext);
        $container       = $this->getContainer();
        $container->get('security.untracked_token_storage')->setToken($token);

        $session = $container->get(SessionFactoryInterface::class)->createSession();
        $session->set('samlsso', true);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
