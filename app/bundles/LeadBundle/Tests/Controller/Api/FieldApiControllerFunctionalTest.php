<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IgnoreAnnotation("covers")
 */
final class FieldApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['create_custom_field_in_background'] = 'testFieldApiEndpointsWithBackgroundProcessingEnabled' === $this->getName();

        parent::setUp();
    }

    public function testCreatingMultiselectField(): void
    {
        $payload = [
            'label'               => 'Shops (TB)',
            'alias'               => 'shops',
            'type'                => 'multiselect',
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => false,
            'properties'          => [
                'list' => [
                    ['label' => 'label1', 'value' => 'value1'],
                    ['label' => 'label2', 'value' => 'value2'],
                ],
            ],
        ];

        $typeSafePayload = $this->generateTypeSafePayload($payload);
        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', $typeSafePayload);
        $clientResponse = $this->client->getResponse();
        $fieldResponse  = json_decode($clientResponse->getContent(), true);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());
        Assert::assertTrue($fieldResponse['field']['isPublished']);
        Assert::assertGreaterThan(0, $fieldResponse['field']['id']);
        Assert::assertSame($payload['label'], $fieldResponse['field']['label']);
        Assert::assertSame($payload['alias'], $fieldResponse['field']['alias']);
        Assert::assertSame($payload['type'], $fieldResponse['field']['type']);
        Assert::assertSame($payload['isPubliclyUpdatable'], $fieldResponse['field']['isPubliclyUpdatable']);
        Assert::assertSame($payload['isUniqueIdentifier'], $fieldResponse['field']['isUniqueIdentifier']);
        Assert::assertSame($payload['properties'], $fieldResponse['field']['properties']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, '/api/fields/contact/'.$fieldResponse['field']['id'].'/delete', $payload);
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful($clientResponse->getContent());
    }

    /**
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::saveEntity
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::newEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::editEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::deleteEntityAction
     * @covers \Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand::execute
     */
    public function testFieldApiEndpointsWithBackgroundProcessingEnabled(): void
    {
        $alias   = uniqid('field');
        $payload = $this->getCreatePayload($alias);
        $id      = $this->assertCreateResponse($payload, Response::HTTP_ACCEPTED);

        // Test that the command will create the field
        $commandTester = $this->testSymfonyCommand('mautic:custom-field:create-column', ['--id' => $id]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        // Test fetching
        $this->assertGetResponse($payload, $id);

        // Test editing
        $payload = $this->getEditPayload($id);
        $this->assertPatchResponse($payload, $id, $alias);

        // Test deleting
        $this->assertDeleteResponse($payload, $id, $alias);
    }

    /**
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::saveEntity
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::newEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::editEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::deleteEntityAction
     */
    public function testFieldApiEndpointsWithBackgroundProcessingDisabled(): void
    {
        $alias   = uniqid('field');
        $payload = $this->getCreatePayload($alias);
        $id      = $this->assertCreateResponse($payload, Response::HTTP_CREATED);

        // Test fetching
        $this->assertGetResponse($payload, $id);

        // Test editing
        $payload = $this->getEditPayload($id);
        $this->assertPatchResponse($payload, $id, $alias);

        // Test deleting
        $this->assertDeleteResponse($payload, $id, $alias);
    }

    /**
     * @param array<string, array<string, string>> $properties
     *
     * @dataProvider dataForCreatingNewBooleanFieldApiEndpoint
     */
    public function testCreatingNewBooleanFieldApiEndpoint(array $properties, string $expectedMessage): void
    {
        $payload = [
            'label'               => 'Request a meeting',
            'alias'               => 'meeting',
            'type'                => 'boolean',
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => false,
        ];

        $payload += $properties;

        $typeSafePayload = $this->generateTypeSafePayload($payload);
        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', $typeSafePayload);
        $clientResponse = $this->client->getResponse();
        $errorResponse  = json_decode($clientResponse->getContent(), true);

        Assert::assertArrayHasKey('errors', $errorResponse);
        Assert::assertSame($errorResponse['errors'][0]['code'], $clientResponse->getStatusCode());
        Assert::assertSame($expectedMessage, $errorResponse['errors'][0]['message']);
    }

    /**
     * @return iterable<string, array<int, string|array<string, array<string, string>>>>
     */
    public function dataForCreatingNewBooleanFieldApiEndpoint(): iterable
    {
        yield 'No properties' => [
            [
            ],
            'A \'positive\' label is required.',
        ];

        yield 'Only Yes' => [
            [
                'properties'=> [
                    'yes' => 'Yes',
                ],
            ],
            'A \'negative\' label is required.',
        ];

        yield 'Only No' => [
            [
                'properties'=> [
                    'no' => 'No',
                ],
            ],
            'A \'positive\' label is required.',
        ];
    }

    /**
     * @dataProvider provideEmptyMultiSelectValue
     */
    public function testMultiselectSetDefaultValue(mixed $defaultFieldValue): void
    {
        $fieldAlias = 'test_multi';

        $fieldModel = $this->getContainer()->get(FieldModel::class);
        \assert($fieldModel instanceof FieldModel);

        $fields = $fieldModel->getLeadFieldCustomFields();
        Assert::assertEmpty($fields, 'There are no Custom Fields.');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias($fieldAlias)
            ->setType('multiselect')
            ->setObject('lead')
            ->setProperties([
                'list' => [
                    [
                        'label' => 'Halusky',
                        'value' => 'halusky',
                    ],
                    [
                        'label' => 'Bramborak',
                        'value' => 'bramborak',
                    ],
                    [
                        'label' => 'Makovec',
                        'value' => 'makovec',
                    ],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $contact = new Lead();
        $contact->setEmail('email@acquia.cz');

        $contact->addUpdatedField($fieldAlias, ['bramborak', 'makovec']);
        $contactModel = self::getContainer()->get(LeadModel::class);
        \assert($contactModel instanceof LeadModel);
        $contactModel->saveEntity($contact);

        $this->em->flush();
        $this->em->clear();

        // Call endpoint
        $this->client->request('GET', '/api/contacts/'.(string) $contact->getId());
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $responseJson = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('bramborak|makovec', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);

        // Test patch and values should be updated
        $updatedValues = [
            $fieldAlias  => ['halusky'],
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contact->getId()),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $responseJson   = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('halusky', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);

        // Test empty patch and values should be updated
        $updatedValues = [
            $fieldAlias          => $defaultFieldValue,
            'overwriteWithBlank' => true,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contact->getId()),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $responseJson   = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);
    }

    /**
     * @return \Iterator<string, array<string|array<string|null>|null>>
     */
    public static function provideEmptyMultiSelectValue(): \Iterator
    {
        yield 'null' => [null];
        yield 'empty string value' => [''];
        yield 'empty array with empty string value' => [['']];
        yield 'empty array with null value' => [[null]];
    }

    /**
     * @dataProvider provideEmptySelectValue
     */
    public function testSelectSetDefaultValue(mixed $defaultFieldValue): void
    {
        $fieldAlias = 'test_single';

        $fieldModel = $this->getContainer()->get(FieldModel::class);
        \assert($fieldModel instanceof FieldModel);

        $fields = $fieldModel->getLeadFieldCustomFields();
        Assert::assertEmpty($fields, 'There are no Custom Fields.');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias($fieldAlias)
            ->setType('select')
            ->setObject('lead')
            ->setProperties([
                'list' => [
                    [
                        'label' => 'Halusky',
                        'value' => 'halusky',
                    ],
                    [
                        'label' => 'Bramborak',
                        'value' => 'bramborak',
                    ],
                    [
                        'label' => 'Makovec',
                        'value' => 'makovec',
                    ],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $contact = new Lead();
        $contact->setEmail('email@acquia.cz');

        $contact->addUpdatedField($fieldAlias, ['makovec']);
        $contactModel = self::getContainer()->get(LeadModel::class);
        \assert($contactModel instanceof LeadModel);
        $contactModel->saveEntity($contact);

        $this->em->flush();
        $this->em->clear();

        // Call endpoint
        $this->client->request('GET', '/api/contacts/'.(string) $contact->getId());
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $responseJson = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('makovec', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);

        // Test patch and values should be updated
        $updatedValues = [
            $fieldAlias  => 'halusky',
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contact->getId()),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $responseJson   = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('halusky', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);

        // Test empty patch and values should be updated
        $updatedValues = [
            $fieldAlias          => $defaultFieldValue,
            'overwriteWithBlank' => true,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contact->getId()),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $responseJson   = \json_decode($clientResponse->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('contact', $responseJson);
        self::assertArrayHasKey('fields', $responseJson['contact']);
        self::assertArrayHasKey('core', $responseJson['contact']['fields']);
        self::assertArrayHasKey($fieldAlias, $responseJson['contact']['fields']['core']);
        self::assertArrayHasKey('value', $responseJson['contact']['fields']['core'][$fieldAlias]);
        self::assertSame('', $responseJson['contact']['fields']['core'][$fieldAlias]['value']);
    }

    /**
     * @return \Iterator<string, array<string|null>>
     */
    public static function provideEmptySelectValue(): \Iterator
    {
        yield 'null' => [null];
        yield 'empty string value' => [''];
    }

    private function assertCreateResponse(array $payload, int $expectedStatusCode): int
    {
        // Test creating a new field

        $typeSafePayload = $this->generateTypeSafePayload($payload);
        $this->client->request('POST', '/api/fields/contact/new', $typeSafePayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // should be "accepted" if pushed to the background; otherwise "created"
        $this->assertEquals($expectedStatusCode, $clientResponse->getStatusCode());

        // Assert that the fields returned are what is expected
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));

            if (Response::HTTP_ACCEPTED === $expectedStatusCode && 'isPublished' === $key) {
                // This should be false because the background job publishes once ready
                $this->assertEquals(false, $response['field'][$key]);
                continue;
            }

            $this->assertEquals($value, $response['field'][$key]);
        }

        return $response['field']['id'];
    }

    private function assertGetResponse(array $payload, int $id): void
    {
        // Test get and that the field was published
        $this->client->request('GET', sprintf('/api/fields/contact/%s', $id));
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Assert that the fields returned are what is expected and the field is now published
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));
            $this->assertEquals($value, $response['field'][$key]);
        }
    }

    private function assertPatchResponse(array $payload, int $id, string $alias): void
    {
        $typeSafePayload = $this->generateTypeSafePayload($payload);
        $this->client->request('PATCH', sprintf('/api/fields/contact/%s/edit', $id), $typeSafePayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $response = json_decode($clientResponse->getContent(), true);

        // Assert that the fields returned are what is expected noting that certain fields should not be editable
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));

            match ($key) {
                'alias'  => $this->assertEquals($alias, $response['field'][$key]),
                'object' => $this->assertEquals('lead', $response['field'][$key]),
                'type'   => $this->assertEquals('text', $response['field'][$key]),
                default  => $this->assertEquals($value, $response['field'][$key]),
            };
        }
    }

    private function assertDeleteResponse(array $payload, int $id, string $alias): void
    {
        // Test the field is deleted
        $this->client->request('DELETE', sprintf('/api/fields/contact/%s/delete', $id));
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $response = json_decode($clientResponse->getContent(), true);

        // Assert that the fields returned are what is expected
        foreach ($payload as $key => $value) {
            // use array has key because ID will now be null
            $this->assertArrayHasKey($key, $response['field']);

            match ($key) {
                'id'     => $this->assertNull($response['field'][$key]),
                'alias'  => $this->assertEquals($alias, $response['field'][$key]),
                'object' => $this->assertEquals('lead', $response['field'][$key]),
                'type'   => $this->assertEquals('text', $response['field'][$key]),
                default  => $this->assertEquals($value, $response['field'][$key]),
            };
        }
    }

    private function getCreatePayload(string $alias): array
    {
        return [
            'isPublished'         => true,
            'label'               => 'New Field',
            'alias'               => $alias,
            'type'                => 'text',
            'group'               => 'core',
            'object'              => 'lead',
            'defaultValue'        => 'foobar',
            'isRequired'          => true,
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => true,
            'isVisible'           => false,
            'isListable'          => false,
            'isIndex'             => true, // Must be true, because if isUniqueIdentifier field is true the contact field *must* be indexed.
            'charLengthLimit'     => 255,
            'properties'          => [],
        ];
    }

    private function getEditPayload(int $id): array
    {
        return [
            'id'                  => $id,
            'label'               => 'Foo Bar',
            'alias'               => 'should_not_change',
            'type'                => 'text',
            'group'               => 'core',
            'object'              => 'company',
            'defaultValue'        => 'foobar',
            'isRequired'          => false,
            'isPubliclyUpdatable' => false,
            'isUniqueIdentifier'  => false,
            'isVisible'           => true,
            'isListable'          => true,
            'isIndex'             => false, // Can be false, if isUniqueIdentifier the field is *not* indexed.
            'charLengthLimit'     => 250,
            'properties'          => [],
        ];
    }
}
