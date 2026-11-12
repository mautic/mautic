<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\FieldRepository;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FormApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        self::getContainer()->get(EventDispatcherInterface::class)->addListener(
            FormEvents::FORM_ON_BUILD,
            static function (FormBuilderEvent $event): void {
                $event->addFormField('test.custom', [
                    'label'          => 'Test custom field',
                    'formType'       => FormType::class,
                    'template'       => '@MauticForm/Field/text.html.twig',
                    'builderOptions' => [
                        'addMappedFieldList' => false,
                        'addIsRequired'      => false,
                        'addDefaultValue'    => false,
                        'addSaveResult'      => false,
                    ],
                ]);
            },
        );
    }

    private const TEST_PAYLOAD = [
        'name'        => 'API form',
        'description' => 'Form created via API test',
        'formType'    => 'standalone',
        'isPublished' => true,
        'fields'      => [
            [
                'label'        => 'Email',
                'type'         => 'text',
                'alias'        => 'email',
                'mappedObject' => 'contact',
                'mappedField'  => 'email',
                'showLabel'    => true,
                'isRequired'   => true,
            ],
            [
                'label'     => 'Number',
                'type'      => 'number',
                'alias'     => 'number',
                'leadField' => 'points', // @deprecated Setting leadField, no mappedField or mappedObject (BC).
            ],
            [
                'label'     => 'Company',
                'type'      => 'text',
                'alias'     => 'company',
                'leadField' => 'company', // @deprecated Setting leadField, no mappedField or mappedObject (BC).
            ],
            [
                'label'     => 'Company Phone',
                'type'      => 'tel',
                'alias'     => 'phone',
                'leadField' => 'companyphone', // @deprecated Setting leadField, no mappedField or mappedObject (BC).
            ],
            [
                'label'        => 'Country',
                'type'         => 'country',
                'alias'        => 'country',
                'mappedObject' => 'contact',
                'mappedField'  => 'country',
            ],
            [
                'label'      => 'Multiselect',
                'type'       => 'select',
                'alias'      => 'multiselect',
                'properties' => [
                    'syncList' => 0,
                    'multiple' => 1,
                    'list'     => [
                        'list' => [
                            [
                                'label' => 'One',
                                'value' => 'one',
                            ],
                            [
                                'label' => 'Two',
                                'value' => 'two',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Submit',
                'type'  => 'button',
            ],
        ],
        'actions' => [
        ],
        'postAction' => 'return',
    ];

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $expectedResponse
     */
    #[DataProvider('formDataProvider')]
    public function testAddAndEditForms(array $payload, array $expectedResponse): void
    {
        $this->client->request('POST', '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Return code must be 201.');

        $formId = $response['form']['id'];
        $this->assertGreaterThan(0, $formId);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        $counter = count($payload['fields']);
        for ($i = 0; $i < $counter; ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($expectedResponse['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
            $this->assertEquals($expectedResponse['fields'][$i]['mappedField'], $response['form']['fields'][$i]['mappedField']);
            $this->assertEquals($expectedResponse['fields'][$i]['mappedObject'], $response['form']['fields'][$i]['mappedObject']);
        }

        // Edit PATCH:
        $this->client->request('PATCH', "/api/forms/{$formId}/edit", ['name' => $expectedResponse['newName']]);
        $clientResponse = $this->client->getResponse();
        $responsePatch  = json_decode($clientResponse->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertSame($formId, $responsePatch['form']['id'], 'ID of the created form does not match with the edited one.');
        $this->assertEquals($expectedResponse['newName'], $responsePatch['form']['name']);
        $this->assertEquals($payload['formType'], $responsePatch['form']['formType']);
        $this->assertEquals($payload['isPublished'], $responsePatch['form']['isPublished']);
        $this->assertEquals($payload['description'], $responsePatch['form']['description']);
        $this->assertIsArray($responsePatch['form']['fields']);
        $this->assertCount(count($payload['fields']), $responsePatch['form']['fields']);
        $counter = count($payload['fields']);
        for ($i = 0; $i < $counter; ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $responsePatch['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $responsePatch['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $responsePatch['form']['fields'][$i]['type']);
            $this->assertEquals($expectedResponse['fields'][$i]['leadField'], $responsePatch['form']['fields'][$i]['leadField']);
            $this->assertEquals($expectedResponse['fields'][$i]['mappedField'], $responsePatch['form']['fields'][$i]['mappedField']);
            $this->assertEquals($expectedResponse['fields'][$i]['mappedObject'], $responsePatch['form']['fields'][$i]['mappedObject']);
        }
    }

    /**
     * @return \Iterator<int, array<int, array<string, mixed>>>
     */
    public static function formDataProvider(): \Iterator
    {
        yield [
            [
                'name'        => 'Form API test',
                'formType'    => 'standalone',
                'isPublished' => true,
                'description' => 'Functional API test',
                'fields'      => [
                    [
                        'label'     => 'Email',
                        'alias'     => 'email',
                        'type'      => 'text',
                        'leadField' => 'email',
                    ],
                    [
                        'label'        => 'Company Address',
                        'type'         => 'text',
                        'alias'        => 'companyaddress1',
                        'leadField'    => 'companyaddress1',
                    ],
                    [
                        'label'        => 'Company Phone',
                        'type'         => 'tel',
                        'alias'        => 'phone',
                        'leadField'    => 'companyphone',
                    ],
                    [
                        'label'        => 'Country',
                        'type'         => 'country',
                        'alias'        => 'country',
                        'mappedObject' => 'contact',
                        'mappedField'  => 'country',
                    ],
                ],
                'postAction'  => 'return',
            ],
            [
                'newName'      => 'Form API test',
                'fields'       => [
                    [
                        'mappedObject' => 'contact',
                        'leadField'    => 'email',
                        'mappedField'  => 'email',
                    ],
                    [
                        'mappedObject' => 'company',
                        'leadField'    => 'companyaddress1',
                        'mappedField'  => 'companyaddress1',
                    ],
                    [
                        'mappedObject' => 'company',
                        'leadField'    => 'companyphone',
                        'mappedField'  => 'companyphone',
                    ], [
                        'mappedObject' => 'contact',
                        'leadField'    => 'country',
                        'mappedField'  => 'country',
                    ],
                ],
            ],
        ];
        yield [
            [
                'name'        => 'Form',
                'formType'    => 'standalone',
                'isPublished' => true,
                'description' => 'Functional API test2',
                'fields'      => [
                    [
                        'label'        => 'Lastname',
                        'alias'        => 'lastname',
                        'type'         => 'text',
                        'mappedField'  => 'lastname',
                        'mappedObject' => 'contact',
                    ],
                    [
                        'label'        => 'Company Email',
                        'type'         => 'text',
                        'alias'        => 'companyemail1',
                        'mappedField'  => 'companyemail',
                        'mappedObject' => 'company',
                        'leadField'    => 'companyemail',
                    ],
                    [
                        'label'        => 'Phone',
                        'type'         => 'tel',
                        'alias'        => 'phone',
                        'leadField'    => 'position',
                    ],
                ],
                'postAction'  => 'return',
            ],
            [
                'newName'      => 'Form API test',
                'fields'       => [
                    [
                        'mappedObject' => 'contact',
                        'leadField'    => 'lastname',
                        'mappedField'  => 'lastname',
                    ],
                    [
                        'mappedObject' => 'company',
                        'leadField'    => 'companyemail',
                        'mappedField'  => 'companyemail',
                    ],
                    [
                        'mappedObject' => 'contact',
                        'leadField'    => 'position',
                        'mappedField'  => 'position',
                    ],
                ],
            ],
        ];
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testApiResolvesRegisteredCustomFieldMetadata(): void
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'   => 'API form with custom field',
            'fields' => [[
                'label'            => 'Custom field',
                'type'             => 'test.custom',
                'isCustom'         => false,
                'customParameters' => [
                    'template' => '@invalid/template.html.twig',
                ],
            ]],
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getContent());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $formId       = (int) $responseData['form']['id'];

        $this->em->clear();

        $form = self::getContainer()->get(FormRepository::class)->find($formId);
        $this->assertInstanceOf(Form::class, $form);

        $customField = $form->getFields()->filter(
            static fn (Field $field): bool => 'test.custom' === $field->getType(),
        )->first();

        $this->assertInstanceOf(Field::class, $customField);
        $this->assertTrue($customField->isCustom());
        $this->assertSame(
            '@MauticForm/Field/text.html.twig',
            $customField->getCustomParameters()['template'] ?? null,
        );

        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", [
            'name'   => 'Updated API form with built-in field',
            'fields' => [[
                'id'   => $customField->getId(),
                'type' => 'text',
            ]],
        ]);

        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $this->em->clear();

        $form = self::getContainer()->get(FormRepository::class)->find($formId);
        $this->assertInstanceOf(Form::class, $form);

        $builtInField = $form->getFields()->first();
        $this->assertInstanceOf(Field::class, $builtInField);
        $this->assertFalse($builtInField->isCustom());
        $this->assertSame([], $builtInField->getCustomParameters());
    }

    public function testApiRejectsUnavailableCustomFieldWithoutClearingMetadata(): void
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'   => 'API form with unavailable custom field',
            'fields' => [[
                'label' => 'Custom field',
                'type'  => 'text',
            ]],
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getContent());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $formId       = (int) $responseData['form']['id'];

        $this->em->clear();

        $form = self::getContainer()->get(FormRepository::class)->find($formId);
        $this->assertInstanceOf(Form::class, $form);

        $customField = $form->getFields()->first();
        $this->assertInstanceOf(Field::class, $customField);

        $fieldRepository = self::getContainer()->get(FieldRepository::class);
        $customField     = $fieldRepository->find($customField->getId());
        $this->assertInstanceOf(Field::class, $customField);

        $customParameters = ['template' => '@UnavailablePlugin/Field/custom.html.twig'];
        $customField->setType('plugin.unavailable');
        $customField->setIsCustom(true);
        $customField->setCustomParameters($customParameters);
        $fieldRepository->saveEntity($customField);
        $this->em->clear();

        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", [
            'fields' => [[
                'id'    => $customField->getId(),
                'label' => 'Updated custom field',
            ]],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getContent());

        $this->em->clear();

        $form = self::getContainer()->get(FormRepository::class)->find($formId);
        $this->assertInstanceOf(Form::class, $form);

        $customField = $form->getFields()->first();
        $this->assertInstanceOf(Field::class, $customField);
        $this->assertSame('plugin.unavailable', $customField->getType());
        $this->assertTrue($customField->isCustom());
        $this->assertSame($customParameters, $customField->getCustomParameters());
    }

    public function testBatchApiRejectsUnavailableCustomFieldWithoutCreatingForm(): void
    {
        $formName = 'Batch API form with unavailable custom field';

        $this->client->request(Request::METHOD_POST, '/api/forms/batch/new', [[
            'name'   => $formName,
            'fields' => [[
                'label' => 'Unavailable custom field',
                'type'  => 'plugin.unavailable',
            ]],
        ]]);

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getContent());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $responseData['statusCodes'][0]);
        $this->assertSame(
            'Form field type "plugin.unavailable" is not registered.',
            $responseData['errors'][0]['message'],
        );
        $this->assertSame([], $responseData['forms']);
        $this->assertNull(self::getContainer()->get(FormRepository::class)->findOneBy(['name' => $formName]));
    }

    public function testBatchApiRejectsUnavailableCustomFieldWithoutReturningUnsavedChanges(): void
    {
        $originalName = 'API form before rejected batch update';
        $this->client->request(Request::METHOD_POST, '/api/forms/new', [
            'name'   => $originalName,
            'fields' => [[
                'label' => 'Text field',
                'type'  => 'text',
            ]],
        ]);

        $createResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $createResponse->getContent());

        $createData = json_decode($createResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $formId     = (int) $createData['form']['id'];
        $fieldId    = (int) $createData['form']['fields'][0]['id'];

        $this->client->request(Request::METHOD_PATCH, '/api/forms/batch/edit', [[
            'id'     => $formId,
            'name'   => 'Unsaved batch update',
            'fields' => [[
                'id'   => $fieldId,
                'type' => 'plugin.unavailable',
            ]],
        ]]);

        $updateResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $updateResponse->getContent());

        $updateData = json_decode($updateResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $updateData['statusCodes'][0]);
        $this->assertSame([], $updateData['forms']);

        $this->em->clear();

        $form = self::getContainer()->get(FormRepository::class)->find($formId);
        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame($originalName, $form->getName());
    }

    public function testSingleFormWorkflow(): void
    {
        $payload    = self::TEST_PAYLOAD;
        $fieldCount = count($payload['fields']);
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertArrayHasKey('id', $response['form'], $clientResponse->getContent());

        $formId = $response['form']['id'];

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());
        $this->assertGreaterThan(0, $formId);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertNotEmpty($response['form']['cachedHtml']);
        $this->assertCount($fieldCount, $response['form']['fields']);
        $this->assertEquals($payload['fields'][0]['label'], $response['form']['fields'][0]['label']);
        $this->assertEquals($payload['fields'][0]['type'], $response['form']['fields'][0]['type']);
        $this->assertEquals($payload['fields'][0]['mappedObject'], $response['form']['fields'][0]['mappedObject']);
        $this->assertEquals($payload['fields'][0]['mappedField'], $response['form']['fields'][0]['mappedField']);
        $this->assertEquals(
            $payload['fields'][0]['mappedField'],
            $response['form']['fields'][0]['leadField']
        ); // @deprecated leadField was replaced by mappedField. Check for BC.
        $this->assertEquals($payload['fields'][0]['showLabel'], $response['form']['fields'][0]['showLabel']);
        $this->assertEquals($payload['fields'][0]['isRequired'], $response['form']['fields'][0]['isRequired']);
        $this->assertEquals($payload['fields'][1]['label'], $response['form']['fields'][1]['label']);
        $this->assertEquals($payload['fields'][1]['type'], $response['form']['fields'][1]['type']);
        $this->assertEquals('contact', $response['form']['fields'][1]['mappedObject']);
        $this->assertEquals('points', $response['form']['fields'][1]['mappedField']);
        $this->assertEquals(
            $payload['fields'][1]['leadField'],
            $response['form']['fields'][1]['leadField']
        ); // @deprecated leadField was replaced by mappedField. Check for BC.
        $this->assertTrue($response['form']['fields'][1]['showLabel']);
        $this->assertFalse($response['form']['fields'][1]['isRequired']);
        $this->assertEquals($payload['fields'][2]['label'], $response['form']['fields'][2]['label']);
        $this->assertEquals($payload['fields'][2]['type'], $response['form']['fields'][2]['type']);
        $this->assertEquals('contact', $response['form']['fields'][2]['mappedObject']);
        $this->assertEquals('company', $response['form']['fields'][2]['mappedField']);
        $this->assertEquals(
            $payload['fields'][2]['leadField'],
            $response['form']['fields'][2]['leadField']
        ); // @deprecated leadField was replaced by mappedField. Check for BC.
        $this->assertEquals($payload['fields'][3]['label'], $response['form']['fields'][3]['label']);
        $this->assertEquals($payload['fields'][3]['type'], $response['form']['fields'][3]['type']);
        $this->assertEquals('company', $response['form']['fields'][3]['mappedObject']);
        $this->assertEquals('companyphone', $response['form']['fields'][3]['mappedField']);
        $this->assertEquals(
            $payload['fields'][3]['leadField'],
            $response['form']['fields'][3]['leadField']
        ); // @deprecated leadField was replaced by mappedField. Check for BC.

        // Edit PATCH:
        $patchPayload = [
            'name'   => 'API form renamed',
            'fields' => [
                [
                    'label'        => 'State',
                    'type'         => 'select',
                    'alias'        => 'state',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'state',
                    'parent'       => $response['form']['fields'][4]['id'],
                    'conditions'   => [
                        'expr'   => 'in',
                        'any'    => 1,
                        'values' => [],
                    ],
                    'properties'   => [
                        'syncList' => 1,
                        'multiple' => 0,
                    ],
                ],
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        ++$fieldCount;

        $this->assertResponseIsSuccessful($clientResponse->getContent());
        $this->assertSame($formId, $response['form']['id']);
        $this->assertEquals('API form renamed', $response['form']['name']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertCount($fieldCount, $response['form']['fields']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertNotEmpty($response['form']['cachedHtml']);

        // Edit PUT:
        $payload['description'] .= ' renamed';
        $payload['fields']      = []; // Set fields to an empty array as it would duplicate all fields.
        $payload['postAction']  = 'return'; // Must be present for PUT as all empty values are being cleared.
        $this->client->request(Request::METHOD_PUT, "/api/forms/{$formId}/edit", $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertResponseIsSuccessful($clientResponse->getContent());
        $this->assertSame($formId, $response['form']['id']);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals('Form created via API test renamed', $response['form']['description']);
        $this->assertCount($fieldCount, $response['form']['fields']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertNotEmpty($response['form']['cachedHtml']);

        // Get:
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful($clientResponse->getContent());
        $this->assertSame($formId, $response['form']['id']);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertCount($fieldCount, $response['form']['fields']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertNotEmpty($response['form']['cachedHtml']);

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_apiform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[email]'       => 'john@doe.test',
            'mauticform[number]'      => '123',
            'mauticform[company]'     => 'Doe Corp',
            'mauticform[phone]'       => '+420444555666',
            'mauticform[country]'     => 'Czech Republic',
            'mauticform[state]'       => 'Plzeňský kraj',
            'mauticform[multiselect]' => ['two'],
        ]);
        $this->client->submit($form);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();

        $this->assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        $this->assertSame([
            'email'       => 'john@doe.test',
            'state'       => 'Plzeňský kraj',
            'number'      => 123.0,
            'company'     => 'Doe Corp',
            'phone'       => '+420444555666',
            'country'     => 'Czech Republic',
            'multiselect' => 'two',
        ], $submission->getResults());

        // A contact should be created by the submission.
        $contact = $submission->getLead();
        $this->assertInstanceOf(Lead::class, $contact);

        $this->assertSame('john@doe.test', $contact->getEmail());
        $this->assertSame('Czech Republic', $contact->getCountry());
        $this->assertSame('Plzeňský kraj', $contact->getState());
        $this->assertSame(123, $contact->getPoints());
        $this->assertSame('Doe Corp', $contact->getCompany());

        $companies = $this->em->getRepository(Company::class)->findAll();

        $this->assertCount(1, $companies);

        // A company should be created by the submission.
        /** @var Company $company */
        $company = $companies[0];
        $this->assertSame('Doe Corp', $company->getName());
        $this->assertSame('+420444555666', $company->getPhone());

        // The previous request changes user to anonymous.
        $this->loginUser($this->em->getRepository(User::class)->findOneBy(['username' => 'admin']));

        // Delete:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful($clientResponse->getContent());
        $this->assertNull($response['form']['id']);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertCount($fieldCount, $response['form']['fields']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertNotEmpty($response['form']['cachedHtml']);

        // Get (ensure that the form is gone):
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, $clientResponse->getContent());
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['errors'][0]['code']);
    }

    public function testFormWithChangeTagsAction(): void
    {
        // Create tag:
        $tag1Payload = ['tag' => 'add this'];
        $tag2Payload = ['tag' => 'remove this'];

        $this->client->request('POST', '/api/tags/new', $tag1Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tag1Id         = $response['tag']['id'];

        $this->client->request('POST', '/api/tags/new', $tag2Payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $tag2Id         = $response['tag']['id'];

        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'lab',
                    'alias'     => 'email',
                    'type'      => 'text',
                    'leadField' => 'email',
                ],
            ],
            'actions' => [
                [
                    'name'        => 'Add tags to contact',
                    'description' => 'action description',
                    'type'        => 'lead.changetags',
                    'order'       => 1,
                    'properties'  => [
                        'add_tags'    => [$tag1Id],
                        'remove_tags' => [$tag2Id],
                    ],
                ],
            ],
            'postAction'  => 'return',
        ];

        // Create form with lead.changetags action:
        $this->client->request('POST', '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Return code must be 201.');

        $formId = $response['form']['id'];
        $this->assertGreaterThan(0, $formId);
        $this->assertEquals($payload['name'], $response['form']['name']);
        $this->assertEquals($payload['formType'], $response['form']['formType']);
        $this->assertEquals($payload['isPublished'], $response['form']['isPublished']);
        $this->assertEquals($payload['description'], $response['form']['description']);
        $this->assertIsArray($response['form']['fields']);
        $this->assertCount(count($payload['fields']), $response['form']['fields']);
        $counter = count($payload['fields']);
        for ($i = 0; $i < $counter; ++$i) {
            $this->assertEquals($payload['fields'][$i]['label'], $response['form']['fields'][$i]['label']);
            $this->assertEquals($payload['fields'][$i]['alias'], $response['form']['fields'][$i]['alias']);
            $this->assertEquals($payload['fields'][$i]['type'], $response['form']['fields'][$i]['type']);
            $this->assertEquals($payload['fields'][$i]['leadField'], $response['form']['fields'][$i]['leadField']);
        }
        $this->assertIsArray($response['form']['actions']);
        $this->assertCount(count($payload['actions']), $response['form']['actions']);
        $this->assertEquals($payload['actions'][0]['name'], $response['form']['actions'][0]['name']);
        $this->assertEquals($payload['actions'][0]['description'], $response['form']['actions'][0]['description']);
        $this->assertEquals($payload['actions'][0]['type'], $response['form']['actions'][0]['type']);
        $this->assertEquals($payload['actions'][0]['order'], $response['form']['actions'][0]['order']);
        $this->assertIsArray($response['form']['actions'][0]['properties']['add_tags']);
        $this->assertIsArray($response['form']['actions'][0]['properties']['remove_tags']);
        $this->assertEquals($tag1Payload['tag'], $response['form']['actions'][0]['properties']['add_tags'][0]);
        $this->assertEquals($tag2Payload['tag'], $response['form']['actions'][0]['properties']['remove_tags'][0]);
    }

    public function testFormWithDuplicateFieldAliases(): void
    {
        // Create form
        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'Email',
                    'alias'     => 'email',
                    'type'      => 'text',
                    'leadField' => 'email',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $lastValidFormId = $response['form']['id'];
        $this->assertGreaterThan(0, $lastValidFormId);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Return code must be 201.');

        // Get the last correctly saved form
        $this->client->request(Request::METHOD_GET, '/api/forms/'.$lastValidFormId);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertIsArray($response['form']);
        $this->assertCount(1, $response);
        $this->assertEquals($lastValidFormId, $response['form']['id']);

        // Try to update invalid, non-existent form
        $longAlias      = 'very_long_field_alias_12345';
        $invalidPayload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'test1',
                    'alias'     => 'very_long_field_alias_12345',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'test2',
                    'alias'     => 'very_long_field_alias_123456',
                    'type'      => 'text',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/forms/123/edit', $invalidPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $validationMessage = 'Another field is already using this alias: %alias%. Please choose another or leave it blank to have it autogenerated.';
        $expectedMessage   = str_replace('%alias%', substr($longAlias, 0, 25), $validationMessage);

        $this->assertNotEmpty($response['errors'], 'No errors were returned when trying to save an invalid form');
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response['errors'][0]['code'], 'Return code must be 400.');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, 'Return code must be 400.');
        $this->assertSame($expectedMessage, $response['errors'][0]['message'], 'Returned message is different than expected');
    }

    public function testFormWithInvalidField(): void
    {
        $payload = [
            'name'        => 'Form API test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'description' => 'Functional API test',
            'fields'      => [
                [
                    'label'     => 'test1',
                    'alias'     => 'test1',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'test2',
                    'id'        => 123,
                    'alias'     => 'test2',
                    'type'      => 'invalidField',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/forms/123/edit', $payload);
        $response        = $this->client->getResponse();
        $responseContent = json_decode($response->getContent());

        $this->assertNotEmpty($responseContent->errors, 'No errors were returned when trying to save an invalid form');
        $this->assertSame('Form Field ID 123 not found', $responseContent->errors[0]->message);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'Return code must be 404.');
    }

    public function testFormIsNotUnpublishedWhenEdited(): void
    {
        $payload = [
            'name'        => 'Test Form with Fields',
            'description' => 'Form that triggers transaction error',
            'fields'      => [
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
        ];

        $this->client->request('POST', '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (isset($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Return code must be 201.');

        $formId = $response['form']['id'];
        $this->assertGreaterThan(0, $formId);
        $this->assertTrue($response['form']['isPublished']);

        $newFormName = 'Updated Form Name';

        $payload = [
            'name'   => $newFormName,
            'fields' => [
                [
                    'label' => 'Modified Field',
                    'type'  => 'text',
                    'alias' => 'modified_field',
                ],
                [
                    'label' => 'New Additional Field',
                    'type'  => 'textarea',
                    'alias' => 'additional_field',
                ],
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/forms/'.$formId.'/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (isset($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }
        self::assertResponseStatusCodeSame(Response::HTTP_OK, 'Return code must be 200.');

        $this->assertSame($newFormName, $response['form']['name']);
        $this->assertTrue($response['form']['isPublished']);
    }
}
