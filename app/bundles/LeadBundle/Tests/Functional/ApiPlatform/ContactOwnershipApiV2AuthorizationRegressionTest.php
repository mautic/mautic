<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\ApiPlatform;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class ContactOwnershipApiV2AuthorizationRegressionTest extends OwnershipScopedApiAuthorizationTestBase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('endpointProvider')]
    public function testViewOwnCannotReadForeignContactOnApiEndpoints(string $endpointTemplate): void
    {
        $owner    = $this->createUserWithPermissions(
            username: 'owner.user',
            email: 'owner.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother', 'editother', 'deleteother'],
                'api:access' => ['full'],
            ]
        );
        $attacker = $this->createUserWithPermissions(
            username: 'attacker.user',
            email: 'attacker.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        $foreignContact = new Lead();
        $foreignContact->setFirstname('Foreign');
        $foreignContact->setLastname('Contact');
        $foreignContact->setEmail('foreign.contact@example.test');
        $foreignContact->setOwner($owner);
        $foreignContact->setCreatedBy($owner);

        $this->em->persist($foreignContact);
        $this->em->flush();
        $this->em->clear();

        $attacker = $this->em->getRepository(User::class)->findOneBy(['username' => 'attacker.user']);
        \assert($attacker instanceof User);
        $this->client->getCookieJar()->clear();
        $this->client->setServerParameter('PHP_AUTH_USER', $attacker->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $endpoint = sprintf($endpointTemplate, (int) $foreignContact->getId());
        $this->client->request('GET', $endpoint);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @return iterable<array{string}>
     */
    public static function endpointProvider(): iterable
    {
        yield 'legacy api v1 contact item' => ['/api/contacts/%d'];
        yield 'api v2 contact item' => ['/api/v2/contacts/%d'];
    }

    public function testViewOwnCollectionCannotSeeForeignContactOnApiV2(): void
    {
        $owner    = $this->createUserWithPermissions(
            username: 'owner.collection.user',
            email: 'owner.collection.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother', 'editother', 'deleteother'],
                'api:access' => ['full'],
            ]
        );
        $attacker = $this->createUserWithPermissions(
            username: 'attacker.collection.user',
            email: 'attacker.collection.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        $foreignContact = new Lead();
        $foreignContact->setFirstname('ForeignCollection');
        $foreignContact->setLastname('Contact');
        $foreignContact->setEmail('foreign.collection.contact@example.test');
        $foreignContact->setOwner($owner);
        $foreignContact->setCreatedBy($owner);

        $ownContact = new Lead();
        $ownContact->setFirstname('OwnCollection');
        $ownContact->setLastname('Contact');
        $ownContact->setEmail('own.collection.contact@example.test');
        $ownContact->setOwner($attacker);
        $ownContact->setCreatedBy($attacker);

        $this->em->persist($foreignContact);
        $this->em->persist($ownContact);
        $this->em->flush();
        $this->em->clear();

        $attacker = $this->em->getRepository(User::class)->findOneBy(['username' => 'attacker.collection.user']);
        \assert($attacker instanceof User);

        $this->client->getCookieJar()->clear();
        $this->client->setServerParameter('PHP_AUTH_USER', $attacker->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/contacts?limit=100');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $legacyCollection = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($legacyCollection);
        self::assertArrayHasKey('contacts', $legacyCollection);
        self::assertIsArray($legacyCollection['contacts']);

        $legacyIds = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            array_values($legacyCollection['contacts'])
        );

        self::assertContains((int) $ownContact->getId(), $legacyIds);
        self::assertNotContains((int) $foreignContact->getId(), $legacyIds);

        $this->client->request('GET', '/api/v2/contacts?page=1');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $v2Collection = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($v2Collection);
        self::assertArrayHasKey('member', $v2Collection);
        self::assertIsArray($v2Collection['member']);

        $v2Ids = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            $v2Collection['member']
        );

        self::assertContains((int) $ownContact->getId(), $v2Ids);
        self::assertNotContains((int) $foreignContact->getId(), $v2Ids);
    }

    public function testViewOwnCollectionReportsOwnedTotalAcrossPagesOnApiV2(): void
    {
        $owner = $this->createUserWithPermissions(
            username: 'owner.pagination.user',
            email: 'owner.pagination.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother', 'editother', 'deleteother'],
                'api:access' => ['full'],
            ]
        );
        $viewer = $this->createUserWithPermissions(
            username: 'viewer.pagination.user',
            email: 'viewer.pagination.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        $ownedContactIds = [];
        for ($i = 1; $i <= 4; ++$i) {
            $ownedContactIds[] = (int) $this->createContactOwnedBy($viewer, sprintf('owned.%d@example.test', $i))->getId();
        }

        for ($i = 1; $i <= 12; ++$i) {
            $this->createContactOwnedBy($owner, sprintf('foreign.%d@example.test', $i));
        }

        $this->client->getCookieJar()->clear();
        $this->client->setServerParameter('PHP_AUTH_USER', $viewer->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/contacts?page=1&itemsPerPage=2');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $firstPage = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($firstPage);
        self::assertArrayHasKey('member', $firstPage);
        self::assertArrayHasKey('totalItems', $firstPage);
        self::assertIsArray($firstPage['member']);
        self::assertSame(4, (int) $firstPage['totalItems']);

        $firstPageIds = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            $firstPage['member']
        );

        foreach ($firstPageIds as $id) {
            self::assertContains($id, $ownedContactIds);
        }

        $this->client->request('GET', '/api/v2/contacts?page=2&itemsPerPage=2');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $secondPage = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($secondPage);
        self::assertArrayHasKey('member', $secondPage);
        self::assertArrayHasKey('totalItems', $secondPage);
        self::assertIsArray($secondPage['member']);
        self::assertSame(4, (int) $secondPage['totalItems']);

        $secondPageIds = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            $secondPage['member']
        );

        foreach ($secondPageIds as $id) {
            self::assertContains($id, $ownedContactIds);
        }
    }

    public function testViewOtherPermissionCollectionShowsOwnAndForeignOnApiV1AndV2(): void
    {
        $viewOtherUser = $this->createUserWithPermissions(
            username: 'other.only.user',
            email: 'other.only.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother'],
                'api:access' => ['full'],
            ]
        );

        $anotherOwner = $this->createUserWithPermissions(
            username: 'another.owner.user',
            email: 'another.owner.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother'],
                'api:access' => ['full'],
            ]
        );

        $ownContact = new Lead();
        $ownContact->setFirstname('Own Contact');
        $ownContact->setLastname('Hidden');
        $ownContact->setEmail('own.hidden@example.test');
        $ownContact->setOwner($viewOtherUser);
        $ownContact->setCreatedBy($viewOtherUser);

        $foreignContact = new Lead();
        $foreignContact->setFirstname('Foreign Contact');
        $foreignContact->setLastname('Visible');
        $foreignContact->setEmail('foreign.visible@example.test');
        $foreignContact->setOwner($anotherOwner);
        $foreignContact->setCreatedBy($anotherOwner);

        $this->em->persist($ownContact);
        $this->em->persist($foreignContact);
        $this->em->flush();
        $this->em->clear();

        $this->client->getCookieJar()->clear();
        $this->client->setServerParameter('PHP_AUTH_USER', $viewOtherUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/contacts?start=0&limit=10');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $legacyCollection = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($legacyCollection);
        self::assertArrayHasKey('contacts', $legacyCollection);
        self::assertIsArray($legacyCollection['contacts']);

        $legacyIds = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            array_values($legacyCollection['contacts'])
        );

        self::assertContains((int) $ownContact->getId(), $legacyIds);
        self::assertContains((int) $foreignContact->getId(), $legacyIds);

        $this->client->request('GET', '/api/v2/contacts?page=1&itemsPerPage=10');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $collection = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($collection);
        self::assertArrayHasKey('totalItems', $collection);
        self::assertArrayHasKey('member', $collection);
        self::assertSame(2, (int) $collection['totalItems'], 'Expected own and foreign contacts');

        $v2Ids = array_map(
            static fn (array $contact): int => (int) $contact['id'],
            $collection['member']
        );

        self::assertContains((int) $ownContact->getId(), $v2Ids);
        self::assertContains((int) $foreignContact->getId(), $v2Ids);
    }

    public function testViewOwnCollectionRespectsOwnerFieldAfterReassignmentOnApiV2(): void
    {
        $originalOwner = $this->createUserWithPermissions(
            username: 'original.contact.owner',
            email: 'original.contact.owner@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );
        $newOwner = $this->createUserWithPermissions(
            username: 'new.contact.owner',
            email: 'new.contact.owner@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        // Create a contact where originalOwner is both creator and owner
        $contact = new Lead();
        $contact->setFirstname('Reassigned');
        $contact->setLastname('Contact');
        $contact->setEmail('reassigned.contact@example.test');
        $contact->setCreatedBy($originalOwner);
        $contact->setOwner($originalOwner);
        $this->em->persist($contact);
        $this->em->flush();

        // Reassign to newOwner (owner changes, but createdBy stays originalOwner)
        $contact->setOwner($newOwner);
        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        // Test 1: newOwner (current owner) SHOULD see the contact
        $newOwner = $this->em->getRepository(User::class)->findOneBy(['username' => 'new.contact.owner']);
        \assert($newOwner instanceof User);
        $this->loginUser($newOwner);
        $this->client->setServerParameter('PHP_AUTH_USER', $newOwner->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/contacts?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(
            1,
            $data['member'],
            'New owner should see the reassigned contact (owner field takes precedence over createdBy)'
        );
        self::assertSame($contact->getId(), $data['member'][0]['id']);

        // Test 2: originalOwner (creator but no longer owner) should NOT see the contact
        $originalOwner = $this->em->getRepository(User::class)->findOneBy(['username' => 'original.contact.owner']);
        \assert($originalOwner instanceof User);
        $this->loginUser($originalOwner);
        $this->client->setServerParameter('PHP_AUTH_USER', $originalOwner->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/contacts?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(
            0,
            $data['member'],
            'Original creator should NOT see the contact after reassignment (owner field takes precedence)'
        );
    }

    private function createContactOwnedBy(User $owner, string $email): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Owned');
        $contact->setLastname('Contact');
        $contact->setEmail($email);
        $contact->setOwner($owner);
        $contact->setCreatedBy($owner);

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }
}
