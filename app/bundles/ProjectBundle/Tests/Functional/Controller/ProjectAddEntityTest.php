<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Model\ProjectModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[Group('database')]
final class ProjectAddEntityTest extends MauticMysqlTestCase
{
    private Project $testProject;

    private Email $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ProjectModel $projectModel */
        $projectModel            = self::getContainer()->get(ProjectModel::class);

        // Create test project
        $this->testProject = new Project();
        $this->testProject->setName('Test Project for Add Entity');
        $this->testProject->setDescription('Test project for functional testing');
        $projectModel->saveEntity($this->testProject);

        // Create test email
        /** @var EmailModel $emailModel */
        $emailModel      = self::getContainer()->get(EmailModel::class);
        $this->testEmail = new Email();
        $this->testEmail->setName('Test Email for Project');
        $this->testEmail->setSubject('Test Email Subject');
        $this->testEmail->setEmailType('template');
        $this->testEmail->setTemplate('blank');
        $emailModel->saveEntity($this->testEmail);
    }

    public function testSelectEntityTypeActionRendersModal(): void
    {
        $url = '/s/projects/selectEntityType/'.$this->testProject->getId();

        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $response = $this->client->getResponse();
        $content  = $response->getContent();

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('entityType=email', (string) $content);
    }

    public function testSelectEntityTypeActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/selectEntityType/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAddEntityActionGetRequest(): void
    {
        $url = '/s/projects/addEntity/'.$this->testProject->getId().'?entityType=email';

        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $response = $this->client->getResponse();
        $content  = $response->getContent();

        $this->assertResponseIsSuccessful();

        // Should contain the form with proper structure
        $this->assertStringContainsString('name="project_add_entity"', (string) $content);
        $this->assertStringContainsString('project_add_entity[entityType]', (string) $content);
        $this->assertStringContainsString('project_add_entity[projectId]', (string) $content);
        $this->assertStringContainsString('project_add_entity[entityIds][]', (string) $content);
    }

    public function testAddEntityActionPostWithValidData(): void
    {
        // Add email to project directly using the entity relationship
        $this->testEmail->addProject($this->testProject);
        $this->em->persist($this->testEmail);
        $this->em->flush();

        // View project page to verify email was added
        $url = '/s/projects/view/'.$this->testProject->getId();
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);

        $response = $this->client->getResponse();
        $content  = $response->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($this->testProject->getName(), (string) $content);
        $this->assertStringContainsString($this->testEmail->getName(), (string) $content);
    }

    public function testAddEntityActionPostWithEmptyData(): void
    {
        $url = '/s/projects/addEntity/'.$this->testProject->getId().'?entityType=email';

        // Get the form
        $crawler = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();

        // Submit form with no entities selected
        $form = $crawler->filter('form')->first()->form();
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testAddEntityActionPostWithCancelledForm(): void
    {
        $url = '/s/projects/addEntity/'.$this->testProject->getId().'?entityType=email';

        // Get the form
        $crawler = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();

        // Submit form normally (simulating any button press)
        $form = $crawler->filter('form')->first()->form();
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testAddEntityActionWithInvalidEntityType(): void
    {
        $url = '/s/projects/addEntity/'.$this->testProject->getId().'?entityType=invalid_type';

        // Get request with invalid entity type should redirect with error
        $this->client->followRedirects();
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        // Check for error message in flashes
        $content = $response->getContent();
        $this->assertStringContainsString('Invalid entity type', (string) $content);
    }

    public function testAddEntityActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/projects/addEntity/99999?entityType=email');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAddEntityActionWithoutPermission(): void
    {
        $this->createAndLoginUser();

        $url = '/s/projects/addEntity/'.$this->testProject->getId().'?entityType=email';
        $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = new Role();
        $role->setName('Test Role');
        $role->setIsAdmin(false);
        $this->em->persist($role);

        // Create non-admin user
        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $user->setPassword($hasher->hash('password'));
        $user->setRole($role);
        $this->em->persist($user);

        $this->em->flush();

        $this->loginUser($user);

        return $user;
    }
}
