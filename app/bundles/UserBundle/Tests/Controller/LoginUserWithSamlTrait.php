<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\HttpFoundation\Session\SessionFactory;

trait LoginUserWithSamlTrait
{
    private function loginUserWithSaml(User $user): void
    {
        $firewallContext = 'mautic';
        $token           = new TestBrowserToken($user->getRoles(), $user, $firewallContext);
        $container       = $this->getContainer();
        $container->get('security.untracked_token_storage')->setToken($token);

        $session = self::getContainer()->get('session.factory')->createSession();
        $session->set('samlsso', true);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $sessionFactory = $this->createMock(SessionFactory::class);
        $sessionFactory->expects($this->any())
            ->method('createSession')
            ->willReturn($session);

        self::getContainer()->set('session.factory', $sessionFactory);
    }
}
