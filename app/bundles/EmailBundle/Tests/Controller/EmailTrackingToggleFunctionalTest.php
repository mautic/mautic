<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * An email can turn tracking of its opens and clicks off, whatever the global tracking settings are.
 */
final class EmailTrackingToggleFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const ANALYTICS_CODE = '<script>/* analytics-code */</script>';

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

    #[DataProvider('trackingProvider')]
    public function testSentEmailFollowsItsTrackingOption(bool $trackingEnabled): void
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

        if ($trackingEnabled) {
            $this->assertStringContainsString('/r/', $link[1], 'the link goes through a tracked link');
            $this->assertMatchesRegularExpression('#/email/[^"/]+\.gif#', $body, 'the open pixel is added');
        } else {
            $this->assertSame('https://example.com/offer', $link[1], 'the link goes straight to its page');
            $this->assertDoesNotMatchRegularExpression('#/email/[^"/]+\.gif#', $body, 'no open pixel is added');
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
        $this->client->request(Request::METHOD_POST, '/api/emails/new', [
            'name'            => 'API tracking',
            'subject'         => 'API tracking',
            'emailType'       => 'template',
            'customHtml'      => '<html><body>Hi</body></html>',
            'trackingEnabled' => 0,
        ]);
        $this->assertResponseStatusCodeSame(201, (string) $this->client->getResponse()->getContent());
        $email = json_decode((string) $this->client->getResponse()->getContent(), true)['email'];
        $this->assertFalse($email['trackingEnabled']);

        $this->client->request(Request::METHOD_PUT, sprintf('/api/emails/%d/edit', $email['id']), [
            'name'       => 'API tracking',
            'subject'    => 'API tracking',
            'emailType'  => 'template',
            'customHtml' => '<html><body>Hi</body></html>',
            'language'   => 'en',
        ]);
        $this->assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $this->assertTrue(json_decode((string) $this->client->getResponse()->getContent(), true)['email']['trackingEnabled'], 'a PUT without the option goes back to tracking on');
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
