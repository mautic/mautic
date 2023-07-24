<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Controller\EmailMapStatsController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\MockObject\MockObject;

class EmailMapStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $emailModelMock;

    private EmailMapStatsController $mapController;

    protected function setUp(): void
    {
        parent::setUp();
        $corePermissionsMock = $this->createMock(CorePermissions::class);
        $corePermissionsMock->method('hasEntityAccess')
            ->willReturn(true);

        $this->emailModelMock = $this->createMock(EmailModel::class);
        $this->mapController  = new EmailMapStatsController($this->emailModelMock);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        return [
            [
                'sent_count'            => '4',
                'read_count'            => '4',
                'clicked_through_count' => '4',
                'country'               => '',
            ],
            [
                'sent_count'            => '20',
                'read_count'            => '12',
                'clicked_through_count' => '7',
                'country'               => 'Italy',
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function testGetData(): void
    {
        $email = new Email();
        $email->setName('Test email');

        $dateFrom = new \DateTime('2023-07-20');
        $dateTo   = new \DateTime('2023-07-25');

        $this->emailModelMock->method('getEmailCountryStats')
            ->with($email, $dateFrom, $dateTo, false)
            ->willReturn($this->getStats());

        $results = $this->mapController->getData($email, $dateFrom, $dateTo);

        $this->assertCount(2, $results);
        $this->assertSame($this->getStats(), $results);
    }
}
