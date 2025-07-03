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
}
