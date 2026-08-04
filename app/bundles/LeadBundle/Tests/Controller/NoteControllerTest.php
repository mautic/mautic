<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadNote;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class NoteControllerTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'companies',
            'campaigns',
            'categories',
            'lead_lists',
        ]);
    }

    public function testIndexActionIsSuccessful(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown'],
        ]);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/'.$contact->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
    }

    public function testIndexActionIsDeniedForMissingLeadId(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/0');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getContent());
    }

    public function testIndexActionShowsEmptyPaneWithoutNotesViewPermission(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
        ]);
        $this->createNoteForContact($contact, $user);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/'.$contact->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $this->assertStringContainsString('alert alert-warning', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('btn-leadnote-add', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Test note', (string) $this->client->getResponse()->getContent());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$contact->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $this->assertSame('0', trim($crawler->filter('#NoteCount')->text()));
    }

    public function testNewActionIsSuccessful(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown', 'create'],
        ]);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/'.$contact->getId().'/new');
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
    }

    public function testNewActionIsDeniedWithoutNotesCreatePermission(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown'],
        ]);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/'.$contact->getId().'/new');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getContent());
    }

    public function testEditActionIsSuccessful(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown', 'editown'],
        ]);
        $note = $this->createNoteForContact($contact, $user);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $note->getId()));
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
    }

    public function testEditActionIsDeniedWithoutNotesEditPermission(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown'],
        ]);
        $note = $this->createNoteForContact($contact, $user);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $note->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getContent());
    }

    public function testDeleteActionIsSuccessful(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown', 'deleteown'],
        ]);
        $note   = $this->createNoteForContact($contact, $user);
        $noteId = $note->getId();
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/delete/%d', $contact->getId(), $noteId));
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $this->assertNotInstanceOf(LeadNote::class, $this->em->getRepository(LeadNote::class)->find($noteId));
    }

    public function testDeleteActionIsDeniedWithoutNotesDeletePermission(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown'],
        ]);
        $note = $this->createNoteForContact($contact, $user);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/delete/%d', $contact->getId(), $note->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getContent());
    }

    public function testDeleteActionReturnsNotFoundWhenNoteDoesNotExist(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown', 'deleteown'],
        ]);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/delete/999999', $contact->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getContent());
    }

    public function testExecuteNoteActionWithInvalidActionReturnsNotFound(): void
    {
        [$user, $contact] = $this->createUserAndOwnedContact([
            'lead:leads' => ['viewown'],
            'lead:notes' => ['viewown'],
        ]);
        $this->loginAs($user);

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/not-real-action/0', $contact->getId()));
        $this->assertResponseStatusCodeSame(
            Response::HTTP_NOT_FOUND,
            'Unknown note actions should return a not found response.'
        );
    }

    public function testUserCanEditOwnNoteOnOthersContactWithEditOwnPermission(): void
    {
        $ownerA = $this->createUserWithPermissions([
            'lead:leads' => ['full'],
            'lead:notes' => ['full'],
        ]);
        $userB = $this->createUserWithPermissions([
            'lead:leads' => ['viewown', 'viewother', 'create'],
            'lead:notes' => ['viewown', 'create', 'editown'],
        ]);

        $contact = new Lead();
        $contact->setFirstname('Owned');
        $contact->setLastname('ByA');
        $contact->setEmail('owned.by.a.'.uniqid().'@example.com');
        $contact->setOwner($ownerA);
        $this->em->persist($contact);
        $this->em->flush();

        $note = new LeadNote();
        $note->setLead($contact);
        $note->setText('Owned by B');
        $note->setCreatedBy($userB);
        $this->em->persist($note);

        $adminOwnedNote = new LeadNote();
        $adminOwnedNote->setLead($contact);
        $adminOwnedNote->setText('Owned by A');
        $adminOwnedNote->setCreatedBy($ownerA);
        $this->em->persist($adminOwnedNote);
        $this->em->flush();

        $this->loginAs($userB);

        $this->client->request(Request::METHOD_GET, '/s/contacts/notes/'.$contact->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Owned by B', (string) $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Owned by A', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString(
            sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $note->getId()),
            (string) $this->client->getResponse()->getContent()
        );
        $this->assertStringNotContainsString(
            sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $adminOwnedNote->getId()),
            (string) $this->client->getResponse()->getContent()
        );

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $note->getId()));
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/notes/%d/edit/%d', $contact->getId(), $adminOwnedNote->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getContent());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$contact->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());
        $this->assertSame('1', trim($crawler->filter('#NoteCount')->text()));
    }

    /**
     * @param array<string, string[]> $permissions
     *
     * @return array{0: User, 1: Lead}
     */
    private function createUserAndOwnedContact(array $permissions): array
    {
        $role = new Role();
        $role->setName('Note Controller Role '.uniqid('', true));
        $this->em->persist($role);
        $this->em->flush();

        $roleModel = static::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('Note');
        $user->setLastName('Tester');
        $user->setUsername('note.controller.'.uniqid());
        $user->setEmail('note.controller.'.uniqid().'@example.com');
        $user->setRole($role);

        $hasher = static::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $this->em->persist($user);
        $this->em->flush();

        $contact = new Lead();
        $contact->setFirstname('Contact');
        $contact->setLastname('Owner');
        $contact->setEmail('note.contact.'.uniqid().'@example.com');
        $contact->setOwner($user);
        $this->em->persist($contact);
        $this->em->flush();

        return [$user, $contact];
    }

    private function createNoteForContact(Lead $contact, ?User $user = null): LeadNote
    {
        $note = new LeadNote();
        $note->setLead($contact);
        $note->setText('Test note');
        if ($user instanceof User) {
            $note->setCreatedBy($user);
        }
        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * @param array<string, string[]> $permissions
     */
    private function createUserWithPermissions(array $permissions): User
    {
        $role = new Role();
        $role->setName('Note Role '.uniqid('', true));
        $this->em->persist($role);
        $this->em->flush();

        $roleModel = static::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('User');
        $user->setLastName('Note');
        $user->setUsername('note.user.'.uniqid());
        $user->setEmail('note.user.'.uniqid().'@example.com');
        $user->setRole($role);

        $hasher = static::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->loginUser($user);
    }
}
