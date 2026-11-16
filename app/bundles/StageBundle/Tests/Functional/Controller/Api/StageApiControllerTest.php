<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

final class StageApiControllerTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'stages',
        ]);
    }

    public function testAddStageToContact(): void
    {
        $contact = $this->createContact('contact-stage-api-add@example.com');
        $stage   = $this->createStage('added stage');

        $this->client->request('POST', "/api/stages/{$stage->getId()}/contact/{$contact->getId()}/add");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $response['success']);
    }

    public function testRemoveStageFromContact(): void
    {
        $stage   = $this->createStage('removed stage');
        $contact = $this->createContact('contact-stage-api-remove@example.com', $stage);

        $this->client->request('POST', "/api/stages/{$stage->getId()}/contact/{$contact->getId()}/remove");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $response['success']);
    }

    private function createContact(string $email, ?Stage $stage = null): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setStage($stage);

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function createStage(string $name): Stage
    {
        $stage = new Stage();
        $stage->setName($name);

        $this->em->persist($stage);
        $this->em->flush();

        return $stage;
    }
}
