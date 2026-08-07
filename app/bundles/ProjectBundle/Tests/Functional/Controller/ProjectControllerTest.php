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
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, $url);
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, $url);
>>>>>>> 222589fde5 (cs)
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
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, '/s/projects?search=project1');
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects?search=project1');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, '/s/projects?search=project1');
>>>>>>> 222589fde5 (cs)
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

<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_POST, '/s/projects/delete/'.$projectId);
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/s/projects/delete/'.$projectId);
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_POST, '/s/projects/delete/'.$projectId);
>>>>>>> 222589fde5 (cs)

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->projectRepository->find($projectId), 'Assert that project is deleted');
        $this->assertCount(0, $this->em->find(LeadList::class, $segment->getId())->getProjects());
    }

    public function testViewAction(): void
    {
        $project = $this->projectRepository->findOneBy([]);

<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, '/s/projects/view/'.$project->getId());
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/view/'.$project->getId());
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, '/s/projects/view/'.$project->getId());
>>>>>>> 222589fde5 (cs)
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($project->getName(), (string) $clientResponseContent, 'The return must contain project');
    }

    public function testViewActionNotFound(): void
    {
        $this->client->followRedirects(false);
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, '/s/projects/view/99999');
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/view/99999');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, '/s/projects/view/99999');
>>>>>>> 222589fde5 (cs)
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    public function testEditAction(): void
    {
        $projectName            = 'Test project';
        $project                = $this->projectRepository->findOneBy([]);
<<<<<<< HEAD
<<<<<<< HEAD
        $crawler                = $this->client->request(Request::METHOD_GET, '/s/projects/edit/'.$project->getId());
=======
        $crawler                = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/edit/'.$project->getId());
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler                = $this->client->request(Request::METHOD_GET, '/s/projects/edit/'.$project->getId());
>>>>>>> 222589fde5 (cs)
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
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_GET, '/s/projects/edit/99999');
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/edit/99999');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_GET, '/s/projects/edit/99999');
>>>>>>> 222589fde5 (cs)
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    public function testNewAction(): void
    {
        $projectName = 'Test project';
<<<<<<< HEAD
<<<<<<< HEAD
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/projects/new');
=======
        $crawler     = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/new');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/projects/new');
>>>>>>> 222589fde5 (cs)
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
<<<<<<< HEAD
<<<<<<< HEAD
        $this->client->request(Request::METHOD_POST, '/s/projects/batchDelete?ids='.json_encode($projectsId));
=======
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/s/projects/batchDelete?ids='.json_encode($projectsId));
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $this->client->request(Request::METHOD_POST, '/s/projects/batchDelete?ids='.json_encode($projectsId));
>>>>>>> 222589fde5 (cs)
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

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
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
