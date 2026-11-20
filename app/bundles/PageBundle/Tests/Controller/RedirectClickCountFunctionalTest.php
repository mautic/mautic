<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A click from a visitor Mautic doesn't track still redirects and counts, but nothing links it to the visitor.
 */
final class RedirectClickCountFunctionalTest extends MauticMysqlTestCase
{
    private const EMAIL_ID     = 5;
    private const CONTACT_MAIL = 'clicker@example.com';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'AggregateAnalyticsOff');

        parent::setUp();
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function privacySignalProvider(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1']];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('privacySignalProvider')]
    public function testUntrackedClickIsCountedWithoutLinkingItToTheVisitor(array $server): void
    {
        [$redirect, $contact] = $this->createLink();

        $target = $this->click($redirect, $contact, $server + ['REMOTE_ADDR' => '203.0.113.40']);

        // No contact data is put into a URL that goes to another site
        $this->assertStringContainsStringIgnoringCase('contactfield', urldecode($target));
        $this->assertSame([1, 0], $this->getRedirectCounts($redirect));
        $this->assertSame([1, 0], $this->getTrackableCounts($redirect));
        $this->assertCount(0, $this->em->getRepository(Hit::class)->findBy(['redirect' => $redirect->getId()]));
        $this->assertNotInstanceOf(IpAddress::class, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => '203.0.113.40']), 'the visitor\'s IP address is not stored');
        $cookies = array_map(static fn (\Symfony\Component\HttpFoundation\Cookie $cookie): string => $cookie->getName(), $this->client->getResponse()->headers->getCookies());
        $this->assertSame([], array_intersect(['mautic_device_id', 'mtc_id', 'mautic_referer_id'], $cookies));
    }

    public function testTrackedClickIsStillLinkedToTheContact(): void
    {
        [$redirect, $contact] = $this->createLink();

        $target = $this->click($redirect, $contact, []);

        // Contact tokens in the URL are still replaced for a tracked contact
        $this->assertStringNotContainsStringIgnoringCase('contactfield', urldecode($target));
        $this->assertCount(1, $this->em->getRepository(Hit::class)->findBy(['redirect' => $redirect->getId()]));
    }

    public function testTrackedClickIsCounted(): void
    {
        [$redirect, $contact] = $this->createLink();

        $this->click($redirect, $contact, []);

        $this->assertSame([1, 1], $this->getRedirectCounts($redirect));
    }

    public function testTrackedClickIsNotCountedWhileAggregateAnalyticsOff(): void
    {
        [$redirect, $contact] = $this->createLink();

        $this->click($redirect, $contact, []);

        $this->assertSame([0, 0], $this->getRedirectCounts($redirect));
        $this->assertSame([0, 0], $this->getTrackableCounts($redirect));
        $this->assertCount(1, $this->em->getRepository(Hit::class)->findBy(['redirect' => $redirect->getId()]), 'the click is still recorded on the contact');
    }

    public function testClickFromABotIsNotCounted(): void
    {
        [$redirect, $contact] = $this->createLink();

        $this->click($redirect, $contact, [
            'HTTP_DNT'        => '1',
            'REMOTE_ADDR'     => '203.0.113.41',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $this->assertSame([0, 0], $this->getRedirectCounts($redirect));
    }

    public function testUntrackedClickIsNotCountedWhileAggregateAnalyticsOff(): void
    {
        [$redirect, $contact] = $this->createLink();

        $this->click($redirect, $contact, ['HTTP_DNT' => '1']);

        $this->assertSame([0, 0], $this->getRedirectCounts($redirect));
        $this->assertSame([0, 0], $this->getTrackableCounts($redirect));
    }

    public function testUntrackedClickWithoutClickthroughIsNotCounted(): void
    {
        [$redirect] = $this->createLink();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/r/'.$redirect->getRedirectId(), [], [], ['HTTP_DNT' => '1']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->assertSame([0, 0], $this->getRedirectCounts($redirect));
    }

    public function testUntrackedClickWithABrokenClickthroughIsNotCounted(): void
    {
        [$redirect] = $this->createLink();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/r/'.$redirect->getRedirectId(), ['ct' => 'not-a-clickthrough'], [], ['HTTP_DNT' => '1']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->assertSame([0, 0], $this->getRedirectCounts($redirect));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function visitorProvider(): iterable
    {
        yield 'tracked visitor' => [[]];
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('visitorProvider')]
    public function testClickthroughWithAStringChannelStillRedirects(array $server): void
    {
        [$redirect, $contact] = $this->createLink();
        $ct                   = ClickthroughHelper::encodeArrayForUrl(['channel' => 'email', 'lead' => $contact->getId()]);

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/r/'.$redirect->getRedirectId(), ['ct' => $ct], [], $server);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function nestedClickthroughProvider(): iterable
    {
        yield 'nested channel id' => [['channel' => ['email', [self::EMAIL_ID]]]];
        yield 'nested single channel' => [['channel' => ['email' => [self::EMAIL_ID]]]];
        yield 'nested source id' => [['source' => ['email', [self::EMAIL_ID]]]];
        yield 'nested source name' => [['source' => [['email'], self::EMAIL_ID]]];
    }

    /**
     * @param array<string, mixed> $clickthrough
     */
    #[DataProvider('nestedClickthroughProvider')]
    public function testUntrackedClickWithANestedClickthroughStillRedirects(array $clickthrough): void
    {
        [$redirect] = $this->createLink();

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/r/'.$redirect->getRedirectId(), ['ct' => ClickthroughHelper::encodeArrayForUrl($clickthrough)], [], ['HTTP_DNT' => '1']);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertSame([0, 0], $this->getTrackableCounts($redirect));
    }

    /**
     * @param array<string, string> $server
     */
    private function click(Redirect $redirect, Lead $contact, array $server): string
    {
        $ct = ClickthroughHelper::encodeArrayForUrl([
            'source' => ['email', self::EMAIL_ID],
            'email'  => self::EMAIL_ID,
            'lead'   => $contact->getId(),
        ]);

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/r/'.$redirect->getRedirectId(), ['ct' => $ct], [], $server);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND, 'the link works either way');

        return (string) $this->client->getResponse()->headers->get('Location');
    }

    /**
     * @return array{int, int}
     */
    private function getRedirectCounts(Redirect $redirect): array
    {
        $this->em->clear();
        $redirect = $this->em->find(Redirect::class, $redirect->getId());
        $this->assertInstanceOf(Redirect::class, $redirect);

        return [(int) $redirect->getHits(), (int) $redirect->getUniqueHits()];
    }

    /**
     * @return array{int, int}
     */
    private function getTrackableCounts(Redirect $redirect): array
    {
        $this->em->clear();
        $trackable = $this->em->getRepository(Trackable::class)->findOneBy(['redirect' => $redirect->getId(), 'channel' => 'email', 'channelId' => self::EMAIL_ID]);
        $this->assertInstanceOf(Trackable::class, $trackable);

        return [(int) $trackable->getHits(), (int) $trackable->getUniqueHits()];
    }

    /**
     * @return array{Redirect, Lead}
     */
    private function createLink(): array
    {
        $contact = new Lead();
        $contact->setEmail(self::CONTACT_MAIL);
        $this->em->persist($contact);

        $redirect = new Redirect();
        $redirect->setUrl('https://example.com/offer?who={contactfield=email}');
        $redirect->setRedirectId('untrackedclick000000000');
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setRedirect($redirect);
        $trackable->setChannel('email');
        $trackable->setChannelId(self::EMAIL_ID);
        $trackable->setHits(0);
        $trackable->setUniqueHits(0);
        $this->em->persist($trackable);
        $this->em->flush();

        $this->logoutUser();

        return [$redirect, $contact];
    }
}
