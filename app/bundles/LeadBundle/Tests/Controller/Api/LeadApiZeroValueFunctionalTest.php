<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/mautic/mautic/issues/15030
 */
final class LeadApiZeroValueFunctionalTest extends MauticMysqlTestCase
{
    private const NUMBER_FIELD  = 'test_score';

    private const BOOLEAN_FIELD = 'test_optin';

    private const ZERO_DEFAULT_FIELD = 'test_quota';

    private const TEXT_FIELD = 'test_ref';

    /**
     * Creating custom fields issues DDL, which cannot be rolled back in a transaction.
     */
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCustomField(self::NUMBER_FIELD, 'number', ['roundmode' => 4, 'scale' => 0]);
        $this->createCustomField(self::BOOLEAN_FIELD, 'boolean', ['no' => 'No', 'yes' => 'Yes']);
        $this->createCustomField(self::ZERO_DEFAULT_FIELD, 'number', ['roundmode' => 4, 'scale' => 0], '0');
        $this->createCustomField(self::TEXT_FIELD, 'text', []);
    }

    public function testPostNewPersistsZeroNumberField(): void
    {
        $contactId = $this->createContact('zero-post@example.com', [self::NUMBER_FIELD => 0]);

        $this->assertSame(0, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    /**
     * #15030 names Text fields as well as Number. is_numeric('0') is true, so the old
     * filter stripped a text "0" on POST and PATCH too.
     */
    public function testPatchPersistsZeroStringOnATextField(): void
    {
        $contactId = $this->createContact('zero-text@example.com', [self::TEXT_FIELD => 'abc']);
        $this->assertSame('abc', $this->getStoredValue($contactId, self::TEXT_FIELD));

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', [self::TEXT_FIELD => '0']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame('0', $this->getStoredValue($contactId, self::TEXT_FIELD));
    }

    /**
     * A field whose default is 0 receives it on POST /new. This already held before the change
     * (checked at the database level on both sides), so it is a guard that narrowing the filter
     * does not disturb how defaults are applied, not a fix.
     */
    public function testPostNewAppliesAZeroFieldDefault(): void
    {
        $contactId = $this->createContact('zero-default-post@example.com', []);

        $this->assertSame(0, $this->getStoredValue($contactId, self::ZERO_DEFAULT_FIELD));
    }

    public function testPatchPersistsZeroNumberField(): void
    {
        $contactId = $this->createContact('zero-patch@example.com', [self::NUMBER_FIELD => 5]);
        $this->assertSame(5, $this->getStoredValue($contactId, self::NUMBER_FIELD));

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', [self::NUMBER_FIELD => 0]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame(0, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    /**
     * Boolean fields are written through a path that does not reach
     * LeadModel::setFieldValues(), so this already passes. Kept as a regression
     * guard: the empty-check change must not alter it.
     */
    public function testPatchStillPersistsFalseBooleanField(): void
    {
        $contactId = $this->createContact('false-patch@example.com', [self::BOOLEAN_FIELD => 1]);
        $this->assertSame(1, $this->getStoredValue($contactId, self::BOOLEAN_FIELD));

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', [self::BOOLEAN_FIELD => 0]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame(0, $this->getStoredValue($contactId, self::BOOLEAN_FIELD));
    }

    public function testPutStillPersistsZeroNumberField(): void
    {
        $contactId = $this->createContact('zero-put@example.com', [self::NUMBER_FIELD => 5]);

        $this->client->request(Request::METHOD_PUT, '/api/contacts/'.$contactId.'/edit', [
            'email'           => 'zero-put@example.com',
            self::NUMBER_FIELD => 0,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame(0, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    /**
     * Guards the behaviour the removed array_filter was introduced for in bc7db68326:
     * a field the client does not send must not be reset when POST /new matches an
     * existing contact by unique identifier.
     */
    public function testPostNewDoesNotZeroUnsentNumericFieldOnIdentifierMatch(): void
    {
        $email     = 'identifier-match@example.com';
        $contactId = $this->createContact($email, [self::NUMBER_FIELD => 5]);

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', [
            'email'     => $email,
            'firstname' => 'Unchanged',
        ]);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($contactId, $response['contact']['id'], 'Expected the identifier match to update the same contact.');

        $this->assertSame(5, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    /**
     * The narrowed filter's reason to exist. On PATCH the form submits with clearMissing=false,
     * so a field the client omitted is injected from its 'data' option, which falls back to the
     * field's default of 0. That injected zero must not overwrite the stored null.
     *
     * Without the filter this fails: the zero becomes non-empty after the LeadModel fix and is
     * written. It is the one case that distinguishes narrowing the filter from deleting it.
     */
    public function testPatchDoesNotWriteZeroDefaultInjectedByTheFormForAnUnsentField(): void
    {
        // Created directly, not via POST, so prepareParametersForBinding does not apply the default.
        $contact = new Lead();
        $contact->setEmail('zero-default@example.com');
        $this->em->persist($contact);
        $this->em->flush();
        $contactId = $contact->getId();

        $this->assertNull($this->getStoredValue($contactId, self::ZERO_DEFAULT_FIELD), 'Precondition: field starts unset.');

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', ['firstname' => 'Untouched']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertNull(
            $this->getStoredValue($contactId, self::ZERO_DEFAULT_FIELD),
            'A zero the client did not send was injected by the form and must not be persisted.'
        );
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function createContact(string $email, array $fields): int
    {
        $this->client->request(Request::METHOD_POST, '/api/contacts/new', ['email' => $email] + $fields);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $this->client->getResponse()->getContent());

        return json_decode($this->client->getResponse()->getContent(), true)['contact']['id'];
    }

    private function getStoredValue(int $contactId, string $alias): mixed
    {
        $this->em->clear();

        $this->client->request(Request::METHOD_GET, '/api/contacts/'.$contactId);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        return json_decode($this->client->getResponse()->getContent(), true)['contact']['fields']['all'][$alias];
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createCustomField(string $alias, string $type, array $properties, ?string $defaultValue = null): void
    {
        $field = new LeadField();
        $field->setDefaultValue($defaultValue);
        $field->setType($type);
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setLabel(ucfirst(str_replace('_', ' ', $alias)));
        $field->setAlias($alias);
        $field->setProperties($properties);

        $fieldModel = static::getContainer()->get(FieldModel::class);
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);

        $this->em->flush();
    }
}
