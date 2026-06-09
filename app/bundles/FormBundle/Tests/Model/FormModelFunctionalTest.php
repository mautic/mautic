<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @see https://github.com/mautic/mautic/issues/11558
     */
    public function testConditionalFieldsPreserveOrderAfterDatabaseSave(): void
    {
        /** @var FormModel $formModel */
        $formModel = static::getContainer()->get('mautic.form.model.form');

        $parentKey     = 'mautic_parent';
        $sessionFields = [
            $parentKey => [
                'id'         => $parentKey,
                'label'      => 'Yes or No',
                'alias'      => 'yes_no',
                'type'       => 'select',
                'showLabel'  => 1,
                'saveResult' => 1,
                'properties' => [
                    'list' => [
                        'list' => [
                            ['label' => 'Yes', 'value' => 'yes'],
                            ['label' => 'No', 'value' => 'no'],
                        ],
                    ],
                ],
            ],
            'child_a' => [
                'id'         => 'child_a',
                'label'      => 'Question A',
                'alias'      => 'question_a',
                'type'       => 'text',
                'showLabel'  => 1,
                'saveResult' => 1,
                'parent'     => $parentKey,
                'conditions' => [
                    'expr'   => 'in',
                    'any'    => 0,
                    'values' => ['yes'],
                ],
            ],
            'child_b' => [
                'id'         => 'child_b',
                'label'      => 'Question B',
                'alias'      => 'question_b',
                'type'       => 'text',
                'showLabel'  => 1,
                'saveResult' => 1,
                'parent'     => $parentKey,
                'conditions' => [
                    'expr'   => 'in',
                    'any'    => 0,
                    'values' => ['yes'],
                ],
            ],
            'child_c' => [
                'id'         => 'child_c',
                'label'      => 'Question C',
                'alias'      => 'question_c',
                'type'       => 'text',
                'showLabel'  => 1,
                'saveResult' => 1,
                'parent'     => $parentKey,
                'conditions' => [
                    'expr'   => 'in',
                    'any'    => 0,
                    'values' => ['yes'],
                ],
            ],
        ];

        $form = new Form();
        $form->setName('Conditional field order test');
        $form->setAlias('cond_order_'.uniqid());
        $form->setIsPublished(false);

        $formModel->setFields($form, $sessionFields);
        $formModel->saveEntity($form);

        $formId = $form->getId();
        $this->em->clear();

        $reloaded = $formModel->getEntity($formId);
        self::assertNotNull($reloaded);
        self::assertSame(['Question A', 'Question B', 'Question C'], $this->getConditionalChildLabels($reloaded));

        $resaveSessionFields = [];
        foreach ($reloaded->getFields() as $field) {
            $sessionId = 'mautic_re_'.$field->getId();
            $field->setSessionId($sessionId);
            $fieldData = $field->convertToArray();
            unset($fieldData['form']);
            $fieldData['id']                 = $field->getId();
            $resaveSessionFields[$sessionId] = $fieldData;
        }

        $formModel->setFields($reloaded, $resaveSessionFields);
        $formModel->saveEntity($reloaded);
        $this->em->clear();

        $savedAgain = $formModel->getEntity($formId);
        self::assertNotNull($savedAgain);
        self::assertSame(['Question A', 'Question B', 'Question C'], $this->getConditionalChildLabels($savedAgain));

        $formModel->deleteEntity($savedAgain);
    }

    /**
     * @return string[]
     */
    private function getConditionalChildLabels(Form $form): array
    {
        $labels = [];

        foreach ($form->getFields() as $field) {
            if ($field->getParent()) {
                $labels[] = $field->getLabel();
            }
        }

        return $labels;
    }

    public function testPopulateValuesWithGetParameters(): void
    {
        $formId     = $this->createForm();
        $crawler    = $this->client->request(
            Request::METHOD_GET,
            "/s/forms/preview/{$formId}?email=testform@test.com&firstname=test&description=test-test&checkbox=val1|val3"
        );
        $inputValue = $crawler->filter('input[type=email]')->attr('value');
        self::assertSame('testform@test.com', $inputValue);
        $inputValue = $crawler->filter('input[type=text]')->attr('value');
        self::assertSame('test', $inputValue);
        $inputValue = $crawler->filter('textarea[name^=mauticform]')->html();
        self::assertSame('test-test', $inputValue);
        $inputValue = $crawler->filter('textarea[name^=mauticform]')->html();
        self::assertSame('test-test', $inputValue);
        $inputValue = $crawler->filter('input[value^=val1]')->attr('checked');
        self::assertSame('checked', $inputValue);
        $inputValue = $crawler->filter('input[value^=val2]')->attr('checked');
        self::assertNull($inputValue);
        $inputValue = $crawler->filter('input[value^=val3]')->attr('checked');
        self::assertSame('checked', $inputValue);

        $this->createPage($formId);
        $crawler    = $this->client->request(Request::METHOD_GET, '/test-page?email=test%2Bpage@test.com&firstname=test');
        $inputValue = $crawler->filter('input[type=email]')->attr('value');
        self::assertSame('test+page@test.com', $inputValue);
        $inputValue = $crawler->filter('input[type=text]')->attr('value');
        self::assertSame('test', $inputValue);
    }

    private function createForm(): int
    {
        $formPayload = [
            'name'        => 'Test Form',
            'formType'    => 'standalone',
            'description' => 'API test',
            'fields'      => [
                [
                    'label'     => 'firstname',
                    'alias'     => 'firstname',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'email',
                    'alias'     => 'email',
                    'type'      => 'email',
                    'leadField' => 'email',
                ],
                [
                    'label'     => 'description',
                    'alias'     => 'description',
                    'type'      => 'textarea',
                ],
                [
                    'label'          => 'checkbox',
                    'alias'          => 'checkbox',
                    'type'           => 'checkboxgrp',
                    'properties'     => [
                        'syncList'   => 0,
                        'optionlist' => [
                            'list'   => [
                                [
                                    'label' => 'val1',
                                    'value' => 'val1',
                                ],
                                [
                                    'label' => 'val2',
                                    'value' => 'val2',
                                ],
                                [
                                    'label' => 'val3',
                                    'value' => 'val3',
                                ],
                            ],
                        ],
                        'labelAttributes' => null,
                    ],
                ],
                [
                    'label'     => 'Submit',
                    'alias'     => 'submit',
                    'type'      => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);

        return $response['form']['id'];
    }

    private function createPage(int $formId): void
    {
        $pagePayload = [
            'title'        => 'Test Page',
            'alias'        => 'test-page',
            'description'  => 'This is my first page created via API.',
            'isPublished'  => true,
            'customHtml'   => '<!DOCTYPE html>
             <html>
                <head>
                    <title>Test Page</title>
                    <meta name="description" content="Test Page" />
                </head>
                <body>
                    <div class="container">
                        <div>{form='.$formId.'}</div>
                    </div>
                </body>
            </html>',
        ];

        $this->client->request('POST', '/api/pages/new', $pagePayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }
}
