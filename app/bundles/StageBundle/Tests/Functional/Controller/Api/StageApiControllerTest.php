<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use PHPUnit\Framework\Assert;

class StageApiControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'stages',
        ]);
    }

    public function testAddStageToContact(): void
    {
        $contact = new Lead();
        $contact->setEmail('contact@a.email');

        $stage = new Stage();
        $stage->setName('added stage');

        $this->em->persist($contact);
        $this->em->persist($stage);
        $this->em->flush();

        $this->client->request('POST', "/api/stages/{$contact->getId()}/contact/{$stage->getId()}/add");

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('success', $response);
        Assert::assertSame(1, $response['success']);
    }

    public function testRemoveStageToContact(): void
    {
        $contact = new Lead();
        $contact->setEmail('contact@a.email');

        $stage = new Stage();
        $stage->setName('removed stage');

        $contact->setStage($stage);

        $this->em->persist($contact);
        $this->em->persist($stage);
        $this->em->flush();

        $this->client->request('POST', "/api/stages/{$contact->getId()}/contact/{$stage->getId()}/remove");

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('success', $response);
        Assert::assertSame(1, $response['success']);
    }
}
