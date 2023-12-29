<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Controller\CampaignTableStatsController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignTableStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $campaignModelMock;

    private CampaignTableStatsController $controller;

    private MockObject $exportHelper;

    private MockObject $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $corePermissionsMock = $this->createMock(CorePermissions::class);
        $corePermissionsMock->method('hasEntityAccess')
            ->willReturn(true);

        $this->campaignModelMock       = $this->createMock(CampaignModel::class);
        $this->exportHelper            = $this->createMock(ExportHelper::class);
        $this->translator              = $this->createMock(Translator::class);
        $this->controller              = new CampaignTableStatsController(
            $this->campaignModelMock,
            $this->exportHelper,
            $this->translator
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getStats(bool $addEmail = true): array
    {
        return $addEmail ? [
            'Finland' => [
                    'contacts'              => '14',
                    'country'               => 'Finland',
                    'sent_count'            => '14',
                    'read_count'            => '4',
                    'clicked_through_count' => '0',
                ],
            'Italy' => [
                    'contacts'              => '5',
                    'country'               => 'Italy',
                    'sent_count'            => '5',
                    'read_count'            => '5',
                    'clicked_through_count' => '3',
                ],
        ] : [
            'Finland' => [
                    'contacts' => '14',
                    'country'  => 'Finland',
                ],
            'Italy' => [
                    'contacts' => '5',
                    'country'  => 'Italy',
                ],
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     */
    public function testGetData(): void
    {
        $campaign = $this->createCampaignWithEmail();

        $this->campaignModelMock->method('getCountryStats')
            ->with($campaign)
            ->willReturn($this->getStats());

        $results = $this->controller->getData($campaign);

        $this->assertCount(2, $results);
        $this->assertSame(['Finland', 'Italy'], array_keys($results));
        $this->assertSame([
            'contacts'              => '5',
            'country'               => 'Italy',
            'sent_count'            => '5',
            'read_count'            => '5',
            'clicked_through_count' => '3',
        ], $results['Italy']);
    }

    /**
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     */
    public function testGetDataNoEmail(): void
    {
        $campaign = $this->createCampaignNoEmail();

        $this->campaignModelMock->method('getCountryStats')
            ->with($campaign)
            ->willReturn($this->getStats(false));

        $results = $this->controller->getData($campaign);

        $this->assertCount(2, $results);
        $this->assertSame(['Finland', 'Italy'], array_keys($results));
        $this->assertSame([
            'contacts' => '5',
            'country'  => 'Italy',
        ], $results['Italy']);
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignNoEmail(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetExportHeader(): void
    {
        $campaign        = $this->createCampaignWithEmail();
        $campaignNoEmail = $this->createCampaignNoEmail();

        $this->translator->expects($this->exactly(7))
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.lead.thead.country'],
                ['mautic.lead.leads'],
                ['mautic.lead.lead.thead.country'],
                ['mautic.lead.leads'],
                ['mautic.email.graph.line.stats.sent'],
                ['mautic.email.graph.line.stats.read'],
                ['mautic.email.clicked']
            )
            ->willReturnOnConsecutiveCalls('Country', 'Contacts', 'Country', 'Contacts', 'Sent', 'Read', 'Clicked');

        $this->assertSame(['Country', 'Contacts'], $this->controller->getExportHeader($campaignNoEmail));
        $this->assertSame(['Country', 'Contacts', 'Sent', 'Read', 'Clicked'], $this->controller->getExportHeader($campaign));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Exception
     */
    public function testExportAction(): void
    {
        $campaign = $this->createCampaignWithEmail();

        $this->campaignModelMock->expects($this->once())
            ->method('getEntity')
            ->with($campaign->getId())
            ->willReturn($campaign);

        $this->exportHelper->expects($this->once())
            ->method('exportDataAs')
            ->willReturn(new StreamedResponse());

        $this->controller->exportAction($campaign->getId(), 'csv');
    }
}
