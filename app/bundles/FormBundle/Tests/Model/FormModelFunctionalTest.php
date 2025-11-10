<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

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
        self::assertNotNull($inputValue, $crawler->html());
        $inputValue = $crawler->filter('input[value^=val2]')->attr('checked');
        self::assertNull($inputValue);
        $inputValue = $crawler->filter('input[value^=val3]')->attr('checked');
        self::assertNotNull($inputValue);

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
