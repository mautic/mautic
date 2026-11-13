<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

final class KeepAliveControllerTest extends MauticMysqlTestCase
{
    public function testKeepAliveRefreshesAuthenticatedSession(): void
    {
        ClockMock::register(MetadataBag::class);
        $now = time();
        ClockMock::withClockMock($now);

        try {
            $this->client->request('GET', '/s/dashboard');
            self::assertResponseIsSuccessful();

            $session   = $this->client->getRequest()->getSession();
            $sessionId = $session->getId();
            $token     = $session->get('_security_mautic');
            self::assertNotNull($token);

            ClockMock::withClockMock($now + 2);

            $this->client->request('GET', '/s/keep-alive', [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
            self::assertResponseIsSuccessful();
            self::assertSame('', $this->client->getResponse()->getContent());

            $session = $this->client->getRequest()->getSession();
            self::assertSame($sessionId, $session->getId());
            self::assertSame($token, $session->get('_security_mautic'));

            $this->client->request('GET', '/s/dashboard');
            self::assertResponseIsSuccessful();
            // getLastUsed() reports the timestamp persisted by the previous request.
            self::assertGreaterThan($now, $this->client->getRequest()->getSession()->getMetadataBag()->getLastUsed());
            self::assertSelectorNotExists('input#username');
        } finally {
            ClockMock::withClockMock(false);
        }
    }
}
