<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Controller\EmailMapStatsController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;

class EmailMapStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $emailModelMock;

    private EmailMapStatsController $mapController;

    private CorePermissions $corePermissionsMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->corePermissionsMock = $this->createMock(CorePermissions::class);

        $this->emailModelMock = $this->createMock(EmailModel::class);
        $this->mapController  = new EmailMapStatsController($this->emailModelMock);
    }

    /**
     * @throws ORMException
     */
    public function testHasAccess(): void
    {
        $role = new Role();
        $role->setName('Example admin');
        $this->em->persist($role);
        $this->em->flush();

        $user = new User();
        $user->setFirstName('Example');
        $user->setLastName('Example');
        $user->setUsername('Example');
        $user->setPassword('123456');
        $user->setEmail('example@example.com');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        $email = new Email();
        $email->setName('Test email 1');
        $email->setCreatedBy($user);
        $this->em->persist($email);

        $this->corePermissionsMock->method('hasEntityAccess')
            ->with(
                'email:emails:viewown',
                'email:emails:viewother',
                $user->getId()
            )
            ->willReturn(false);

        $result = $this->mapController->hasAccess($this->corePermissionsMock, $email);

        $this->assertFalse($result);
    }

    public function testGetMapOptions(): void
    {
        $result = $this->mapController->getMapOptions();
        $this->assertSame(EmailMapStatsController::MAP_OPTIONS, $result);
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
