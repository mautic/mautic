<?php

declare(strict_types=1);

namespace Mautic\Tests\Functional\Form;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockDomainSubmissionsFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

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
        $formCrawler = $crawler->filter('form[id^="mauticform_"]');
        $form        = $formCrawler->form();

        $form->setValues([
            'mauticform[email]' => 'blocked@example.com',
        ]);

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];

        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request(
            Request::METHOD_POST,
            "/s/forms/results/{$formId}/markSpam/{$submission->getId()}"
        );

        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $batchIds = urlencode(json_encode([$submission->getId()], JSON_THROW_ON_ERROR));
        $this->client->request(
            Request::METHOD_POST,
            "/s/forms/results/{$formId}/batchMarkSpam?ids={$batchIds}"
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $editor = $this->createUserWithPermission([
            'email'      => 'form-editor@mautic-test.com',
            'user-name'  => 'form-editor',
            'first-name' => 'Form',
            'last-name'  => 'Editor',
            'role'       => [
                'name'        => 'form-editor-role',
                'permissions' => ['form:forms' => 1023],
            ],
        ]);
        $this->em->flush();
        $this->loginOtherUser($editor);

        foreach ([
            "/s/forms/results/{$formId}/markSpam/{$submission->getId()}",
            "/s/forms/results/{$formId}/batchMarkSpam?ids={$batchIds}",
        ] as $url) {
            $this->client->request(Request::METHOD_POST, $url);
            $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        }

        $this->setUpSymfony($this->configParams);

        $this->client->request(
            Request::METHOD_POST,
            "/form/submit?formId={$formId}&ajax=1",
            [
                'mauticform' => [
                    'email'    => 'another@example.com',
                    'formId'   => $formId,
                    'formName' => 'blockdomainspamtestform',
                ],
            ]
        );
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame(0, $response['success']);
        $this->assertSame('Cannot be sent with this email', $response['validationErrors']['email']);
    }
}
