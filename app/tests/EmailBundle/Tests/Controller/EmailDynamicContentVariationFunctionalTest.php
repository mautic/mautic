<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmailDynamicContentVariationFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['legacy_builder_enabled'] = true;
        parent::setUp();
    }

    public function testSaveEmailWithSparseDynamicContentVariationIndexAfterDeletingMiddleVariation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save & Close')->form();

        $values = $form->getPhpValues();
        $values['emailform']['emailType']  = 'template';
        $values['emailform']['subject']    = 'Dynamic content variation test';
        $values['emailform']['name']       = 'Dynamic content variation test';
        $values['emailform']['template']   = 'blank';
        $values['emailform']['customHtml'] = '<html><body><p>test</p></body></html>';
        $values['emailform']['isPublished'] = '1';

        // Simulate deleting Variation 1 (index 0) while keeping Variation 2 (index 1).
        $values['emailform']['dynamicContent'][0]['tokenName'] = 'Dynamic Content 1';
        $values['emailform']['dynamicContent'][0]['content']   = 'Default content';
        $values['emailform']['dynamicContent'][0]['filters']   = [
            1 => [
                'content' => 'Variation 2 content',
                'filters' => [],
            ],
        ];

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);
        $response = $this->client->getResponse();

        $this->assertStringNotContainsString('This form should not contain extra fields.', (string) $response->getContent(), 'Deleting a middle dynamic content variation must not trigger extra-fields validation.');
    }

    public function testSaveEmailWithSparseContactFilterIndexInsideVariation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save & Close')->form();

        $values = $form->getPhpValues();
        $values['emailform']['emailType']  = 'template';
        $values['emailform']['subject']    = 'Dynamic content filter test';
        $values['emailform']['name']       = 'Dynamic content filter test';
        $values['emailform']['template']   = 'blank';
        $values['emailform']['customHtml'] = '<html><body><p>test</p></body></html>';
        $values['emailform']['isPublished'] = '1';

        // Simulate deleting the first contact filter inside a variation (index gap).
        $values['emailform']['dynamicContent'][0]['tokenName'] = 'Dynamic Content 1';
        $values['emailform']['dynamicContent'][0]['content']   = 'Default content';
        $values['emailform']['dynamicContent'][0]['filters']   = [
            0 => [
                'content' => 'Variation content',
                'filters' => [
                    1 => [
                        'glue'     => 'and',
                        'field'    => 'firstname',
                        'object'   => 'lead',
                        'type'     => 'text',
                        'operator' => '=',
                        'filter'   => 'John',
                        'display'  => null,
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);
        $response = $this->client->getResponse();

        $this->assertStringNotContainsString('This form should not contain extra fields.', (string) $response->getContent(), 'Deleting a contact filter inside a variation must not trigger extra-fields validation.');
    }

    public function testSaveEmailWithSparseVariationIndexOnJsCreatedDynamicContentBlock(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $form    = $crawler->selectButton('Save & Close')->form();

        $values = $form->getPhpValues();
        $values['emailform']['emailType']  = 'template';
        $values['emailform']['subject']    = 'JS dynamic content block test';
        $values['emailform']['name']       = 'JS dynamic content block test';
        $values['emailform']['template']   = 'blank';
        $values['emailform']['customHtml'] = '<html><body>{dynamiccontent="Dynamic Content 2"}</body></html>';
        $values['emailform']['isPublished'] = '1';

        // GrapesJS adds a second dynamic content block at index 1; deleting its first variation leaves index 1.
        $values['emailform']['dynamicContent'][1]['tokenName'] = 'Dynamic Content 2';
        $values['emailform']['dynamicContent'][1]['content']   = 'Default content 2';
        $values['emailform']['dynamicContent'][1]['filters']   = [
            1 => [
                'content' => 'Remaining variation content',
                'filters' => [],
            ],
        ];

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);
        $response = (string) $this->client->getResponse()->getContent();

        $this->assertStringNotContainsString('This form should not contain extra fields.', $response, 'Sparse variation index on JS-created dynamic content block must save successfully.');
    }

    public function testSaveEmailIgnoresStrayFilterKeyAtVariationLevel(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $form    = $crawler->selectButton('Save & Close')->form();

        $values = $form->getPhpValues();
        $values['emailform']['emailType']  = 'template';
        $values['emailform']['subject']    = 'Stray filter key test';
        $values['emailform']['name']       = 'Stray filter key test';
        $values['emailform']['template']   = 'blank';
        $values['emailform']['customHtml'] = '<html><body><p>test</p></body></html>';
        $values['emailform']['isPublished'] = '1';

        $values['emailform']['dynamicContent'][0]['tokenName'] = 'Dynamic Content 1';
        $values['emailform']['dynamicContent'][0]['content']   = 'Default content';
        $values['emailform']['dynamicContent'][0]['filters']   = [
            'filter' => 'stray',
            0        => [
                'content' => 'Variation content',
                'filters' => [],
            ],
        ];

        $this->client->request(Request::METHOD_POST, $form->getUri(), $values);

        $this->assertStringNotContainsString('This form should not contain extra fields.', (string) $this->client->getResponse()->getContent());
    }
}
