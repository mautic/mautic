<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class FormApiControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @param array<string, mixed> $formData
     */
    #[DataProvider('formDataProvider')]
    public function testCreateFormWithFieldsAndActions(array $formData, int $expectedStatusCode): void
    {
        $this->client->request(
            'POST',
            '/api/forms/new',
            $formData,
        );

        $response = $this->client->getResponse();
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('form', $responseData);
        $this->assertArrayHasKey('id', $responseData['form']);
        $this->assertArrayHasKey('name', $responseData['form']);
        $this->assertSame($formData['name'], $responseData['form']['name']);

        // Verify fields were created if provided
        if (isset($formData['fields'])) {
            $this->assertArrayHasKey('fields', $responseData['form']);
            $this->assertCount(count($formData['fields']), $responseData['form']['fields']);

            foreach ($formData['fields'] as $index => $expectedField) {
                $actualField = $responseData['form']['fields'][$index];
                $this->assertSame($expectedField['label'], $actualField['label']);
                $this->assertSame($expectedField['type'], $actualField['type']);

                if (isset($expectedField['alias'])) {
                    $this->assertSame($expectedField['alias'], $actualField['alias']);
                }
            }
        }

        // Verify actions were created if provided
        if (isset($formData['actions'])) {
            $this->assertArrayHasKey('actions', $responseData['form']);
            $this->assertCount(count($formData['actions']), $responseData['form']['actions']);
        }
    }

    /**
     * @param array<string, mixed> $initialFormData
     * @param array<string, mixed> $updateData
     */
    #[DataProvider('updateFormDataProvider')]
    public function testUpdateFormWithFieldsAndActions(array $initialFormData, array $updateData, int $expectedStatusCode): void
    {
        $form = $this->createForm($initialFormData);

        $this->client->request(
            'PUT',
            '/api/forms/'.$form->getId().'/edit',
            $updateData,
        );

        $response = $this->client->getResponse();
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('form', $responseData);
        $this->assertSame($form->getId(), $responseData['form']['id']);

        // Verify updates were applied
        if (isset($updateData['name'])) {
            $this->assertSame($updateData['name'], $responseData['form']['name']);
        }

        // Verify field updates/deletions for PUT requests
        if (isset($updateData['fields'])) {
            $this->assertArrayHasKey('fields', $responseData['form']);
            $this->assertCount(count($updateData['fields']), $responseData['form']['fields']);
        }
    }

    public function testCreateFormWithDuplicateFieldAliasesHandledCorrectly(): void
    {
        $formData = [
            'name'   => 'Test Form with Duplicate Aliases',
            'fields' => [
                [
                    'label' => 'First Field',
                    'type'  => 'text',
                    'alias' => 'duplicate_alias',
                ],
                [
                    'label' => 'Second Field',
                    'type'  => 'text',
                    'alias' => 'duplicate_alias',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/forms/new',
            $formData,
        );

        $response = $this->client->getResponse();

        // The form creation should be rejected due to duplicate aliases
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);

        // Check for error response - could be either 'error' or 'errors' key depending on API format
        $this->assertTrue(
            isset($responseData['error']) || isset($responseData['errors']),
            'Response should contain error information'
        );

        // Check that the error mentions the duplicate alias
        if (isset($responseData['error']['message'])) {
            $this->assertStringContainsString('duplicate_alias', $responseData['error']['message']);
        } elseif (isset($responseData['errors'][0]['message'])) {
            $this->assertStringContainsString('duplicate_alias', $responseData['errors'][0]['message']);
        }
    }

    public function testCreateFormWithInvalidFieldData(): void
    {
        $formData = [
            'name'   => 'Test Form with Invalid Field',
            'fields' => [
                [
                    'label' => '', // Empty label should cause validation error
                    'type'  => 'text',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/forms/new',
            $formData,
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUpdateFormRemovesUnspecifiedFieldsOnPut(): void
    {
        // Create form with multiple fields
        $form = $this->createForm([
            'name'   => 'Test Form',
            'fields' => [
                ['label' => 'Field 1', 'type' => 'text'],
                ['label' => 'Field 2', 'type' => 'email'],
                ['label' => 'Field 3', 'type' => 'textarea'],
            ],
        ]);

        // Update with PUT - only include one field (should remove others)
        $firstField = $form->getFields()->first();
        $this->assertNotFalse($firstField, 'Form should have at least one field');

        $updateData = [
            'name'   => 'Updated Form',
            'fields' => [
                [
                    'id'    => $firstField->getId(),
                    'label' => 'Updated Field 1',
                    'type'  => 'text',
                ],
            ],
        ];

        $this->client->request(
            'PUT',
            '/api/forms/'.$form->getId().'/edit',
            $updateData,
        );

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['form']['fields']);
        $this->assertSame('Updated Field 1', $responseData['form']['fields'][0]['label']);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function formDataProvider(): array
    {
        return [
            'simple form' => [
                [
                    'name'        => 'Simple Test Form',
                    'description' => 'A simple test form',
                ],
                Response::HTTP_CREATED,
            ],
            'form with fields' => [
                [
                    'name'   => 'Form with Fields',
                    'fields' => [
                        [
                            'label' => 'First Name',
                            'type'  => 'text',
                            'alias' => 'first_name',
                        ],
                        [
                            'label' => 'Email Address',
                            'type'  => 'email',
                            'alias' => 'email',
                        ],
                    ],
                ],
                Response::HTTP_CREATED,
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function updateFormDataProvider(): array
    {
        return [
            'update name only' => [
                ['name' => 'Original Form'],
                ['name' => 'Updated Form Name'],
                Response::HTTP_OK,
            ],
            'add fields to existing form' => [
                ['name' => 'Form without fields'],
                [
                    'name'   => 'Form with fields',
                    'fields' => [
                        [
                            'label' => 'New Field',
                            'type'  => 'text',
                        ],
                    ],
                ],
                Response::HTTP_OK,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createForm(array $data): Form
    {
        $form = new Form();
        $form->setName($data['name']);
        $form->setDescription($data['description'] ?? '');
        $form->setAlias($data['alias'] ?? strtolower(str_replace(' ', '_', $data['name'])));

        $this->em->persist($form);
        $this->em->flush();

        // If fields are provided, create them through the API to properly test the preSaveEntity method
        if (isset($data['fields'])) {
            $this->client->request(
                'PUT',
                '/api/forms/'.$form->getId().'/edit',
                [
                    'name'   => $form->getName(),
                    'fields' => $data['fields'],
                ]
            );

            // Refresh the form entity to get the updated data
            $this->em->refresh($form);
        }

        return $form;
    }
}
