<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Entity\ProjectRepository;
use Mautic\ProjectBundle\Model\ProjectModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ProjectControllerTest extends MauticMysqlTestCase
{
    public const string USERNAME = 'johny';

    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $projects = [
            'project1',
            'project2',
            'project3',
            'project4',
        ];

        /** @var ProjectModel $projectModel */
        $projectModel            = self::getContainer()->get(ProjectModel::class);
        $this->projectRepository = $projectModel->getRepository();

        foreach ($projects as $projectName) {
            $project = new Project();
            $project->setName($projectName);
            $projectModel->saveEntity($project);
        }
    }

    #[DataProvider('indexUrlsProvider')]
    public function testIndexActionDisplaysProjects(string $url): void
    {
        $this->client->request(Request::METHOD_GET, $url);
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('project1', (string) $clientResponseContent, 'The return must contain project1');
        $this->assertStringContainsString('project2', (string) $clientResponseContent, 'The return must contain project2');
    }

    /**
     * @return iterable<string, array<int, string>>
     */
    public static function indexUrlsProvider(): iterable
    {
        yield 'non-existent page nuber'                         => ['/s/projects/999'];
        yield 'main index page with no number (meaning page=1)' => ['/s/projects'];
    }

    public function testIndexActionWhenFiltered(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/projects?search=project1');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('project1', (string) $clientResponseContent, 'The return must contain project1');
        $this->assertStringNotContainsString('project2', (string) $clientResponseContent, 'The return must not contain project2');
    }

    public function testProjectDeletion(): void
    {
        $project = $this->projectRepository->findOneBy([]);
        $segment = new LeadList();
        $segment->setName('Test segment');
        $segment->setPublicName('Test segment');
        $segment->setAlias('test-segment');
        $segment->addProject($project);

        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $projectId = $project->getId();

        $this->client->request(Request::METHOD_POST, '/s/projects/delete/'.$projectId);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->projectRepository->find($projectId), 'Assert that project is deleted');
        $this->assertCount(0, $this->em->find(LeadList::class, $segment->getId())->getProjects());
    }

    public function testViewAction(): void
    {
        $project = $this->projectRepository->findOneBy([]);

        $this->client->request(Request::METHOD_GET, '/s/projects/view/'.$project->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($project->getName(), (string) $clientResponseContent, 'The return must contain project');
    }

    public function testViewActionOpensTheOnlyEditableEntityTypeDirectly(): void
    {
        $project = $this->projectRepository->findOneBy([]);

        $this->createAndLoginUser([
            'project:project' => ['view'],
            'asset:assets'    => ['viewown', 'editown'],
        ]);

        $this->client->request('GET', '/s/projects/view/'.$project->getId());
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('/s/projects/addEntity/', $content);
        $this->assertStringContainsString('entityType=asset', $content);
    }

    public function testViewActionOpensEntityTypeSelectorWhenSeveralEntityTypesAreEditable(): void
    {
        $project = $this->projectRepository->findOneBy([]);

        $this->createAndLoginUser([
            'project:project' => ['view'],
            'asset:assets'    => ['viewown', 'editown'],
            'email:emails'    => ['viewown', 'editown'],
        ]);

        $this->client->request('GET', '/s/projects/view/'.$project->getId());
        $content = (string) $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('/s/projects/selectEntityType/', $content);
    }

    public function testViewActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/s/projects/view/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    public function testEditAction(): void
    {
        $projectName            = 'Test project';
        $project                = $this->projectRepository->findOneBy([]);
        $crawler                = $this->client->request(Request::METHOD_GET, '/s/projects/edit/'.$project->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Edit project: '.$project->getName(), (string) $clientResponseContent, 'The return must contain \'Edit project\' text');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['project_entity[name]']->setValue($projectName);
        $this->client->submit($form);

        $this->assertSame(1, $this->projectRepository->count(['name' => $projectName]));
    }

    public function testEditActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/s/projects/edit/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    public function testNewAction(): void
    {
        $projectName = 'Test project';
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/projects/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['project_entity[name]']->setValue($projectName);
        $this->client->submit($form);

        $this->assertSame(1, $this->projectRepository->count(['name' => $projectName]));
    }

    public function testBatchDeleteAction(): void
    {
        $projects   = $this->projectRepository->findAll();
        $projectsId = array_map(fn (Project $project): ?int => $project->getId(), $projects);
        $this->client->request(Request::METHOD_POST, '/s/projects/batchDelete?ids='.json_encode($projectsId));
        $this->assertResponseIsSuccessful();
        $this->assertEmpty($this->projectRepository->count([]), 'All projects must be deleted.');
    }

    public function testEmptyProjectShouldThrowValidationError(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/projects/new');
        $this->assertResponseIsSuccessful();

        $buttonCrawler  = $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form->setValues(['project_entity[name]' => '']);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('A name is required.', (string) $this->client->getResponse()->getContent());
    }

    public function testEditProjectWithNoPermission(): void
    {
        $this->createAndLoginUser();
        $project     = $this->projectRepository->findOneBy([]);
        $this->client->request(Request::METHOD_GET, '/s/projects/edit/'.$project->getId());
        $this->assertResponseStatusCodeSame(403, (string) $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, array<int, string>> $permissions
     */
    private function createAndLoginUser(array $permissions = []): User
    {
        // Create non-admin role
        $role = $this->createRole();
        /** @var RoleModel $roleModel */
        $roleModel = self::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->loginUser($user);
        // $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        // $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

        return $user;
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('Jhon');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('mautic'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
