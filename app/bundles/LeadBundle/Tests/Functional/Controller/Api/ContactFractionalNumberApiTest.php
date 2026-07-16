<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ContactFractionalNumberApiTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private string $fieldAlias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAlias = 'frac_test_'.uniqid();

        $fieldPayload = [
            'label'      => 'Fraction Test',
            'alias'      => $this->fieldAlias,
            'type'       => 'number',
            'group'      => 'core',
            'object'     => 'lead',
            'properties' => [],
        ];

        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', $fieldPayload);
        $this->assertResponseIsSuccessful();
    }

    public function testFractionalNumericFieldIsSilentlyDiscardedOnPatch(): void
    {
        // Create contact with integer value
        $payload = [
            'firstname'       => 'FractionTest',
            $this->fieldAlias => 1,
        ];
        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $this->assertResponseIsSuccessful();
        $response  = json_decode($this->client->getResponse()->getContent(), true);
        $contactId = $response['contact']['id'];

        // Now PATCH with fractional value as string "0.5"
        $this->client->request(Request::METHOD_PATCH, "/api/contacts/{$contactId}/edit", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            $this->fieldAlias => '0.5',
        ]));
        $this->assertResponseIsSuccessful();

        // Verify
        $this->client->request(Request::METHOD_GET, "/api/contacts/{$contactId}");
        $contact = json_decode($this->client->getResponse()->getContent(), true);
        $fields  = $contact['contact']['fields']['all'] ?? [];

        $this->assertNotNull(
            $fields[$this->fieldAlias] ?? null,
            'BUG: Fractional value 0.5 was silently discarded on PATCH'
        );

        // Verify the actual stored value
        $storedValue = $fields[$this->fieldAlias] ?? 'NOT FOUND';
        $this->assertNotNull($storedValue);
        // If the bug exists, stored value would be 1 (unchanged from create)
        // If the fix works, stored value would be 0.5
        $this->assertEquals(0.5, (float) $storedValue, sprintf('Expected 0.5, got %s', var_export($storedValue, true)));
    }
}
