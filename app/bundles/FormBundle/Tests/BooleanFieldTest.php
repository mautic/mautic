<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BooleanFieldTest extends MauticMysqlTestCase
{
    private const FORM_SELECTOR          = 'form[data-mautic-form]';

    private const FORM_SELECTOR_BY_ALIAS = 'form[data-mautic-form="%s"]';

    private const VALUE_ZERO             = 'value="0"';

    protected $useCleanupRollback   = false;

    protected bool $authenticateApi = true;

    /**
     * Create a standalone form via API with a single boolean field and a submit button.
     *
     * @param array<string,string> $booleanProperties
     *
     * @return array{formId:int,formAlias:string}
     */
    private function createFormWithBooleanField(array $booleanProperties): array
    {
        $payload = [
            'name'        => 'Boolean Test Form',
            'description' => 'Form created via boolean field test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'postAction'  => 'return',
            'fields'      => [
                [
                    'label'      => 'Consent',
                    'type'       => 'boolean',
                    'alias'      => 'test_boolean',
                    'isRequired' => false,
                    'properties' => $booleanProperties,
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $response  = json_decode($clientResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $formId    = $response['form']['id'];
        $formAlias = $response['form']['alias'];

        return ['formId' => (int) $formId, 'formAlias' => (string) $formAlias];
    }

    private function cleanupForm(int $formId): void
    {
        $this->setUpSymfony($this->configParams);
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful($clientResponse->getContent());
    }

    public function testBooleanFieldRendersAsRadioWithBothLabelsAndSubmits(): void
    {
        $created = $this->createFormWithBooleanField([
            'yes' => 'Custom Yes',
            'no'  => 'Custom No',
        ]);
        $formId = $created['formId'];

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        $html      = $crawler->html();
        $pageAlias = (string) $crawler->filter(self::FORM_SELECTOR)->attr('data-mautic-form');

        $this->assertStringContainsString('Custom Yes', $html);
        $this->assertStringContainsString('Custom No', $html);
        $this->assertStringContainsString(self::VALUE_ZERO, $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('mauticform-boolean', $html);
        $this->assertStringContainsString('mauticform-boolean-positive', $html);
        $this->assertStringContainsString('mauticform-boolean-negative', $html);
        $this->assertStringContainsString('mauticform-radiogrp-radio', $html);
        $this->assertStringNotContainsString('checked="checked"', $html);

        $formCrawler = $crawler->filter(sprintf(self::FORM_SELECTOR_BY_ALIAS, $pageAlias));
        $this->assertCount(1, $formCrawler, $html);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[test_boolean]' => '0',
        ]);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        /** @var Submission $submission */
        $submission = $submissions[0];
        $this->assertSame(['test_boolean' => '0'], $submission->getResults());

        $this->cleanupForm($formId);
    }

    public function testBooleanFieldRendersCheckboxModeWithOnlyYesLabelAndSubmits(): void
    {
        $created = $this->createFormWithBooleanField([
            'yes' => 'I wanna receive comm',
            'no'  => '',
        ]);
        $formId = $created['formId'];

        // Load public form page
        $crawler   = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $html      = $crawler->html();
        $pageAlias = (string) $crawler->filter(self::FORM_SELECTOR)->attr('data-mautic-form');

        // Assert checkbox rendering with positive option only
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('I wanna receive comm', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('name="mauticform[test_boolean][]"', $html);
        $this->assertStringContainsString('mauticform-checkboxgrp-checkbox', $html);
        $this->assertStringContainsString('mauticform-boolean-positive', $html);
        $this->assertStringNotContainsString(self::VALUE_ZERO, $html);

        // Submit unchecked (no value sent)
        $formCrawler = $crawler->filter(sprintf(self::FORM_SELECTOR_BY_ALIAS, $pageAlias));
        $form        = $formCrawler->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        /** @var Submission $submission1 */
        $submission1 = $submissions[0];
        $this->assertSame(['test_boolean' => ''], $submission1->getResults());

        // Submit checked (send value "1")
        $crawler2     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler2 = $crawler2->filter(sprintf(self::FORM_SELECTOR_BY_ALIAS, $pageAlias));
        $form2        = $formCrawler2->form();
        $form2->setValues([
            'mauticform[test_boolean]' => ['1'],
        ]);
        $this->client->submit($form2);
        $this->assertResponseIsSuccessful();

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(2, $submissions);
        /** @var Submission $submission2 */
        $submission2 = $submissions[1];
        $this->assertSame(['test_boolean' => '1'], $submission2->getResults());

        $this->cleanupForm($formId);
    }

    public function testBooleanFieldRendersCheckboxModeWithOnlyNoLabelAndSubmits(): void
    {
        $created = $this->createFormWithBooleanField([
            'yes' => '',
            'no'  => 'I do not want to receive comm',
        ]);
        $formId = $created['formId'];

        $crawler   = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $html      = $crawler->html();
        $pageAlias = (string) $crawler->filter(self::FORM_SELECTOR)->attr('data-mautic-form');

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('I do not want to receive comm', $html);
        $this->assertStringContainsString(self::VALUE_ZERO, $html);
        $this->assertStringContainsString('name="mauticform[test_boolean][]"', $html);
        $this->assertStringContainsString('mauticform-checkboxgrp-checkbox', $html);
        $this->assertStringContainsString('mauticform-boolean-negative', $html);

        // Submit with the checkbox checked (send value "0")
        $formCrawler = $crawler->filter(sprintf(self::FORM_SELECTOR_BY_ALIAS, $pageAlias));
        $form        = $formCrawler->form();
        $form->setValues([
            'mauticform[test_boolean]' => ['0'],
        ]);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);
        /** @var Submission $submission */
        $submission = $submissions[0];
        $this->assertSame(['test_boolean' => '0'], $submission->getResults());

        $this->cleanupForm($formId);
    }
}
