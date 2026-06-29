<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Model\ProjectModel;
use PHPUnit\Framework\Assert;

final class AjaxControllerTest extends MauticMysqlTestCase
{
    public function testCreatingProjectViaMultiselectInput(): void
    {
        $projectNames = [
            'Yellow Project',
            'Blue Project',
            'Red Project',
        ];

        /** @var ProjectModel $projectModel */
        $projectModel = self::getContainer()->get(ProjectModel::class);

        $projects = array_map(
            static function (string $projectName) use ($projectModel) {
                $project = new Project();
                $project->setName($projectName);
                $projectModel->saveEntity($project);

                return $project;
            },
            $projectNames
        );

        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode(['Green Project']),
                'existingProjectIds' => json_encode([$projects[0]->getId(), $projects[1]->getId()]),
            ]
        );
        $this->assertResponseIsSuccessful();

        $payload = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertArrayHasKey('projects', $payload);

        // The options are orderec alphabetically by name.
        Assert::assertSame(
            // The Blue Project is selected as it was sent as part of the existingProjectIds.
            '<option selected="selected" value="'.$projects[1]->getId().'">'.$projects[1]->getName().'</option>'.
            // The Green Project is selected as it was sent as part of the newProjectNames and should have next ID as it was created as 4th.
            '<option selected="selected" value="'.($projects[2]->getId() + 1).'">Green Project</option>'.
            // The Red Project is NOT selected as it was not sent in the AJAX request but it is listed as unselected option.
            '<option value="'.$projects[2]->getId().'">'.$projects[2]->getName().'</option>'.
            // The Yellow Project is selected as it was sent as part of the existingProjectIds.
            '<option selected="selected" value="'.$projects[0]->getId().'">'.$projects[0]->getName().'</option>',
            $payload['projects']
        );
    }

    public function testCreatingDuplicateProject(): void
    {
        $projectModel = self::getContainer()->get('mautic.project.model.project');
        $this->assertInstanceOf(ProjectModel::class, $projectModel);

        $this->assertCount(
            0,
            $this->em->getRepository(Project::class)->findAll(),
            'There should be no projects at the beginning of the test.'
        );

        $project = new Project();
        $project->setName('Yellow Project');
        $projectModel->saveEntity($project);

        $this->assertCount(
            1,
            $this->em->getRepository(Project::class)->findAll(),
            'There should be 1 project after creating the first one.'
        );

        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode(['yellow project']),
                'existingProjectIds' => json_encode([$project->getId()]),
            ]
        );

        $this->assertCount(
            1,
            $this->em->getRepository(Project::class)->findAll(),
            'There should be still 1 project after an attempt to create a duplicate project.'
        );

        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode(['green project']),
                'existingProjectIds' => json_encode([$project->getId()]),
            ]
        );

        $this->assertCount(
            2,
            $this->em->getRepository(Project::class)->findAll(),
            'There should be 2 projects after an attempt to create a unique project.'
        );
    }

    /**
     * Project names with HTML/JS payloads should be properly escaped when rendered
     * in <option> elements to prevent stored XSS attacks.
     *
     * @param string $xssPayload         Malicious XSS payload to test
     * @param string $dangerousSubstring Substring that should NOT appear in escaped output
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('xssPayloadsProvider')]
    public function testProjectNamesAreEscapedInAjaxResponse(string $xssPayload, string $dangerousSubstring): void
    {
        $projectModel = self::getContainer()->get('mautic.project.model.project');
        $this->assertInstanceOf(ProjectModel::class, $projectModel);

        // Create a project with an XSS payload in the name
        $project = new Project();
        $project->setName($xssPayload);
        $projectModel->saveEntity($project);

        // Create another project to verify selection works
        $normalProject = new Project();
        $normalProject->setName('Normal Project');
        $projectModel->saveEntity($normalProject);

        // Request the project options via AJAX without selecting the malicious project
        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode([]),
                'existingProjectIds' => json_encode([]),
            ]
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('projects', $payload);

        $projectOptions = $payload['projects'];

        // The XSS payload should be escaped in both the label and value
        $escapedPayload = htmlspecialchars($xssPayload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Assert the malicious payload is properly escaped
        Assert::assertStringContainsString($escapedPayload, $projectOptions, 'Project name should be HTML-escaped in the response');

        // Assert the dangerous substring is NOT present in the response
        Assert::assertStringNotContainsString($dangerousSubstring, $projectOptions, 'Raw XSS payload should not be present in the response');

        // Assert proper option structure with escaped content
        Assert::assertStringContainsString(
            '<option value="'.$project->getId().'">'.$escapedPayload.'</option>',
            $projectOptions,
            'Option should contain properly escaped label'
        );

        // Verify the malicious project can still be selected and associated normally
        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode([]),
                'existingProjectIds' => json_encode([$project->getId()]),
            ]
        );

        $this->assertResponseIsSuccessful();

        $payload2        = json_decode($this->client->getResponse()->getContent(), true);
        $projectOptions2 = $payload2['projects'];

        // Verify the malicious project can be selected
        Assert::assertStringContainsString(
            '<option selected="selected" value="'.$project->getId().'">'.$escapedPayload.'</option>',
            $projectOptions2,
            'Malicious project should be selectable with escaped content'
        );

        // Verify dangerous content is still not present when selected
        Assert::assertStringNotContainsString($dangerousSubstring, $projectOptions2, 'Raw XSS payload should not be present even when selected');
    }

    /**
     * @return array<string, array<string>>
     */
    public static function xssPayloadsProvider(): array
    {
        return [
            'option break with img onerror' => [
                '</option><img src=x onerror="alert(1)">',
                '<img src=x onerror="alert(1)">',
            ],
            'script tag'                    => [
                '<script>alert(1)</script>',
                '<script>alert(1)</script>',
            ],
            'quote escape with img onerror' => [
                '"><img src=x onerror=alert(1)>',
                '<img src=x onerror=alert(1)>',
            ],
        ];
    }

    /**
     * Test that project names with special characters are properly escaped
     * and remain functional in the project selector.
     *
     * @param string $projectName Project name with special characters
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('specialCharacterProjectNamesProvider')]
    public function testProjectNamesWithSpecialCharactersAreEscapedAndFunctional(string $projectName): void
    {
        $projectModel = self::getContainer()->get('mautic.project.model.project');
        $this->assertInstanceOf(ProjectModel::class, $projectModel);

        // Create a project with special characters
        $project = new Project();
        $project->setName($projectName);
        $projectModel->saveEntity($project);

        // Create another unselected project for comparison
        $unselectedProject = new Project();
        $unselectedProject->setName('Unselected Project');
        $projectModel->saveEntity($unselectedProject);

        // Request project options with the special char project selected
        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode([]),
                'existingProjectIds' => json_encode([$project->getId()]),
            ]
        );

        $this->assertResponseIsSuccessful();

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('projects', $payload);

        $projectOptions = $payload['projects'];
        $escapedName    = htmlspecialchars($projectName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Verify the project appears correctly with escaped name
        Assert::assertStringContainsString($escapedName, $projectOptions, 'Project name with special characters should be HTML-escaped');

        // Verify the selected project has the selected attribute
        Assert::assertStringContainsString(
            '<option selected="selected" value="'.$project->getId().'">'.$escapedName.'</option>',
            $projectOptions,
            'Selected project should have selected attribute and escaped name'
        );

        // Verify the unselected project appears without selected attribute
        Assert::assertStringContainsString(
            '<option value="'.$unselectedProject->getId().'">Unselected Project</option>',
            $projectOptions,
            'Unselected project should not have selected attribute'
        );

        // Test that we can change selection (deselect the special char project, select the other)
        $this->client->request(
            'POST',
            '/s/ajax?action=project:addProjects',
            [
                'newProjectNames'    => json_encode([]),
                'existingProjectIds' => json_encode([$unselectedProject->getId()]),
            ]
        );

        $this->assertResponseIsSuccessful();

        $payload2        = json_decode($this->client->getResponse()->getContent(), true);
        $projectOptions2 = $payload2['projects'];

        // Verify selections are preserved correctly
        Assert::assertStringContainsString(
            '<option selected="selected" value="'.$unselectedProject->getId().'">Unselected Project</option>',
            $projectOptions2,
            'Selection changes should be preserved'
        );

        Assert::assertStringContainsString(
            '<option value="'.$project->getId().'">'.$escapedName.'</option>',
            $projectOptions2,
            'Previously selected project should now be unselected'
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function specialCharacterProjectNamesProvider(): array
    {
        return [
            'ampersand'      => ['Project & Company'],
            'double quotes'  => ['Client "ABC" Ltd'],
            'single quotes'  => ["Q1 '26 Results"],
        ];
    }
}
