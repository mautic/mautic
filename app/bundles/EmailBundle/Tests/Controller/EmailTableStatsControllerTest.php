<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Controller\EmailTableStatsController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailTableStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $emailModelMock;

    private EmailTableStatsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailModelMock = $this->createMock(EmailModel::class);
        $this->controller     = new EmailTableStatsController($this->emailModelMock);
    }

    /**
     * @throws ORMException
     */
    public function testHasAccess(): void
    {
        $corePermissionsMock = $this->createMock(CorePermissions::class);

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
        $this->em->flush();

        $corePermissionsMock->method('hasEntityAccess')
            ->with(
                'email:emails:viewown',
                'email:emails:viewother',
                $user->getId()
            )
            ->willReturn(false);

        $result = $this->controller->hasAccess($corePermissionsMock, $email);

        try {
            $this->controller->viewAction($corePermissionsMock, $email->getId());
        } catch (AccessDeniedHttpException|\Exception $e) {
            $this->assertTrue($e instanceof AccessDeniedHttpException);
        }

        $this->assertFalse($result);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function getStats(): array
    {
        return [
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
        ];
    }

    /**
     * @throws Exception
     */
    public function testGetData(): void
    {
        $email = new Email();
        $email->setName('Test email');

        $this->emailModelMock->method('getCountryStats')
            ->with($email, false)
            ->willReturn($this->getStats());

        $results = $this->controller->getData($email);

        $this->assertCount(2, $results);
        $this->assertSame($this->getStats(), $results);
    }

    public function testGetExportHeader(): void
    {
        $translator = $this->getContainer()->get('translator');
        $email      = new Email();

        $headerRow = [
            $translator->trans('mautic.lead.lead.thead.country'),
            $translator->trans('mautic.email.graph.line.stats.sent'),
            $translator->trans('mautic.email.graph.line.stats.read'),
            $translator->trans('mautic.email.clicked'),
        ];

        $this->assertSame($headerRow, $this->getExportHeader($email));
    }
}
