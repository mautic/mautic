<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * An email can turn tracking of its opens and clicks off, whatever the global tracking settings are.
 */
final class EmailTrackingToggleFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const ANALYTICS_CODE = '<script>/* analytics-code */</script>';

    private const API_EMAIL_NAME = 'API tracking';

    private const API_EDIT_URL = '/api/emails/%d/edit';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['google_analytics'] = self::ANALYTICS_CODE;

        parent::setUp();
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function trackingProvider(): iterable
    {
        yield 'tracking on' => [true];
        yield 'tracking off' => [false];
    }

    /**
     * @return iterable<string, array{bool, array<string, string|null>, string|null}>
     */
    public static function sentLinkProvider(): iterable
    {
        // The email form saves every UTM field, empty ones included
        $emptyUtmTags = ['utmSource' => '', 'utmMedium' => null, 'utmCampaign' => '', 'utmContent' => ''];

        yield 'tracking on' => [true, $emptyUtmTags, null];
        yield 'tracking off' => [false, $emptyUtmTags, 'https://example.com/offer'];
        yield 'tracking off with a UTM tag' => [false, ['utmSource' => 'newsletter'] + $emptyUtmTags, 'https://example.com/offer?utm_source=newsletter'];
    }

    /**
     * @param array<string, string|null> $utmTags
     */
    #[DataProvider('sentLinkProvider')]
    public function testSentEmailFollowsItsTrackingOption(bool $trackingEnabled, array $utmTags, ?string $expectedLink): void
    {
        $segment = $this->createSegment('tracking-toggle', []);
        $contact = $this->createLead('', '', 'toggle@example.com');
        $this->createListLead($segment, $contact);

        $email = $this->createEmail('Tracking toggle');
        $email->setSubject('Tracking toggle');
        $email->setEmailType('list');
        $email->setLists([$segment]);
        $email->setTemplate('blank');
        $email->setCustomHtml('<!DOCTYPE html><html><body><a href="https://example.com/offer">offer</a></body></html>');
        $email->setUtmTags($utmTags);
        $email->setTrackingEnabled($trackingEnabled);
        $this->em->persist($email);
        $this->em->flush();
        $this->em->clear();

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', ['id' => $email->getId(), 'pending' => 1]);
        $this->assertResponseIsSuccessful();

        $message = self::getMailerMessagesByToAddress('toggle@example.com')[0] ?? null;
        $this->assertInstanceOf(MauticMessage::class, $message);
        $body = quoted_printable_decode($message->getBody()->bodyToString());

        preg_match('/<a href="([^"]*)">offer<\/a>/', $body, $link);
        $this->assertArrayHasKey(1, $link, $body);

        $this->em->clear();
        $trackables = $this->em->getRepository(Trackable::class)->findBy(['channel' => 'email', 'channelId' => $email->getId()]);
        $redirects  = $this->em->getRepository(Redirect::class)->findBy(['url' => 'https://example.com/offer']);

        if ($trackingEnabled) {
            $this->assertStringContainsString('/r/', $link[1], 'the link goes through a tracked link');
            $this->assertMatchesRegularExpression('#/email/[^"/]+\.gif#', $body, 'the open pixel is added');
            $this->assertCount(1, $trackables);
            $this->assertCount(1, $redirects);
        } else {
            $this->assertSame($expectedLink, $link[1], 'the link goes straight to its page, without a stray "?"');
            $this->assertDoesNotMatchRegularExpression('#/email/[^"/]+\.gif#', $body, 'no open pixel is added');
            $this->assertSame([], $trackables, 'no trackable row is created for an untracked link');
            $this->assertSame([], $redirects, 'no redirect row is created for an untracked link');
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function openProvider(): iterable
    {
        yield 'open pixel' => ['/email/%s.gif'];
        yield 'view in browser' => ['/email/view/%s'];
    }

    #[DataProvider('openProvider')]
    public function testOpenIsNotRecordedForAnEmailWithTrackingOff(string $uri): void
    {
        $stat = $this->createSentStat(false);

        $this->client->request(Request::METHOD_GET, sprintf($uri, $stat->getTrackingHash()), [], [], ['REMOTE_ADDR' => '203.0.113.90']);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('analytics-code', (string) $this->client->getResponse()->getContent());

        $this->em->clear();
        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead());
    }

    public function testOpenIsRecordedForAnEmailWithTrackingOn(): void
    {
        $stat = $this->createSentStat(true);

        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], ['REMOTE_ADDR' => '203.0.113.91']);
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertTrue($stat->getIsRead());
    }

    #[DataProvider('trackingProvider')]
    public function testPublicPreviewGetsTheAnalyticsCodeOnlyWithTrackingOn(bool $trackingEnabled): void
    {
        $email = $this->createEmail('Preview');
        $email->setSubject('Preview');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setCustomHtml('<html><head></head><body>Hi</body></html>');
        $email->setPublicPreview(true);
        $email->setTrackingEnabled($trackingEnabled);
        $this->em->persist($email);
        $this->em->flush();
        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/email/preview/'.$email->getId());
        $this->assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();
        if ($trackingEnabled) {
            $this->assertStringContainsString('analytics-code', $content);
        } else {
            $this->assertStringNotContainsString('analytics-code', $content);
        }
    }

    public function testCloneKeepsTheTrackingOption(): void
    {
        $email = new Email();
        $email->setTrackingEnabled(false);

        $this->assertFalse((clone $email)->getTrackingEnabled());
    }

    public function testTrackingOptionThroughTheApi(): void
    {
        $this->client->request(Request::METHOD_POST, '/api/emails/new', $this->apiEmailPayload(['trackingEnabled' => 0]));
        $this->assertResponseStatusCodeSame(201, (string) $this->client->getResponse()->getContent());
        $emailId = $this->apiEmail()['id'];
        $this->assertFalse($this->apiEmail()['trackingEnabled']);

        $this->client->request(Request::METHOD_PUT, sprintf(self::API_EDIT_URL, $emailId), $this->apiEmailPayload(['language' => 'en']));
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertFalse($this->apiEmail()['trackingEnabled'], 'a PUT without the option keeps tracking off');

        $this->client->request(Request::METHOD_PATCH, sprintf(self::API_EDIT_URL, $emailId), ['subject' => 'API tracking patched']);
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertSame('API tracking patched', $this->apiEmail()['subject']);
        $this->assertFalse($this->apiEmail()['trackingEnabled'], 'a PATCH without the option keeps tracking off');

        $this->client->request(Request::METHOD_PUT, sprintf(self::API_EDIT_URL, $emailId), $this->apiEmailPayload(['language' => 'en', 'trackingEnabled' => 1]));
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertTrue($this->apiEmail()['trackingEnabled'], 'a PUT with the option turns tracking on');

        $this->client->request(Request::METHOD_PATCH, sprintf(self::API_EDIT_URL, $emailId), ['subject' => self::API_EMAIL_NAME]);
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertTrue($this->apiEmail()['trackingEnabled'], 'a PATCH without the option keeps tracking on');
    }

    public function testEmailFormHasTheOptionAndDetailsShowItIsOff(): void
    {
        $email = $this->createEmail('Form tracking');
        $email->setSubject('Form tracking');
        $email->setEmailType('template');
        $email->setTemplate('blank');
        $email->setCustomHtml('<html><body>Hi</body></html>');
        $email->setTrackingEnabled(false);
        $this->em->persist($email);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/edit/'.$email->getId());
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('input[name="emailform[trackingEnabled]"]'));
        $this->assertSame('0', $crawler->filter('input[name="emailform[trackingEnabled]"]:checked')->attr('value'));

        $this->client->request(Request::METHOD_GET, '/s/emails/view/'.$email->getId());
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Opens and clicks aren&#039;t recorded for this email.', (string) $this->client->getResponse()->getContent());
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function apiEmailPayload(array $overrides = []): array
    {
        return $overrides + [
            'name'       => self::API_EMAIL_NAME,
            'subject'    => self::API_EMAIL_NAME,
            'emailType'  => 'template',
            'customHtml' => '<html><body>Hi</body></html>',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiEmail(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true)['email'];
    }

    private function createSentStat(bool $trackingEnabled): Stat
    {
        $contact = new Lead();
        $contact->setEmail('opened@example.com');
        $this->em->persist($contact);

        $email = $this->createEmail('Opened');
        $email->setSubject('Opened');
        $email->setEmailType('template');
        $email->setTrackingEnabled($trackingEnabled);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress('opened@example.com');
        $stat->setTrackingHash('tracking_toggle_'.($trackingEnabled ? 'on' : 'off'));
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $this->em->persist($stat);
        $this->em->flush();

        $this->logoutUser();

        return $stat;
    }
}
