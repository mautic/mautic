<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

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
        $user = static::getContainer()->get('mautic.user.model.user')->getEntity(1);

        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

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

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());
        $this->assertEquals([], json_decode($response->getContent(), true));

        $reloaded = $this->em->getRepository(User::class)->find($user->getId());
        $prefs    = $reloaded->getPreferences();

        $this->assertArrayHasKey('user_column_visibility_contacts', $prefs);
        $this->assertEquals(['name', 'email', 'id'], $prefs['user_column_visibility_contacts']);
    }
}
