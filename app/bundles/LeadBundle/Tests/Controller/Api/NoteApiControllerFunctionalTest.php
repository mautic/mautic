<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class NoteApiControllerFunctionalTest extends MauticMysqlTestCase
{
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
        ]);
        $this->authenticateApiUser($apiUser);

        $this->client->request('GET', '/api/notes/'.(string) $note->getId());
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
        ]);
        $this->authenticateApiUser($apiUser);

        $this->client->request('POST', '/api/notes/new', [
            'lead' => $contact->getId(),
            'text' => 'Forbidden note creation',
        ]);
        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $response->getContent());
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function createApiUserWithPermissions(array $permissions): User
    {
        $role = new Role();
        $role->setName('Note API Role '.uniqid('', true));
        $this->em->persist($role);
        $this->em->flush();

        $roleModel = static::getContainer()->get('mautic.user.model.role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('Note');
        $user->setLastName('Api');
        $user->setUsername('note.api.'.uniqid());
        $user->setEmail('note.api.'.uniqid().'@example.com');
        $user->setRole($role);

        $hasher = static::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function authenticateApiUser(User $user): void
    {
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }
}
