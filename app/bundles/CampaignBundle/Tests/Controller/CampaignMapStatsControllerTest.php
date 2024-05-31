<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Controller\CampaignMapStatsController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\MapHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class CampaignMapStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $campaignModelMock;

    private CampaignMapStatsController $mapController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignModelMock       = $this->createMock(CampaignModel::class);
        $this->mapController           = new CampaignMapStatsController($this->campaignModelMock);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function getStats(): array
    {
        return [
            'contacts' => [
                [
                    'contacts' => '4',
                    'country'  => '',
                ],
                [
                    'contacts' => '4',
                    'country'  => 'Spain',
                ],
                [
                    'contacts' => '4',
                    'country'  => 'Finland',
                ],
            ],
            'clicked_through_count' => [
                [
                    'clicked_through_count' => '4',
                    'country'               => '',
                ],
                [
                    'clicked_through_count' => '4',
                    'country'               => 'Spain',
                ],
                [
                    'clicked_through_count' => '4',
                    'country'               => 'Finland',
                ],
            ],
            'read_count' => [
                [
                    'read_count'            => '4',
                    'country'               => '',
                ],
                [
                    'read_count'            => '8',
                    'country'               => 'Spain',
                ],
                [
                    'read_count'            => '8',
                    'country'               => 'Finland',
                ],
            ],
        ];
    }

    public function testMapCountries(): void
    {
        $stats   = $this->getStats();
        $reads   = MapHelper::mapCountries($stats['read_count'], 'read_count');
        $clicks  = MapHelper::mapCountries($stats['clicked_through_count'], 'clicked_through_count');

        $this->assertSame([
            'data' => [
                'ES' => 8,
                'FI' => 8,
            ],
            'total'            => 20,
            'totalWithCountry' => 16,
        ], $reads);

        $this->assertSame([
            'data' => [
                'ES' => 4,
                'FI' => 4,
            ],
            'total'            => 12,
            'totalWithCountry' => 8,
        ], $clicks);
    }

    /**
     * @throws \Exception
     */
    public function testViewAction(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        $this->client->request('GET', "s/campaign-map-stats/{$campaign->getId()}/2023-07-20/2023-07-25");
        $clientResponse = $this->client->getResponse();
        $crawler        = new Crawler($clientResponse->getContent(), $this->client->getInternalRequest()->getUri());

        $this->assertEmpty($crawler->filter('.map-options__title'));
        $this->assertCount(1, $crawler->filter('div.map-options'));
        $this->assertCount(1, $crawler->filter('div.vector-map'));
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testViewActionWithEmail(): void
    {
        $leadsPayload = [
            [
                'email'    => 'test1@test.com',
                'country'  => '',
                'read'     => true,
                'click'    => true,
            ],
            [
                'email'    => 'test2@test.com',
                'country'  => '',
                'read'     => true,
                'click'    => false,
            ],
            [
                'email'    => 'example1@example.com',
                'country'  => 'Spain',
                'read'     => false,
                'click'    => false,
            ],
            [
                'email'    => 'example2@example.com',
                'country'  => 'Spain',
                'read'     => true,
                'click'    => true,
            ],
            [
                'email'    => 'example3@example.com',
                'country'  => 'Spain',
                'read'     => true,
                'click'    => true,
            ],
            [
                'email'    => 'example4@example.com',
                'country'  => 'Spain',
                'read'     => true,
                'click'    => false,
            ],
        ];
        $campaign = $this->createCampaignWithEmail($leadsPayload);

        $this->client->request('GET', "s/campaign-map-stats/{$campaign->getId()}/2023-07-20/2023-07-25");
        $clientResponse = $this->client->getResponse();
        $crawler        = new Crawler($clientResponse->getContent(), $this->client->getInternalRequest()->getUri());

        $this->assertEmpty($crawler->filter('.map-options__title'));
        $this->assertCount(1, $crawler->filter('div.map-options'));
        $this->assertCount(1, $crawler->filter('div.vector-map'));
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $readOption = $crawler->filter('label.map-options__item')->filter('[data-stat-unit="Read"]');
        $this->assertCount(1, $readOption);
        $this->assertSame('Total: 5 (3 with country)', $readOption->attr('data-legend-text'));
        $this->assertSame('{"ES":3}', $readOption->attr('data-map-series'));

        $clickOption = $crawler->filter('label.map-options__item')->filter('[data-stat-unit="Click"]');
        $this->assertCount(1, $clickOption);
        $this->assertSame('Total: 3 (2 with country)', $clickOption->attr('data-legend-text'));
        $this->assertSame('{"ES":2}', $clickOption->attr('data-map-series'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetMapOptionsEmailCampaign(): void
    {
        $campaign = $this->createCampaignWithEmail();

        $result = $this->mapController->getMapOptions($campaign);
        $this->assertSame(CampaignMapStatsController::MAP_OPTIONS, $result);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetMapOptionsNotEmailCampaign(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign 1');
        $this->em->persist($campaign);
        $this->em->flush();

        $result = $this->mapController->getMapOptions($campaign);
        $this->assertSame(['contacts' => CampaignMapStatsController::MAP_OPTIONS['contacts']], $result);
    }

    /**
     * @param array<int, array<string, bool|string>> $leadsPayload
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithEmail(array $leadsPayload = []): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        // Create email
        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        // Create email events
        $event = new Event();
        $event->setName('Send email');
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setChannel('email');
        $event->setChannelId($email->getId());
        $event->setCampaign($campaign);
        $this->em->persist($event);
        $this->em->flush();

        // Add events to campaign
        $campaign->addEvent(0, $event);

        if (!empty($leadsPayload)) {
            $this->emulateEmailCampaignStat($event, $email, $leadsPayload);
        }

        $this->em->flush();

        return $campaign;
    }

    /**
     * @param array<int, array<string, bool|string>> $leadsPayload
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateEmailCampaignStat(Event $event, Email $email, array $leadsPayload): void
    {
        foreach ($leadsPayload as $l) {
            $lead = new Lead();
            $lead->setEmail($l['email']);
            $lead->setCountry($l['country']);
            $this->em->persist($lead);

            $stat = new Stat();
            $stat->setEmailAddress('test-a@test.com');
            $stat->setLead($lead);
            $stat->setDateSent(new \DateTime('2023-07-22'));
            $stat->setEmail($email);
            $stat->setIsRead($l['read']);
            $stat->setSource('campaign.event');
            $stat->setSourceId($event->getId());
            $this->em->persist($stat);
            $this->em->flush();

            if ($l['read'] && $l['click']) {
                $this->emulateClick($lead, $email);
            }
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateClick(Lead $lead, Email $email): void
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');
        $this->em->persist($ipAddress);
        $this->em->flush();

        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl('https://example.com');
        $redirect->setUniqueHits(1);
        $redirect->setHits(1);
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setChannelId($email->getId());
        $trackable->setHits(1);
        $trackable->setChannel('email');
        $trackable->setUniqueHits(1);
        $trackable->setRedirect($redirect);
        $this->em->persist($trackable);

        $pageHit = new Hit();
        $pageHit->setRedirect($redirect);
        $pageHit->setIpAddress($ipAddress);
        $pageHit->setEmail($email);
        $pageHit->setLead($lead);
        $pageHit->setDateHit(new \DateTime('2023-07-22'));
        $pageHit->setCode(200);
        $pageHit->setUrl($redirect->getUrl());
        $pageHit->setTrackingId($redirect->getRedirectId());
        $pageHit->setSource('email');
        $pageHit->setSourceId($email->getId());
        $this->em->persist($pageHit);
        $this->em->flush();
    }
}
