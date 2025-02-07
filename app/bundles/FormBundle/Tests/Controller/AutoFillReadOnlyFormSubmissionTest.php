<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\DomCrawler\Crawler;

final class AutoFillReadOnlyFormSubmissionTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @param array<string, bool|null> $data
     * @param array<string, string>    $expected
     *
     * @dataProvider dataForReadOnlyConfigurationSetting
     */
    public function testFieldConfiguration(array $data, array $expected): void
    {
        // Create a form
        $form = $this->createForm();

        $emailField = $this->createFormField($form, 'Email', 'email', $data['isAutoFill'], $data['isReadOnly'], 'email', 'contact');
        $form->addField(1, $emailField);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', sprintf('/s/forms/edit/%d', $form->getId()));
        $this->assertResponseIsSuccessful();

        $formElement = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $this->client->submit($formElement);
        $this->assertResponseIsSuccessful();

        $this->client->xmlHttpRequest('GET', sprintf('/s/forms/field/edit/%d?formId=%d', $emailField->getId(), $form->getId()));
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent())->newContent;
        $crawler  = new Crawler($content, $this->client->getInternalRequest()->getUri());

        $formValues = $crawler->selectButton('Update')->form()->getPhpValues();

        $this->assertSame($expected['isAutoFill'], $formValues['formfield']['isAutoFill']);
        $this->assertSame($expected['isReadOnly'], $formValues['formfield']['isReadOnly']);
    }

    /**
     * @return iterable<string, array<int, array<string, bool|string|null>>>
     */
    public function dataForReadOnlyConfigurationSetting(): iterable
    {
        yield 'When no behaviour configured' => [
            // given
            [
                'isAutoFill' => null,
                'isReadOnly' => null,
            ],
            // expected
            [
                'isAutoFill' => '0',
                'isReadOnly' => '0',
            ],
        ];

        yield 'When field set to read only' => [
            // given
            [
                'isAutoFill' => true,
                'isReadOnly' => true,
            ],
            // expected
            [
                'isAutoFill' => '1',
                'isReadOnly' => '1',
            ],
        ];

        yield 'When field set to read only and not autofill' => [
            // given
            [
                'isAutoFill' => false,
                'isReadOnly' => true,
            ],
            // expected
            [
                'isAutoFill' => '0',
                'isReadOnly' => '1',
            ],
        ];

        yield 'When field set to autofill and not read only' => [
            // given
            [
                'isAutoFill' => true,
                'isReadOnly' => false,
            ],
            // expected
            [
                'isAutoFill' => '1',
                'isReadOnly' => '0',
            ],
        ];
    }

    public function testAutoFilledFormForReadOnlyAttribute(): void
    {
        $form   = $this->createFormWithFields();
        $formId = $form->getId();

        // Initial request
        $crawler = $this->client->request('GET', '/form/'.$formId);
        $this->assertResponseIsSuccessful();
        $this->assertInputCounts($crawler, 0);

        $formValues = ['john@doe.com', 'John', 'Doe'];

        // Submit the form
        $formCrawler = $crawler->filter('form[id=mauticform_test]');
        $form        = $formCrawler->form([
            'mauticform[email]'     => $formValues[0],
            'mauticform[firstname]' => $formValues[1],
            'mauticform[lastname]'  => $formValues[2],
        ]);
        $this->client->submit($form);

        // Request the form again
        $crawler = $this->client->request('GET', '/form/'.$formId);
        $this->assertResponseIsSuccessful();
        $this->assertInputCounts($crawler, 2);

        $readOnlyInput = $crawler->filterXPath('//input[@readonly]');
        $readOnlyInput->each(function (Crawler $node, $i) use ($formValues) {
            $this->assertStringContainsString('readonly', $node->outerHtml());
            $this->assertSame($formValues[$i], $node->attr('value'));
        });
    }

    private function assertInputCounts(Crawler $crawler, int $readonly): void
    {
        $this->assertCount(3, $crawler->filterXPath('//input[not(@type="hidden")]'));
        $this->assertCount(6, $crawler->filterXPath('//input'));
        $this->assertCount($readonly, $crawler->filterXPath('//input[@readonly]'));
    }

    private function createFormWithFields(): Form
    {
        $form = $this->createForm();

        $emailField = $this->createFormField($form, 'Email', 'email', true, true, 'email', 'contact');
        $form->addField(1, $emailField);

        $firstNameField =  $this->createFormField($form, 'First name', 'text', true, true, 'firstname', 'contact');
        $form->addField(2, $firstNameField);

        $lastNameField = $this->createFormField($form, 'Last name', 'text', false, true, 'lastname', 'contact');
        $form->addField(3, $lastNameField);

        $submitButton = $this->createFormField($form, 'Submit', 'button');
        $form->addField(4, $submitButton);

        $this->em->flush();
        $this->em->clear();

        return $form;
    }

    private function createForm(): Form
    {
        $form = new Form();
        $form->setName('Test');
        $form->setAlias('test');
        $form->setPostActionProperty('Success');
        $this->em->persist($form);

        return $form;
    }

    private function createFormField(
        Form $form,
        string $label,
        string $type,
        ?bool $isAutoFill = false,
        ?bool $isReadOnly = false,
        ?string $mappedField = null,
        ?string $mappedObject = null,
    ): Field {
        $field = new Field();
        $field->setLabel($label);
        $field->setType($type);
        $field->setForm($form);
        $field->setAlias(strtolower(str_replace(' ', '', $label)));
        $field->setIsAutoFill($isAutoFill);
        $field->setIsReadOnly($isReadOnly);
        $field->setMappedObject($mappedObject);
        $field->setMappedField($mappedField);

        $this->em->persist($field);

        return $field;
    }
}
