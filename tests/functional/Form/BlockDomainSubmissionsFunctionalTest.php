<?php

declare(strict_types=1);

namespace Mautic\Tests\Functional\Form;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockDomainSubmissionsFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback   = false;
    protected bool $authenticateApi = true;

    public function testMarkSpamBlocksDomainForFutureSubmissions(): void
    {
        $payload = [
            'name'        => 'Block domain spam test form',
            'description' => 'Form created via block domain submission test',
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

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_blockdomainspamtetstform]');
        $form        = $formCrawler->form();

        $form->setValues([
            'mauticform[email]' => 'blocked@example.com',
        ]);

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request(
            Request::METHOD_POST,
            "/s/forms/results/{$formId}/action",
            [
                'action'  => 'markSpam',
                'formId'  => $formId,
                'objectId'=> 1,
            ]
        );

        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_FOUND, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_blockdomainspamtetstform]');
        $form        = $formCrawler->form();

        $form->setValues([
            'mauticform[email]' => 'another@blocked.example.com',
        ]);

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertStringContainsString('mautic.form.submission.errors', $clientResponse->getContent());
    }
}
