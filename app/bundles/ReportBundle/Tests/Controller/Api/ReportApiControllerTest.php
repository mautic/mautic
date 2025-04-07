<?php

namespace Mautic\ReportBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class ReportApiControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testGetReportFailByNoCorrectAccessRoleEmpty(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', []);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetReportSuccessByCorrectAccessIsAdmin(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', [], false, true);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetReportSuccessByNoCorrectAccessToViewOther(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewother']]);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testReportFailByNoCorrectAccessToViewOwn(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewown']]);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testReportSuccessViewOwnBySameUser(): void
    {
        $reportId = $this->createReportStructure('Maut1cR0cks!!!!!', ['report:reports'=>['viewown']], true);
        $this->client->request('GET', '/api/reports/'.$reportId);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<array<string>> $permissions
     */
    private function createReportStructure(string $password, array $permissions, bool $createBy = false, bool $userIsAdmin = false): int
    {
        $role           = $this->createRole($userIsAdmin);
        $user           = $this->createUser($role, $password);
        $createByIdUser = 0;
        if (!empty($createBy)) {
            $createByIdUser = $user->getId();
        }
        $report   = $this->createReportData($createByIdUser);

        if ($permissions) {
            $this->setPermission($user, $permissions);
        }
        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', $password);

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
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        \assert($roleModel instanceof RoleModel);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);
        $this->em->flush();

        return $role;
    }

    private function createUser(Role $role, string $password='mautic'): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash($password));
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
