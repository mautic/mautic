<?php

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class FormControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

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

        $divClass = $crawler->filter('#mauticform_postActionProperty')->parents()->first()->attr('class');

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
        $this->assertTrue($this->client->getResponse()->isOk());

        $formElement = $crawler->filterXPath('//form[@name="mauticform"]')->form();
        $this->client->submit($formElement);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->request('GET', sprintf('/s/forms/field/edit/%d?formId=%d', $field->getId(), $form->getId()), [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertJson($response->getContent());

        $content = json_decode($response->getContent())->newContent;
        $crawler = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $options = $crawler->filterXPath('//select[@name="formfield[mappedField]"]')->html();
        $this->assertStringContainsString('<option value="email">Email</option>', $options, 'Email option should not be pre-selected.');
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
