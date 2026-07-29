<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\HttpFoundation\Response;

final class NoteApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use ApiTestUserTrait;

    public function testApiUserWithNotesPermissionsCanGetAndCreateNotes(): void
    {
        $contact = new Lead();
        $contact->setFirstname('Jane');
        $contact->setLastname('Doe');
        $contact->setEmail('jane.note-api@example.com');
        $this->em->persist($contact);

        $note = new LeadNote();
        $note->setLead($contact);
        $note->setText('Existing API note');
        $this->em->persist($note);
        $this->em->flush();

        $apiUser = $this->createApiUserWithPermissions([
            'lead:leads' => ['viewown', 'viewother'],
            'lead:notes' => ['viewown', 'viewother', 'create', 'editown', 'editother', 'deleteown', 'deleteother'],
        ], 'Note API Role');
        $this->authenticateApiUser($apiUser);

        $this->client->request('GET', '/api/notes/'.$note->getId());
        $getResponse = $this->client->getResponse();

        $this->assertResponseIsSuccessful($getResponse->getContent());
        $getPayload = json_decode($getResponse->getContent(), true);
        $this->assertSame($note->getId(), $getPayload['note']['id']);
        $this->assertSame('Existing API note', $getPayload['note']['text']);

        $this->client->request('POST', '/api/notes/new', [
            'lead' => $contact->getId(),
            'text' => 'Created from API',
        ]);
        $createResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $createResponse->getContent());
        $createPayload = json_decode($createResponse->getContent(), true);
        $this->assertSame('Created from API', $createPayload['note']['text']);

        $createdNote = $this->em->getRepository(LeadNote::class)->find($createPayload['note']['id']);
        $this->assertInstanceOf(LeadNote::class, $createdNote);
        $this->assertSame($contact->getId(), $createdNote->getLead()->getId());
    }

    public function testApiUserWithoutNotesCreatePermissionCannotCreateNotes(): void
    {
        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Smith');
        $contact->setEmail('john.note-api@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        $apiUser = $this->createApiUserWithPermissions([
            'lead:leads' => ['viewown', 'viewother'],
            'lead:notes' => ['viewown', 'viewother'],
        ], 'Note API Role');
        $this->authenticateApiUser($apiUser);

        $this->client->request('POST', '/api/notes/new', [
            'lead' => $contact->getId(),
            'text' => 'Forbidden note creation',
        ]);
        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $response->getContent());
    }
}
