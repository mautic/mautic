<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FieldControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testNewEmailFieldFormIsPreMapped(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/s/forms/field/new?type=email&tmpl=field&formId=temporary_form_hash&inBuilder=1',
            [],
            [],
            $this->createAjaxHeaders()
        );
        $clientResponse = $this->client->getResponse();
        $payload        = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('<option value="email"  selected="selected">', $payload['newContent']);
    }

    public function testNewCaptchaFieldFormCanBeSaved(): void
    {
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via captcha test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Email',
                    'type'      => 'email',
                    'alias'     => 'email',
                    'leadField' => 'email',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/forms/field/new?type=captcha&tmpl=field&formId={$formId}&inBuilder=1", [], [], $this->createAjaxHeaders());
        $content     = $this->client->getResponse()->getContent();
        Assert::assertTrue($this->client->getResponse()->isOk(), $content);
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form[name=formfield]');
        $this::assertSame(1, $formCrawler->count(), $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues(
            [
                'formfield[formId]'              => $formId,
                'formfield[type]'                => 'captcha',
                'formfield[label]'               => 'What is the capital of Czech Republic?',
                'formfield[properties][captcha]' => 'Prague',
            ]
        );
        $this->client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $form->getPhpFiles(), $this->createAjaxHeaders());

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertSame(1, $response['success'], $this->client->getResponse()->getContent());
        Assert::assertSame(1, $response['closeModal'], $this->client->getResponse()->getContent());
    }
}
