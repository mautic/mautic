<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Functional\EventListener;

use GuzzleHttp\Psr7\Response;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Api\AbstractNotificationApi;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\EventListener\CampaignSubscriber;
use Mautic\NotificationBundle\Tests\NotificationTrait;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

class CampaignSubscriberTest extends MauticMysqlTestCase
{
    use NotificationTrait;

    /**
     * @var string
     */
    private const REST_API_ID = 'restApiID';

    /**
     * @var string
     */
    private const API_ID = 'apiID';

    /**
     * @var string
     */
    private const ONESIGNAL_API_BASE_URL = 'https://onesignal.com/api/v1/notifications';

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportMock = $this->getMockHandler(static::getContainer());
        $this->setupIntegration(static::getContainer(), $this->em, self::API_ID, self::REST_API_ID);
    }

    public function testLeadNotContactable(): void
    {
        $notification = $this->createNotification($this->em);
        $this->em->flush();

        $campaign  = $this->createCampaign($this->em);
        $leadOne   = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo   = $this->createLeadInCampaign($campaign, ['web-2']);
        $leadThree = $this->createLeadInCampaign($campaign, ['web-3a', 'web-3b']);
        $event     = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->createDoNotContact($leadOne, $notification);

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-2', 'web-3a', 'web-3b'], $notification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));
        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogFailed($event, $leadOne, 'Contact is not contactable on the Web Notification channel.');
        $this->assertEventLogPassed($event, $leadTwo);
        $this->assertEventLogPassed($event, $leadThree);
    }

    public function testNotificationMissing(): void
    {
        $notification = $this->createNotification($this->em);
        $this->em->flush();

        $campaign = $this->createCampaign($this->em);
        $leadOne  = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo  = $this->createLeadInCampaign($campaign, ['web-2']);
        $event    = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');
        $event->setProperties([]);

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $reason = 'The specified Web Notification entity does not exist.';
        $this->assertEventLogFailed($event, $leadOne, $reason);
        $this->assertEventLogFailed($event, $leadTwo, $reason);
    }

    public function testNotificationUnpublished(): void
    {
        $notification = $this->createNotification($this->em);
        $notification->setIsPublished(false);
        $this->em->flush();

        $campaign = $this->createCampaign($this->em);
        $leadOne  = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo  = $this->createLeadInCampaign($campaign, ['web-2']);
        $event    = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $reason = 'The specified Web Notification is unpublished.';
        $this->assertEventLogFailed($event, $leadOne, $reason);
        $this->assertEventLogFailed($event, $leadTwo, $reason);
    }

    public function testNotificationWithEmptyPushIds(): void
    {
        $notification = $this->createNotification($this->em);
        $this->em->flush();

        $campaign = $this->createCampaign($this->em);
        $leadOne  = $this->createLeadInCampaign($campaign, []);
        $leadTwo  = $this->createLeadInCampaign($campaign, []);
        $event    = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $reason = 'The contact has not subscribed to the Web Notification channel.';
        $this->assertEventLogFailed($event, $leadOne, $reason);
        $this->assertEventLogFailed($event, $leadTwo, $reason);
    }

    public function testWebNotificationsAreSent(): void
    {
        $notification = $this->createNotification($this->em);
        $this->em->flush();

        $campaign  = $this->createCampaign($this->em);
        $leadOne   = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo   = $this->createLeadInCampaign($campaign, ['mobile-1'], true);
        $leadThree = $this->createLeadInCampaign($campaign, ['web-2a', 'web-2b']);
        $leadFour  = $this->createLeadInCampaign($campaign, ['web-3']);
        $event     = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-1', 'web-2a', 'web-2b', 'web-3'], $notification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));
        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogPassed($event, $leadOne);
        $this->assertEventLogFailed($event, $leadTwo, 'The contact has not subscribed to the Web Notification channel.');
        $this->assertEventLogPassed($event, $leadThree);
        $this->assertEventLogPassed($event, $leadFour);
    }

    public function testMobileNotificationsAreSent(): void
    {
        $notification = $this->createNotification($this->em);
        $notification->setMobile(true);
        $this->em->flush();

        $campaign  = $this->createCampaign($this->em);
        $leadOne   = $this->createLeadInCampaign($campaign, ['mobile-1'], true);
        $leadTwo   = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadThree = $this->createLeadInCampaign($campaign, ['mobile-2a', 'mobile-2b'], true);
        $leadFour  = $this->createLeadInCampaign($campaign, ['mobile-3a', 'mobile-3b'], true);
        $event     = $this->createCampaignEvent($campaign, $notification, 'notification.send_mobile_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(
                ['mobile-1', 'mobile-2a', 'mobile-2b', 'mobile-3a', 'mobile-3b'],
                $notification
            ),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));
        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogPassed($event, $leadOne);
        $this->assertEventLogFailed($event, $leadTwo, 'The contact has not subscribed to the Web Notification channel.');
        $this->assertEventLogPassed($event, $leadThree);
        $this->assertEventLogPassed($event, $leadFour);
    }

    public function testWebAndMobileNotificationsAreSent(): void
    {
        $webNotification = $this->createNotification($this->em);
        $webNotification->setHeading('Web heading 1');
        $webNotification->setMessage('Web message 1');

        $mobileNotification = $this->createNotification($this->em);
        $mobileNotification->setHeading('Mobile heading 1');
        $mobileNotification->setMessage('Mobile message 1');
        $mobileNotification->setMobile(true);

        $this->em->flush();

        $campaign    = $this->createCampaign($this->em);
        $leadOne     = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo     = $this->createLeadInCampaign($campaign, ['mobile-1'], true);
        $leadThree   = $this->createLeadInCampaign($campaign, ['mobile-2a', 'mobile-2b'], true);
        $leadFour    = $this->createLeadInCampaign($campaign, ['web-2a', 'web-2b']);
        $webEvent    = $this->createCampaignEvent($campaign, $webNotification, 'notification.send_notification');
        $mobileEvent = $this->createCampaignEvent($campaign, $mobileNotification, 'notification.send_mobile_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-1', 'web-2a', 'web-2b'], $webNotification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['mobile-1', 'mobile-2a', 'mobile-2b'], $mobileNotification),
            'POST',
            self::ONESIGNAL_API_BASE_URL,
            500,
            'Internal server error'
        ));
        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $reason = 'Internal server error (500)';
        $this->assertEventLogPassed($webEvent, $leadOne);
        $this->assertEventLogFailed($mobileEvent, $leadTwo, $reason, true);
        $this->assertEventLogFailed($mobileEvent, $leadThree, $reason, true);
        $this->assertEventLogPassed($webEvent, $leadFour);
    }

    public function testNotificationsWithToken(): void
    {
        $notification = $this->createNotification($this->em);
        $notification->setMessage('Message {contactfield=email}');
        $this->em->flush();

        $campaign = $this->createCampaign($this->em);

        $leadOne = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadOne->setEmail('one@domain.tld');

        $leadTwo = $this->createLeadInCampaign($campaign, ['web-2a', 'web-2b']);
        $leadTwo->setEmail('two@domain.tld');

        $event = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            [
                'include_player_ids' => ['web-1'],
                'contents'           => ['en' => 'Message '.$leadOne->getEmail()],
                'headings'           => ['en' => $notification->getHeading()],
                'app_id'             => self::API_ID,
            ],
            'POST',
            self::ONESIGNAL_API_BASE_URL,
            400,
            'Bad Request'
        ));

        $this->transportMock->append($this->responseDataAssertion(
            [
                'include_player_ids' => ['web-2a', 'web-2b'],
                'contents'           => ['en' => 'Message '.$leadTwo->getEmail()],
                'headings'           => ['en' => $notification->getHeading()],
                'app_id'             => self::API_ID,
            ],
            'POST',
            self::ONESIGNAL_API_BASE_URL,
        ));

        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogFailed($event, $leadOne, 'Bad Request (400)', true);
        $this->assertEventLogPassed($event, $leadTwo);
    }

    public function testWebNotificationsWithUrlAndButtons(): void
    {
        $notification = $this->createNotification($this->em);
        $notification->setUrl('https://some-url.tld');
        $notification->setButton('Some button');
        $this->em->flush();

        $campaign  = $this->createCampaign($this->em);
        $leadOne   = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo   = $this->createLeadInCampaign($campaign, []);
        $leadThree = $this->createLeadInCampaign($campaign, ['web-2']);
        $leadFour  = $this->createLeadInCampaign($campaign, ['web-3a', 'web-3b']);
        $event     = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->createDoNotContact($leadOne, $notification);

        $this->em->flush();
        $this->em->clear();

        $urlThree = $this->convertToTrackedUrl($notification, $leadThree);
        $urlFour  = $this->convertToTrackedUrl($notification, $leadFour);

        $this->transportMock->append($this->responseDataAssertion(
            [
                'include_player_ids' => ['web-2'],
                'contents'           => ['en' => $notification->getMessage()],
                'headings'           => ['en' => $notification->getHeading()],
                'url'                => $urlThree,
                'web_buttons'        => [
                    [
                        'id'   => $notification->getHeading(),
                        'text' => $notification->getButton(),
                        'url'  => $urlThree,
                    ],
                ],
                'app_id'             => self::API_ID,
            ],
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->transportMock->append($this->responseDataAssertion(
            [
                'include_player_ids' => ['web-3a', 'web-3b'],
                'contents'           => ['en' => $notification->getMessage()],
                'headings'           => ['en' => $notification->getHeading()],
                'url'                => $urlFour,
                'web_buttons'        => [
                    [
                        'id'   => $notification->getHeading(),
                        'text' => $notification->getButton(),
                        'url'  => $urlFour,
                    ],
                ],
                'app_id'             => self::API_ID,
            ],
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->triggerCampaigns();

        $this->assertEventLogFailed($event, $leadOne, 'Contact is not contactable on the Web Notification channel.');
        $this->assertEventLogFailed($event, $leadTwo, 'The contact has not subscribed to the Web Notification channel.');
        $this->assertEventLogPassed($event, $leadThree);
        $this->assertEventLogPassed($event, $leadFour);
    }

    public function testMobileNotificationsWithButtonsAndSettings(): void
    {
        $notification = $this->createNotification($this->em);
        $notification->setMobile(true);
        $notification->setButton('Some button');
        $notification->setMobileSettings([
            'ios_subtitle'      => 'iOS Subtitle',
            'android_led_color' => 'FF00DD',
        ]);
        $this->em->flush();

        $campaign = $this->createCampaign($this->em);
        $leadOne  = $this->createLeadInCampaign($campaign, ['mobile-1'], true);
        $leadTwo  = $this->createLeadInCampaign($campaign, ['mobile-2a', 'mobile-2b'], true);
        $event    = $this->createCampaignEvent($campaign, $notification, 'notification.send_mobile_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            [
                'include_player_ids' => ['mobile-1', 'mobile-2a', 'mobile-2b'],
                'contents'           => ['en' => $notification->getMessage()],
                'headings'           => ['en' => $notification->getHeading()],
                'subtitle'           => ['en' => $notification->getMobileSettings()['ios_subtitle']],
                'android_led_color'  => 'FF'.$notification->getMobileSettings()['android_led_color'],
                'buttons'            => [
                    [
                        'id'   => $notification->getHeading(),
                        'text' => $notification->getButton(),
                    ],
                ],
                'app_id'             => self::API_ID,
            ],
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));
        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogPassed($event, $leadOne);
        $this->assertEventLogPassed($event, $leadTwo);
    }

    public function testNotificationsSentInBatches(): void
    {
        $subscriber                                    = new class(static::getContainer()->get('mautic.helper.integration'), static::getContainer()->get('mautic.notification.model.notification'), static::getContainer()->get('mautic.notification.api'), static::getContainer()->get('event_dispatcher'), static::getContainer()->get('mautic.lead.model.dnc'), static::getContainer()->get('translator')) extends CampaignSubscriber {
            protected const MAX_PLAYER_IDS_PER_REQUEST = 2;
        };
        static::getContainer()->set('mautic.notification.campaignbundle.subscriber', $subscriber);

        $notification = $this->createNotification($this->em);
        $this->em->flush();

        $campaign  = $this->createCampaign($this->em);
        $leadOne   = $this->createLeadInCampaign($campaign, ['web-1']);
        $leadTwo   = $this->createLeadInCampaign($campaign, ['mobile-1'], true);
        $leadThree = $this->createLeadInCampaign($campaign, ['web-2a', 'web-2b']);
        $leadFour  = $this->createLeadInCampaign($campaign, ['web-3']);
        $leadFive  = $this->createLeadInCampaign($campaign, ['web-4']);
        $event     = $this->createCampaignEvent($campaign, $notification, 'notification.send_notification');

        $this->em->flush();
        $this->em->clear();

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-1', 'web-2a'], $notification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-2b', 'web-3'], $notification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->transportMock->append($this->responseDataAssertion(
            $this->getExpectedResponsePushIds(['web-4'], $notification),
            'POST',
            self::ONESIGNAL_API_BASE_URL
        ));

        $this->transportMock->append($this->noMoreRequestAssertion());

        $this->triggerCampaigns();

        $this->assertEventLogPassed($event, $leadOne);
        $this->assertEventLogFailed($event, $leadTwo, 'The contact has not subscribed to the Web Notification channel.');
        $this->assertEventLogPassed($event, $leadThree);
        $this->assertEventLogPassed($event, $leadFour);
        $this->assertEventLogPassed($event, $leadFive);
    }

    /**
     * @param string[] $pushIds
     */
    private function createLeadInCampaign(Campaign $campaign, array $pushIds, bool $mobile = false): Lead
    {
        $lead = new Lead();

        foreach ($pushIds as $pushId) {
            $lead->addPushIDEntry($pushId, true, $mobile);
        }

        $this->em->persist($lead);

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        return $lead;
    }

    private function createCampaignEvent(Campaign $campaign, Notification $notification, string $type): CampaignEvent
    {
        $campaignEvent = new CampaignEvent();
        $campaignEvent->setCampaign($campaign);
        $campaignEvent->setName('Send notification');
        $campaignEvent->setType($type);
        $campaignEvent->setEventType('action');
        $campaignEvent->setProperties(['notification' => $notification->getId()]);
        $this->em->persist($campaignEvent);

        return $campaignEvent;
    }

    private function triggerCampaigns(): void
    {
        $this->testSymfonyCommand('mautic:campaigns:trigger');
        $this->em->clear();
    }

    /**
     * @param mixed[] $expectedData
     */
    private function responseDataAssertion(
        array $expectedData,
        string $expectedMethod = 'GET',
        string $expectedUri = '',
        int $status = 200,
        string $body = null,
    ): callable {
        return static function (RequestInterface $request) use ($expectedData, $expectedMethod, $expectedUri, $status, $body) {
            Assert::assertSame($expectedMethod, $request->getMethod());
            Assert::assertSame($expectedUri, $request->getUri()->__toString());
            Assert::assertSame(json_encode($expectedData), $request->getBody()->getContents());
            $headers = $request->getHeaders();
            unset($headers['Content-Length']);
            Assert::assertSame([
                'User-Agent'    => ['GuzzleHttp/7'],
                'Host'          => ['onesignal.com'],
                'Authorization' => ['Basic '.self::REST_API_ID],
                'Content-Type'  => ['application/json'],
            ], $headers);

            return new Response($status, [], $body);
        };
    }

    /**
     * @param array<string> $pushIds
     *
     * @return array<mixed>
     */
    private function getExpectedResponsePushIds(array $pushIds, Notification $notification): array
    {
        return array_merge(
            ['include_player_ids' => $pushIds],
            [
                'contents' => ['en' => $notification->getMessage()],
                'headings' => ['en' => $notification->getHeading()],
                'app_id'   => self::API_ID,
            ]
        );
    }

    private function noMoreRequestAssertion(): callable
    {
        return function () {
            $this->fail('No other request was expected');
        };
    }

    private function convertToTrackedUrl(Notification $notification, Lead $leadOne): string
    {
        /** @var AbstractNotificationApi $api */
        $api          = static::getContainer()->get('mautic.notification.api');
        $clickThrough = [
            'notification' => $notification->getId(),
            'lead'         => $leadOne->getId(),
        ];

        return $api->convertToTrackedUrl($notification->getUrl(), $clickThrough, $notification);
    }

    private function assertEventLogPassed(CampaignEvent $event, Lead $leadOne): void
    {
        $log = $this->findEventLog($event, $leadOne);
        Assert::assertFalse($log->getIsScheduled());

        $metadata = $log->getMetadata();
        Assert::assertIsArray($metadata);
        Assert::assertArrayHasKey('status', $metadata);
        Assert::assertSame('mautic.notification.timeline.status.delivered', $metadata['status']);
    }

    private function assertEventLogFailed(CampaignEvent $event, Lead $leadOne, ?string $reason, bool $isScheduled = false): void
    {
        $log = $this->findEventLog($event, $leadOne);
        Assert::assertSame($isScheduled, $log->getIsScheduled());

        $metadata = $log->getMetadata();
        Assert::assertIsArray($metadata);
        Assert::assertArrayHasKey('failed', $metadata);
        Assert::assertSame(1, $metadata['failed']);
        Assert::assertArrayHasKey('reason', $metadata);
        Assert::assertSame($reason, $metadata['reason']);
    }

    private function findEventLog(CampaignEvent $event, Lead $leadOne): LeadEventLog
    {
        $log = $this->em->getRepository(LeadEventLog::class)->findOneBy([
            'event'    => $event->getId(),
            'lead'     => $leadOne,
            'rotation' => 1,
        ]);
        Assert::assertNotNull($log);

        return $log;
    }

    private function createDoNotContact(Lead $lead, Notification $notification): DoNotContact
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setLead($lead);
        $doNotContact->setChannel('notification');
        $doNotContact->setChannelId($notification->getId());
        $doNotContact->setReason(DoNotContact::UNSUBSCRIBED);
        $doNotContact->setDateAdded(new \DateTime());
        $this->em->persist($doNotContact);

        return $doNotContact;
    }
}
