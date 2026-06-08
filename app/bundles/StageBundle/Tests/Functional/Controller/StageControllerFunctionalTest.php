<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\StageBundle\Entity\Stage;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class StageControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testStageMenuString(): void
    {
        $stage = $this->client->request(Request::METHOD_GET, '/s/stages');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $stageMenuString = $stage->filterXPath('//a[@id="mautic_stage_index"]');
        Assert::assertStringContainsString('Stages', $stageMenuString->text());
    }

    public function testStageWithProject(): void
    {
        $stage = new Stage();
        $stage->setName('test');
        $this->em->persist($stage);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/stages/edit/'.$stage->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['stage[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedStage = $this->em->find(Stage::class, $stage->getId());
        Assert::assertSame($project->getId(), $savedStage->getProjects()->first()->getId());
    }
}
