<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

final class KeepAliveControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // Register before the parent logs in and creates the first session.
        ClockMock::register(MetadataBag::class);
        parent::setUp();
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testKeepAliveRefreshesAuthenticatedSession(): void
    {
        $now = time();
        ClockMock::withClockMock($now);

        try {
            $this->client->request('GET', '/s/dashboard');
            self::assertResponseIsSuccessful();

            $session   = $this->client->getRequest()->getSession();
            $sessionId = $session->getId();
            $token     = $session->get('_security_mautic');
            $this->assertNotNull($token);

            ClockMock::withClockMock($now + 2);

            $this->client->request('GET', '/s/keep-alive', [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
            self::assertResponseIsSuccessful();
            $this->assertSame('', $this->client->getResponse()->getContent());

            $session = $this->client->getRequest()->getSession();
            $this->assertSame($sessionId, $session->getId());
            $this->assertSame($token, $session->get('_security_mautic'));

            $this->client->request('GET', '/s/dashboard');
            self::assertResponseIsSuccessful();
            // getLastUsed() reports the timestamp persisted by the previous request.
            $this->assertGreaterThan($now, $this->client->getRequest()->getSession()->getMetadataBag()->getLastUsed());
            self::assertSelectorNotExists('input#username');
        } finally {
            ClockMock::withClockMock(false);
        }
    }
}
