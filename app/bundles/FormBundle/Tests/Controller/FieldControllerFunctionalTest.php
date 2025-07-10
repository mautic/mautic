<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FieldControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testNewEmailFieldFormIsPreMapped(): void
    {
        $this->client->xmlHttpRequest(
            Request::METHOD_GET,
            '/s/forms/field/new?type=email&tmpl=field&formId=temporary_form_hash&inBuilder=1'
        );
        $clientResponse = $this->client->getResponse();
        $payload        = json_decode($clientResponse->getContent(), true);
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('<option value="email" selected="selected">', $payload['newContent']);
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
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $crawler     = $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/forms/field/new?type=captcha&tmpl=field&formId={$formId}&inBuilder=1");
        $this->assertResponseIsSuccessful();
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form[name=formfield]');
        Assert::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues(
            [
                'formfield[formId]'              => $formId,
                'formfield[type]'                => 'captcha',
                'formfield[label]'               => 'What is the capital of Czech Republic?',
                'formfield[properties][captcha]' => 'Prague',
            ]
        );
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues(), $form->getPhpFiles());
        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertSame(1, $response['success'] ?? null, $this->client->getResponse()->getContent());
        Assert::assertSame(1, $response['closeModal'] ?? null, $this->client->getResponse()->getContent());
    }

    public function testNewCompanyLookupFieldForm(): void
    {
        $form = new Form();
        $form->setName('Test Form')
            ->setIsPublished(true)
            ->setAlias('testform');
        $this->em->persist($form);
        $this->em->flush();

        $this->client->xmlHttpRequest(
            Request::METHOD_GET,
            '/s/forms/field/new?type=companyLookup&tmpl=field&formId='.$form->getId().'&inBuilder=1'
        );

        Assert::assertTrue($this->client->getResponse()->isOk());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());

        $this->assertSame('Contact', $crawler->filter('select[id="formfield_mappedObject"]')->filter('option[selected]')->text());
        $this->assertSame('Primary company', $crawler->filter('select[id="formfield_mappedField"]')->filter('option[selected]')->text());
    }
}
