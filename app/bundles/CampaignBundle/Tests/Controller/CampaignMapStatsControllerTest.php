<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Controller\CampaignMapStatsController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\MapHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
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
        $corePermissionsMock = $this->createMock(CorePermissions::class);
        $corePermissionsMock->method('hasEntityAccess')
            ->willReturn(true);

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
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     */
    public function testGetData(): void
    {
        $campaign = $this->createCampaignWithEmail();

        $dateFrom = new \DateTime('2023-07-20');
        $dateTo   = new \DateTime('2023-07-25');

        $this->campaignModelMock->method('getCountryStats')
            ->with($campaign, $dateFrom, $dateTo)
            ->willReturn($this->getStats());

        $results = $this->mapController->getData($campaign, $dateFrom, $dateTo);

        $this->assertCount(3, $results['read_count']);
        $this->assertCount(3, $results['clicked_through_count']);
        $this->assertSame([
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
        ], $results['read_count']);
        $this->assertSame([
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
        ], $results['clicked_through_count']);
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithEmail(): Campaign
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

        return $campaign;
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
}
