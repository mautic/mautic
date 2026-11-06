<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class KeepAliveControllerTest extends MauticMysqlTestCase
{
    public function testKeepAliveRefreshesAuthenticatedSession(): void
    {
        $this->client->request('GET', '/s/dashboard');
        self::assertResponseIsSuccessful();

        $session   = $this->client->getRequest()->getSession();
        $sessionId = $session->getId();
        $lastUsed  = $session->getMetadataBag()->getLastUsed();
        $token     = $session->get('_security_mautic');
        self::assertNotNull($token);

        // Session activity timestamps have one-second resolution.
        sleep(2);

        $this->client->request('GET', '/s/keep-alive', [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertResponseIsSuccessful();
        self::assertSame('', $this->client->getResponse()->getContent());

        $session = $this->client->getRequest()->getSession();
        self::assertSame($sessionId, $session->getId());
        self::assertGreaterThan($lastUsed, $session->getMetadataBag()->getLastUsed());
        self::assertSame($token, $session->get('_security_mautic'));

        $this->client->request('GET', '/s/dashboard');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input#username');
    }
}
