<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ReportApiControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testGetReportFailByNoCorrectAccessRoleEmpty(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', []);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetReportSuccessByCorrectAccessIsAdmin(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', [], false, true);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertResponseIsSuccessful();
    }

    public function testGetReportSuccessByNoCorrectAccessToViewOther(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewother']]);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertResponseIsSuccessful();
    }

    public function testReportFailByNoCorrectAccessToViewOwn(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewown']]);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testReportSuccessViewOwnBySameUser(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewown']], true);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertResponseIsSuccessful();
    }

    public function testReportSuccessViewSameRole(): void
    {
        $loginValue  = 'Maut1cR0cks!!!!!';
        $role        = $this->createRole();
        $user        = $this->createUser($role, $loginValue);
        $reportOwner = $this->createUser($role, 'owner-value', 'report.owner', 'report.owner@email.com');
        $report      = $this->createReportData($reportOwner->getId());

        $this->setPermission($user, ['report:reports'=>['viewsamerole']]);

        // Disable the default login.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', $loginValue);

        $this->client->request('GET', '/api/reports/'.$report->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testGetReportsIncludesSameRoleReports(): void
    {
        $loginValue  = 'Maut1cR0cks!!!!!';
        $role        = $this->createRole();
        $user        = $this->createUser($role, $loginValue);
        $reportOwner = $this->createUser($role, 'owner-value', 'report.list.owner', 'report.list.owner@email.com');

        $this->createReportData($reportOwner->getId());
        $this->setPermission($user, ['report:reports'=>['viewsamerole']]);

        // Disable the default login.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', $loginValue);

        $this->client->request('GET', '/api/reports');
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Contact report', $this->client->getResponse()->getContent());
    }

    /**
     * @param array<array<string>> $permissions
     */
    private function createReportStructure(string $plainValue, array $permissions, bool $createBy = false, bool $userIsAdmin = false): int
    {
        $role           = $this->createRole($userIsAdmin);
        $user           = $this->createUser($role, $plainValue);
        $createByIdUser = 0;
        if (!empty($createBy)) {
            $createByIdUser = $user->getId();
        }
        $report   = $this->createReportData($createByIdUser);

        if ($permissions) {
            $this->setPermission($user, $permissions);
        }
        // Disable the default login.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', $plainValue);

        return $report->getId();
    }

    /**
     * @param array<array<string>> $permissions
     */
    private function setPermission(User $user, array $permissions): Role
    {
        $role = $user->getRole();
        // Delete previous permissions
        $this->em->createQueryBuilder()
            ->delete(Permission::class, 'p')
            ->where('p.bundle = :bundle')
            ->andWhere('p.role = :role_id')
            ->setParameters(['bundle' => 'report', 'role_id' => $role->getId()])
            ->getQuery()
            ->execute();

        // Set new permissions
        $role->setIsAdmin(false);
        /** @var RoleModel $roleModel */
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        $this->assertInstanceOf(RoleModel::class, $roleModel);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();

        return $role;
    }

    private function createUser(Role $role, string $plainValue = 'mautic', string $username = 'john.doe', string $email = 'john.doe@email.com'): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername($username);
        $user->setEmail($email);
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash($plainValue));
        $user->setRole($role);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);
        $this->em->flush();

        return $role;
    }

    private function createReportData(int $createBy = 0): Report
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setDescription('<b>This is allowed HTML</b>');
        $report->setSource('leads');
        $coulmns = [
            'l.firstname',
            'l.lastname',
            'l.email',
            'l.date_added',
        ];
        $report->setColumns($coulmns);
        if (!empty($createBy)) {
            $report->setCreatedBy($createBy);
            $report->setCreatedByUser($createBy);
        }

        $this->getContainer()->get('mautic.report.model.report')->saveEntity($report);

        return $report;
    }
}
