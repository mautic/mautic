<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use PHPUnit\Framework\Assert;

class FocusControllerTest extends MauticMysqlTestCase
{
    public function testFocusWithProject(): void
    {
        $focus = new Focus();
        $focus->setName('Test Focus');
        $focus->setType('notice');
        $focus->setStyle('bar');
        $this->em->persist($focus);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/focus/edit/'.$focus->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['focus[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedFocus = $this->em->find(Focus::class, $focus->getId());
        Assert::assertSame($project->getId(), $savedFocus->getProjects()->first()->getId());
    }
}
