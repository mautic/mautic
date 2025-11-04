<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Validator;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Request;

final class UniqueNameValidatorFunctionalTest extends MauticMysqlTestCase
{
    public function testDuplicateProjectName(): void
    {
        $project = new Project();
        $project->setName('qwerty');
        $this->em->persist($project);
        $this->em->flush();

        $this->assertCount(1, $this->em->getRepository(Project::class)->findBy(['name' => $project->getName()]));

        $crawler       = $this->client->request(Request::METHOD_GET, '/s/projects/new');
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $form['project_entity[name]']->setValue('QWERTY');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString(
            'A project with this name already exists.',
            $this->client->getResponse()->getContent()
        );

        $this->assertCount(1, $this->em->getRepository(Project::class)->findBy(['name' => $project->getName()]));
    }
}
