<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\ApiPlatform;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;

/**
 * Tests that the User API endpoints properly handle password as write-only field.
 *
 * The password field should:
 * - Accept values when creating/updating users (write-only via user:write group)
 * - NEVER be returned in API responses (not in user:read group)
 * - Be hashed before storage in the database
 *
 * This ensures that password hashes are never exposed through the API,
 * which is critical for security.
 */
final class UserApiTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'users',
            'roles',
        ]);
    }

    /**
     * Test that password hash is not exposed in API GET responses.
     */
    public function testPasswordHashNotExposedInGet(): void
    {
        // Use the default admin user that exists in the database
        $adminUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertNotNull($adminUser, 'Admin user should exist');

        $userId = $adminUser->getId();

        // Test GET - password should not be in response
        $this->client->request('GET', "/api/v2/users/{$userId}");
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Assert password is not in the response
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('username', $responseData);
        $this->assertSame('admin', $responseData['username']);
    }

    /**
     * Test that password is not exposed in GET collection endpoint.
     */
    public function testPasswordNotExposedInCollection(): void
    {
        // Test GET collection
        $this->client->request('GET', '/api/v2/users');
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // ApiPlatform uses 'member' for collection items
        $this->assertArrayHasKey('member', $responseData);
        $this->assertIsArray($responseData['member']);
        $this->assertNotEmpty($responseData['member'], 'Should have at least one user');

        // Check each user in the collection
        foreach ($responseData['member'] as $userData) {
            $this->assertArrayNotHasKey('password', $userData, 'Password should not be exposed in collection');
            $this->assertArrayHasKey('username', $userData);
        }
    }
}
