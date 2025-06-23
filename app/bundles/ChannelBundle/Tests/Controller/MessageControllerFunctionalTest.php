<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Controller;

use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;

class MessageControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testFormWithProject(): void
    {
        $message = new Message();
        $message->setName('Test message');
        $this->em->persist($message);

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/messages/edit/'.$message->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['message[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedMessage = $this->em->find(Message::class, $message->getId());
        Assert::assertSame($project->getId(), $savedMessage->getProjects()->first()->getId());
    }
}
