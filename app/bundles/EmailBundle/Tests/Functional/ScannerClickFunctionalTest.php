<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class ScannerClickFunctionalTest extends MauticMysqlTestCase
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';

    // Not an address any other test uses: a redirect stores its visitor's address, and IpLookupHelper remembers
    // those for the rest of the process, which would skip the do-not-track and bot checks for the same address
    private const IP = '198.51.100.21';

    private const URL = 'https://example.org/offer';

    protected function setUp(): void
    {
        $this->configParams['email_scanner_diagnostics_enabled'] = 'testNothingIsRecordedWhenDiagnosticsAreOff' !== $this->name();
        $this->configParams['anonymize_ip']                      = false;

        parent::setUp();
    }

    public function testAClickOnALinkOfAnEmailIsRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-1');

        $this->click($redirect, $this->createClickthrough($stat));

        $rows = $this->fetchRows();
        $this->assertCount(1, $rows);
        $this->assertSame(ScannerDiagnostic::EVENT_CLICK, $rows[0]['event_type']);
        $this->assertSame($stat->getEmail()?->getId(), (int) $rows[0]['email_id']);
        $this->assertSame($redirect->getId(), (int) $rows[0]['redirect_id']);
        $this->assertNotNull($rows[0]['send_key']);
        $this->assertSame('198.51.100.0/24', $rows[0]['ip_network']);
        $this->assertSame(self::USER_AGENT, $rows[0]['user_agent']);
        $this->assertGreaterThanOrEqual(80, (int) $rows[0]['seconds_after_send']);
        $this->assertNotNull($rows[0]['bot_ratio_flagged']);
    }

    public function testTheClickIsStillHandledExactlyAsBefore(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-2');

        $this->click($redirect, $this->createClickthrough($stat));

        $this->assertInstanceOf(Hit::class, $this->em->getRepository(Hit::class)->findOneBy(['url' => self::URL]), 'the click hit is recorded');
        $this->assertCount(1, $this->fetchRows());
    }

    public function testSeveralLinksOfOneSendAreToldApartAndShareTheSendKey(): void
    {
        $stat     = $this->createStat();
        $first    = $this->createRedirect(self::URL, 'click-redirect-3');
        $second   = $this->createRedirect('https://example.org/other', 'click-redirect-4');
        $ct       = $this->createClickthrough($stat);

        $this->click($first, $ct);
        $this->click($second, $ct);

        $rows = $this->fetchRows();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]['send_key'], $rows[1]['send_key']);
        $this->assertSame($rows[0]['ip_hash'], $rows[1]['ip_hash']);
        $this->assertSame([$first->getId(), $second->getId()], array_map(intval(...), array_column($rows, 'redirect_id')));
    }

    public function testAHoneypotHitAndAClickOfTheSameSendCanBeJoined(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-5');

        $this->client->request(Request::METHOD_GET, '/email/check/'.$stat->getTrackingHash(), [], [], $this->getServer());
        $this->assertResponseIsSuccessful();

        $this->click($redirect, $this->createClickthrough($stat));

        $rows = $this->fetchRows();
        $this->assertSame([ScannerDiagnostic::EVENT_HONEYPOT, ScannerDiagnostic::EVENT_CLICK], array_column($rows, 'event_type'));
        $this->assertSame($rows[0]['send_key'], $rows[1]['send_key']);
        $this->assertNull($rows[0]['redirect_id']);
        $this->assertNotNull($rows[1]['redirect_id']);
    }

    public function testNothingIsRecordedWhenDiagnosticsAreOff(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-6');

        $this->click($redirect, $this->createClickthrough($stat));

        $this->assertCount(0, $this->fetchRows());
        $this->assertInstanceOf(Hit::class, $this->em->getRepository(Hit::class)->findOneBy(['url' => self::URL]), 'the click is handled as it always was');
    }

    public function testAContactWhoTurnedTrackingOffIsNotRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-7');
        self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($stat->getLead(), false, 'test');

        $this->click($redirect, $this->createClickthrough($stat));

        $this->assertCount(0, $this->fetchRows());
    }

    public function testAClickOfASendThatDoesNotExistIsRedirectedAndNotRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-8');

        $this->click($redirect, $this->createClickthrough($stat, 'a-send-that-does-not-exist'));

        $this->assertCount(0, $this->fetchRows());
    }

    public function testAClickWithoutAClickthroughIsRedirectedAndNotRecorded(): void
    {
        $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-9');

        $this->click($redirect, null);

        $this->assertCount(0, $this->fetchRows());
    }

    public function testAClickWithAClickthroughThatDoesNotDecodeIsRedirectedAndNotRecorded(): void
    {
        $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-10');

        $this->click($redirect, 'not-a-clickthrough');

        $this->assertCount(0, $this->fetchRows());
    }

    public function testAClickthroughWithoutAnEmailIsNotRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-11');

        // What a link of a text message looks like: it has a send, but not of an email
        $this->click($redirect, ClickthroughHelper::encodeArrayForUrl([
            'source'  => ['sms', 1],
            'stat'    => $stat->getTrackingHash(),
            'lead'    => $stat->getLead()?->getId(),
            'channel' => ['sms' => 1],
        ]));

        $this->assertCount(0, $this->fetchRows());
    }

    public function testARequestWithDoNotTrackIsRedirectedAndNotRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-12');

        $this->click($redirect, $this->createClickthrough($stat), ['HTTP_DNT' => '1']);

        $this->assertCount(0, $this->fetchRows());
    }

    public function testAHeadRequestIsRedirectedAndNotRecorded(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect(self::URL, 'click-redirect-13');

        $this->click($redirect, $this->createClickthrough($stat), [], Request::METHOD_HEAD);

        $this->assertCount(0, $this->fetchRows());
    }

    /**
     * @param array<string, string> $server
     */
    private function click(Redirect $redirect, ?string $clickthrough, array $server = [], string $method = Request::METHOD_GET): void
    {
        $this->logoutUser();
        $this->client->followRedirects(false);
        $this->client->request($method, '/r/'.$redirect->getRedirectId().(null === $clickthrough ? '' : '?ct='.$clickthrough), [], [], $server + $this->getServer());

        $response = $this->client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($redirect->getUrl(), $response->getTargetUrl(), 'the visitor always gets to the link');
    }

    /**
     * @return array<string, string>
     */
    private function getServer(): array
    {
        return [
            'HTTP_USER_AGENT' => self::USER_AGENT,
            'REMOTE_ADDR'     => self::IP,
        ];
    }

    private function createClickthrough(Stat $stat, ?string $trackingHash = null): string
    {
        $emailId = $stat->getEmail()?->getId();

        return ClickthroughHelper::encodeArrayForUrl([
            'source'  => ['email', $emailId],
            'email'   => $emailId,
            'stat'    => $trackingHash ?? $stat->getTrackingHash(),
            'lead'    => $stat->getLead()?->getId(),
            'channel' => ['email' => $emailId],
        ]);
    }

    private function createStat(): Stat
    {
        $contact = new Lead();
        $contact->setEmail('clicked@example.com');
        $this->em->persist($contact);

        $email = new Email();
        $email->setName('Scanner click');
        $email->setSubject('Scanner click');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress('clicked@example.com');
        $stat->setTrackingHash('scanner-click-hash');
        $stat->setDateSent(new \DateTime('-80 seconds'));
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function createRedirect(string $url, string $redirectId): Redirect
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId($redirectId);
        $this->em->persist($redirect);
        $this->em->flush();

        return $redirect;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME.' ORDER BY id');
    }
}
