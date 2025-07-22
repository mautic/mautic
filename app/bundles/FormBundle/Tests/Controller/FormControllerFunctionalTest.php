<?php

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        if ('testLabelsForFormAction' === $this->getName(false)) {
            $this->truncateTables('assets', 'categories', 'emails', 'lead_lists');
        }
    }

    /**
     * Index should return status code 200.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/forms');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/forms?search=has%3Aresults&tmpl=list');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * Get form's create page.
     */
    public function testNewActionForm(): void
    {
        $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @see https://github.com/mautic/mautic/issues/10453
     */
    public function testSaveActionForm(): void
    {
        $crawler = $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[name]'        => 'Test',
                'mauticform[renderStyle]' => '0',
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[renderStyle]' => '0',
            ]
        );

        // The form failed to save when saved for the second time with renderStyle=No.
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Internal Server Error - Expected argument of type "null or string", "boolean" given', $this->client->getResponse()->getContent());
    }

    public function testSuccessfulSubmitActionForm(): void
    {
        $crawler = $this->client->request('GET', '/s/forms/new/');
        $this->assertTrue($this->client->getResponse()->isOk());

        $selectedValue = $crawler->filter('#mauticform_postAction option:selected')->attr('value');

        $this->assertEquals('message', $selectedValue);

        $form = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $form->setValues(
            [
                'mauticform[name]' => 'Test',
            ]
        );
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $divClass = $crawler->filter('#mauticform_postActionProperty')->ancestors()->first()->attr('class');

        $this->assertStringContainsString('has-error', $divClass);
    }

    public function testLanguageForm(): void
    {
        $translationsPath = __DIR__.'/resource/language/fr';
        $languagePath     = __DIR__.'/../../../../../translations/fr';
        $filesystem       = new Filesystem();

        // copy all from $translationsPath to $languagePath
        $filesystem->mirror($translationsPath, $languagePath);

        /** @var LanguageHelper $languageHelper */
        $languageHelper = $this->getContainer()->get('mautic.helper.language');

        $formPayload = [
            'name'       => 'Test Form',
            'formType'   => 'campaign',
            'language'   => 'fr',
            'postAction' => 'return',
            'fields'     => [
                [
                    'label'      => 'Email',
                    'alias'      => 'email',
                    'type'       => 'email',
                    'leadField'  => 'email',
                    'isRequired' => true,
                ], [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
        ];
        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), json_encode($languageHelper->getLanguageChoices()));
        $form     = $response['form'];
        $formId   = $form['id'];

        $crawler = $this->client->request('GET', '/form/'.$form['id']);
        $this->assertStringContainsString('Merci de patienter...', $crawler->html());
        $this->assertStringContainsString('Ceci est requis.', $crawler->html());

        $filesystem->remove($languagePath);
    }

    public function testMappedFieldIsNotMarkedAsRemappedUponSavingTheForm(): void
    {
        $form  = $this->createForm('Test', 'test');
        $field = $this->createFormField([
            'label'        => 'Email',
            'type'         => 'email',
        ])->setForm($form);

        // @phpstan-ignore-next-line (using the deprecated method on purpose)
        $field->setLeadField('email');
        $this->em->persist($field);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $this->assertTrue($this->client->getResponse()->isOk());

        $formElement = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $this->client->submit($formElement);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertStringNotContainsString('contact: Email', $response->getContent(), 'Email field should not be marked as mapped.');
    }

    public function testMappedFieldIsNotAutoFilledWhenUpdatingField(): void
    {
        $form  = $this->createForm('Test', 'test');
        $field = $this->createFormField([
            'label' => 'Email',
            'type'  => 'email',
        ])->setForm($form);
        $field->setMappedObject(null);
        $field->setMappedField(null);
        $this->em->persist($field);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $formElement = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $this->client->submit($formElement);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->xmlHttpRequest('GET', sprintf('/s/forms/field/edit/%d?formId=%d', $field->getId(), $form->getId()));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertJson($response->getContent());

        $content = json_decode($response->getContent())->newContent;
        $crawler = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $options = $crawler->filterXPath('//select[@name="formfield[mappedField]"]')->html();
        $this->assertStringContainsString('<option value="email">Email</option>', $options, 'Email option should not be pre-selected.');
    }

    public function testMappedFieldCheckboxGroup(): void
    {
        // Create custom boolean field.
        $customField = new LeadField();
        $customField->setObject('lead');
        $customField->setType('boolean');
        $customField->setLabel('Custom Bool Field');
        $customField->setAlias('custom_boolean_field');
        $customField->setProperties([
            'yes' => 'Absolutely yes',
            'no'  => 'Obviously No',
        ]);

        // Create & add checkbox group type field to form.
        $form  = $this->createForm('Test form', 'test_form');
        $field = $this->createFormField([
            'label' => 'Test Checkbox Group',
            'type'  => 'checkboxgrp',
        ]);
        $field->setMappedObject('contact');
        $field->setMappedField('custom_boolean_field');
        $fieldProperties = [
            'list' => [
                'option1' => 'First Option',
                'option2' => 'Second Option',
            ],
        ];
        $field->setProperties($fieldProperties);
        $field->setForm($form);
        $this->em->persist($field);
        $this->em->flush();
        $this->em->clear();

        // Verify form creation
        $crawler = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $this->assertResponseIsSuccessful();

        // Visit the form preview page
        $crawler = $this->client->request('GET', sprintf('/s/forms/preview/%d', $form->getId()));
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('First Option', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Second Option', $this->client->getResponse()->getContent());
    }

    public function testCreateNewActionUsingBaseTemplateToDisplay(): void
    {
        // Create new form
        $form = $this->createForm('Test', 'test');
        $this->em->persist($form);

        // Fetch the form
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/forms/action/new',
            [
                'formId' => $form->getId(),
                'type'   => 'lead.addutmtags',
            ]
        );
        $this->assertResponseIsSuccessful();
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Save new Send Form Results action
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $content    = $this->client->getResponse()->getContent();
        $actionHtml = json_decode($content, true)['actionHtml'] ?? null;
        $this->assertNotNull($actionHtml, $content);
        $crawler  = new Crawler($actionHtml);
        $editPage = $crawler->filter('.btn-edit')->attr('href');

        // Check the content was not changed
        $this->client->xmlHttpRequest(Request::METHOD_GET, $editPage);
        $this->assertResponseIsSuccessful();
    }

    public function testEditNewActionUsingBaseTemplateToDisplay(): void
    {
        // Create new form
        $form = $this->createForm('Test', 'test');

        // Create action
        $action = $this->createFormAction($form, 'lead.addutmtags');
        $form->addAction(0, $action);
        $this->em->persist($form);

        $this->em->flush();
        $this->em->clear();

        // Edit and submit the form to be able to push action into session
        $crawler     = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $formElement = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $this->client->submit($formElement);
        $this->assertResponseIsSuccessful();

        // Update the Action
        $this->setCsrfHeader();
        $this->client->setServerParameter('HTTP_X-Requested-With', 'XMLHttpRequest');
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            sprintf('/s/forms/action/edit/%s?formId=%s', $action->getId(), $form->getId()),
            ['formId' => $form->getId()], // Query parameters (handled in URL)
            [], // Files
            ['CONTENT_TYPE' => 'application/json'], // server
            json_encode([
                'formaction' => [
                    'id'          => $action->getId(),
                    'name'        => $action->getName(),
                    'type'        => 'lead.addutmtags',
                    'order'       => $action->getOrder(),
                    'properties'  => [],
                    'formId'      => $form->getId(),
                ],
            ])
        );
        $this->assertResponseIsSuccessful();

        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $content    = $this->client->getResponse()->getContent();
        $actionHtml = json_decode($content, true)['actionHtml'] ?? null;
        $this->assertNotNull($actionHtml, $content);
        $crawler  = new Crawler($actionHtml);
        $editPage = $crawler->filter('.btn-edit')->attr('href');

        // Check the content was not changed
        $this->client->xmlHttpRequest(Request::METHOD_GET, $editPage);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array{
     *      type: string,
     *      properties: array<string, mixed>,
     *      entities?: array<object>
     *  } $inputValues The input configuration for the form action
     * @param array<int, array{
     *      message: string,
     *      message_arg: array<string, mixed>
     *  }> $expectedMessages The expected messages with translation arguments
     *
     * @dataProvider dataTestLabelsForFormActions
     */
    public function testLabelsForFormAction(array $inputValues, array $expectedMessages): void
    {
        $form = $this->createForm('test', 'test');

        // Persist entities if provided
        if (!empty($inputValues['entities'])) {
            foreach ($inputValues['entities'] as $entity) {
                $this->em->persist($entity);
            }
        }

        // create form action
        $action = $this->createFormAction($form, $inputValues['type'], $inputValues['properties']);
        $form->addAction(0, $action);
        $this->em->persist($form);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $this->assertResponseIsSuccessful();

        $translator = $this->getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        foreach ($expectedMessages as $expectedMessage) {
            $translatedMessage = $translator->trans($expectedMessage['message'], $expectedMessage['message_arg']);
            $this->assertStringContainsString($translatedMessage, $crawler->html());
        }
    }

    /**
     * @return iterable<string, array{
     *      0: array{
     *          type: string,
     *          properties: array<string, mixed>,
     *          entities?: array<object>
     *      },
     *      1: array<array{
     *          message: string,
     *          message_arg: array<string, mixed>
     *      }>
     *  }>
     */
    public function dataTestLabelsForFormActions(): iterable
    {
        $category = new Category();
        $category->setTitle('Category');
        $category->setAlias('category');
        $category->setBundle('global');

        $asset = new Asset();
        $asset->setTitle('test');
        $asset->setAlias('test');
        $asset->setCategory($category);

        yield 'Action: Download asset using category' => [
            // input
            [
                'type'       => 'asset.download',
                'properties' => [
                    'asset'    => null,
                    'category' => 1,
                ],
                'entities' => [
                    $category,
                    $asset,
                ],
            ],
            // expected
            [
                [
                    'message'     => 'mautic.form.field.asset.use_category',
                    'message_arg' => [
                        '%category_name%' => $category->getTitle(),
                    ],
                ],
            ],
        ];

        yield 'Action: Add to company points' => [
            // input
            [
                'type'       => 'lead.scorecontactscompanies',
                'properties' => ['score' => 10],
            ],
            // expected
            [
                [
                    'message'     => 'mautic.form.form.change_points_by',
                    'message_arg' => ['%value%' => 10],
                ],
            ],
        ];

        yield 'Action: Add to contact points' => [
            // input
            [
                'type'       => 'lead.pointschange',
                'properties' => [
                    'operator' => 'plus',
                    'points'   => 10,
                    'group'    => 0,
                ],
            ],
            // expected
            [
                [
                    'message'     => 'mautic.form.field.points.operation',
                    'message_arg' => [
                        '%operator%' => '(+)',
                        '%points%'   => 10,
                        '%group%'    => '',
                    ],
                ],
            ],
        ];

        yield 'Action: Email to send to user' => [
            // input
            [
                'type'       => 'email.send.user',
                'properties' => [
                    'useremail' => ['email' => 1],
                    'user_id'   => [1],
                ],
                'entities' => [
                    (new Email())->setName('Email')
                        ->setSubject('Test Subject')
                        ->setIsPublished(true),
                ],
            ],
            // expected
            [
                [
                    'message'     => 'Email',
                    'message_arg' => [],
                ],
                [
                    'message'     => 'Email',
                    'message_arg' => [],
                ],
            ],
        ];

        $segmentOne = new LeadList();
        $segmentOne->setName('list one');
        $segmentOne->setAlias('list_one');
        $segmentOne->setPublicName('list_one');
        $segmentOne->setFilters([]);

        $segmentTwo = new LeadList();
        $segmentTwo->setName('list two');
        $segmentTwo->setAlias('list_two');
        $segmentTwo->setPublicName('list_two');
        $segmentTwo->setFilters([]);

        yield 'Action: Change segments' => [
            // input
            [
                'type'       => 'lead.changelist',
                'properties' => [
                    'addToLists'      => [1],
                    'removeFromLists' => [2],
                ],
                'entities' => [
                    $segmentOne,
                    $segmentTwo,
                ],
            ],
            // expected
            [
                [
                    'message'     => $segmentOne->getName(),
                    'message_arg' => [],
                ],
                [
                    'message'     => $segmentTwo->getName(),
                    'message_arg' => [],
                ],
            ],
        ];
    }

    /**
     * @param array<string, int|string|array<mixed>> $properties
     */
    private function createFormAction(Form $form, string $type, array $properties = []): Action
    {
        $action = new Action();

        $action->setName($type);
        $action->setType($type);
        $action->setForm($form);
        $action->setProperties($properties);

        $this->em->persist($action);

        return $action;
    }

    private function createForm(string $name, string $alias): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias($alias);
        $form->setPostActionProperty('Success');
        $this->em->persist($form);

        return $form;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createFormField(array $data = []): Field
    {
        $field     = new Field();
        $aliasSlug = strtolower(str_replace(' ', '_', $data['label'] ?? 'Field 1'));
        $field->setLabel($data['label'] ?? 'Field 1');
        $field->setAlias('field_'.$aliasSlug);
        $field->setType($data['type'] ?? 'text');
        $field->setMappedObject($data['mappedObject'] ?? '');
        $field->setMappedField($data['mappedField'] ?? '');
        $field->setConditions($data['conditions'] ?? []);
        $this->em->persist($field);

        return $field;
    }
}
