<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;

final class TriggerControllerTest extends MauticMysqlTestCase
{
    public function testPointTriggerWithProject(): void
    {
        $trigger = new Trigger();
        $trigger->setName('test');
        $this->em->persist($trigger);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/points/triggers/edit/'.$trigger->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['pointtrigger[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedAsset = $this->em->find(Trigger::class, $trigger->getId());
        Assert::assertSame($project->getId(), $savedAsset->getProjects()->first()->getId());
    }
}
