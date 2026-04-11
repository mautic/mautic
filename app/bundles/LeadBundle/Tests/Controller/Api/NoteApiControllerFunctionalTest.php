<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NoteApiControllerFunctionalTest extends MauticMysqlTestCase
{
    private function createLead(): Lead
    {
        $lead = (new Lead())->setFirstname('Test');
        static::getContainer()->get('mautic.lead.model.lead')->saveEntity($lead);

        return $lead;
    }

    public function testCreateNoteRequiresDateTime(): void
    {
        $lead = $this->createLead();
        $payload = [
            'lead' => $lead->getId(),
            'text' => 'API note without date',
            'type' => 'general',
        ];

        $this->client->request(Request::METHOD_POST, '/api/notes/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertIsArray($response);
        $this->assertNotEmpty($response['errors'][0]['details'] ?? [], $clientResponse->getContent());
        $this->assertArrayHasKey('dateTime', $response['errors'][0]['details']);
    }

    public function testCreateNoteWithDateTime(): void
    {
        $lead = $this->createLead();
        $payload = [
            'lead' => $lead->getId(),
            'text' => 'API note with date',
            'type' => 'general',
            'dateTime' => '2026-03-30 10:00:00',
        ];

        $this->client->request(Request::METHOD_POST, '/api/notes/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertSame($payload['text'], $response['note']['text']);
        $this->assertNotEmpty($response['note']['dateTime']);
    }
}
