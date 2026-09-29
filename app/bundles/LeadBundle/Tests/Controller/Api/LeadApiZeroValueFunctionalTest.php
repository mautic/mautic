<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function testPatchPersistsZeroStringOnATextField(): void
    {
        $contactId = $this->createContact('zero-text@example.com', [self::TEXT_FIELD => 'abc']);
        $this->assertSame('abc', $this->getStoredValue($contactId, self::TEXT_FIELD));

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', [self::TEXT_FIELD => '0']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame('0', $this->getStoredValue($contactId, self::TEXT_FIELD));
    }

    public function testPostNewPersistsZeroStringOnATextField(): void
    {
        $contactId = $this->createContact('zero-text-post@example.com', [self::TEXT_FIELD => '0']);

        $this->assertSame('0', $this->getStoredValue($contactId, self::TEXT_FIELD));
    }

    public function testPostNewAppliesAZeroFieldDefault(): void
    {
        $contactId = $this->createContact('zero-default-post@example.com', []);

        $this->assertSame(0, $this->getStoredValue($contactId, self::ZERO_DEFAULT_FIELD));
    }

    public function testPostNewLeavesBlankNumberFieldEmpty(): void
    {
        $contactId = $this->createContact('blank-post@example.com', [self::NUMBER_FIELD => '']);

        $this->assertNull($this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    public function testSetFieldValuesDoesNotOverwriteStoredNumberWithBlank(): void
    {
        $contactId = $this->createContact('blank-stored@example.com', [self::NUMBER_FIELD => 5]);
        $this->em->clear();

        $leadModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $leadModel);
        $contact = $leadModel->getEntity($contactId);
        $this->assertInstanceOf(Lead::class, $contact);

        // Integrations and the campaign Update contact action pass raw values straight to setFieldValues().
        $leadModel->setFieldValues($contact, [self::NUMBER_FIELD => ''], false, false);
        $leadModel->saveEntity($contact);

        $this->assertSame(5, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    #[DataProvider('nonNumericTextProvider')]
    public function testSetFieldValuesDoesNotOverwriteStoredNumberWithNonNumericText(string $value): void
    {
        $contactId = $this->createContact('non-numeric-'.md5($value).'@example.com', [self::NUMBER_FIELD => 5]);
        $this->em->clear();

        $leadModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $leadModel);
        $contact = $leadModel->getEntity($contactId);
        $this->assertInstanceOf(Lead::class, $contact);

        $leadModel->setFieldValues($contact, [self::NUMBER_FIELD => $value], false, false);
        $leadModel->saveEntity($contact);

        $this->assertSame(5, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    public static function nonNumericTextProvider(): \Generator
    {
        yield 'whitespace' => [' '];
        yield 'text' => ['abc'];
        yield 'placeholder' => ['n/a'];
    }

    public function testPatchPersistsZeroNumberField(): void
    {
        $contactId = $this->createContact('zero-patch@example.com', [self::NUMBER_FIELD => 5]);
        $this->assertSame(5, $this->getStoredValue($contactId, self::NUMBER_FIELD));

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/'.$contactId.'/edit', [self::NUMBER_FIELD => 0]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $this->client->getResponse()->getContent());

        $this->assertSame(0, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

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

    public function testPostNewWritesSentZeroOnIdentifierMatch(): void
    {
        $email     = 'identifier-match-zero@example.com';
        $contactId = $this->createContact($email, [self::NUMBER_FIELD => 5]);

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', [
            'email'            => $email,
            self::NUMBER_FIELD => 0,
        ]);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($contactId, $response['contact']['id'], 'Expected the identifier match to update the same contact.');

        $this->assertSame(0, $this->getStoredValue($contactId, self::NUMBER_FIELD));
    }

    public function testPatchDoesNotWriteZeroDefaultInjectedByTheFormForAnUnsentField(): void
    {
        // Persisted directly so LeadModel::saveEntity() does not apply the field default.
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

        $fieldModel = self::getContainer()->get(FieldModel::class);
        $this->assertInstanceOf(FieldModel::class, $fieldModel);
        $fieldModel->saveEntity($field);

        $this->em->flush();
    }
}
