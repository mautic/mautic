<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Functional tests for user preference functionality.
 *
 * This test class verifies that:
 * - Users can successfully set their preferences via API endpoint
 * - Preferences are properly stored in the database
 * - The system responds with appropriate success messages
 *
 * Specifically tests the column visibility preference for contacts as a representative example.
 */
class UserPreferenceTest extends MauticMysqlTestCase
{
    public function testUserCanSetPreference(): void
    {
        $user = $this->createTestUser();

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(
            Request::METHOD_POST,
            '/s/user/set-preference',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'preference' => 'user_column_visibility_contacts',
                'value'      => ['name', 'email', 'id'],
            ])
        );

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('"success":true', $response->getContent());

        $reloaded = $this->em->getRepository(User::class)->find($user->getId());
        $prefs    = $reloaded->getPreferences();

        $this->assertArrayHasKey('user_column_visibility_contacts', $prefs);
        $this->assertEquals(['name', 'email', 'id'], $prefs['user_column_visibility_contacts']);
    }

    private function createTestUser(): User
    {
        $role = $this->em->getRepository(Role::class)->findOneBy(['isAdmin' => true]);

        $user = new User();
        $user->setUsername('pref_user');
        $user->setEmail('pref_user@example.com');
        $user->setFirstName('Pref');
        $user->setLastName('User');
        $user->setRole($role);

        $hasher = static::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
