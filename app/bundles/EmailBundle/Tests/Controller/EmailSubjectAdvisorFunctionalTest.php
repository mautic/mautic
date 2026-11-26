<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Ai\EmailSubjectAdvisor;
use Mautic\EmailBundle\Entity\Email;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class EmailSubjectAdvisorFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams += [
            'ai_providers' => [['name' => 'Local fixture', 'endpoint' => 'http://inference.example.test/v1', 'location' => 'local']],
            'ai_routine_provider' => '0',
            'ai_routine_model' => 'editorial-fixture',
        ];
        parent::setUp();
    }

    public function testOnlyDraftSubjectReachesTheProviderAndTheEmailIsNotSaved(): void
    {
        $email = $this->createEmail();
        $id = $email->getId();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/edit/'.$id);
        self::assertResponseIsSuccessful();
        $control = $crawler->filter('[data-subject-advisor]');
        $this->assertCount(1, $control);

        $reply = new MockResponse('{"choices":[{"message":{"role":"assistant","content":"{\"suggestion\":\"Conheça a coleção de verão\"}"},"finish_reason":"stop"}]}');
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory([$reply]);
        $this->client->request(Request::METHOD_POST, (string) $control->attr('data-url'), [
            'subject' => 'Veja o lançamento de verão',
            'html' => 'Private email body',
            'contact' => 'fixture@example.test',
            'model' => 'not-the-configured-model',
        ], server: ['HTTP_X-CSRF-Token' => $control->attr('data-csrf'), 'HTTP_X-Requested-With' => 'XMLHttpRequest']);
        self::assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"suggestion":"Conheça a coleção de verão"}', (string) $this->client->getResponse()->getContent());
        $payload = json_decode((string) $reply->getRequestOptions()['body'], true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame('editorial-fixture', $payload['model']);
        $this->assertSame(['subject' => 'Veja o lançamento de verão'], json_decode((string) $payload['messages'][1]['content'], true, flags: \JSON_THROW_ON_ERROR));

        $this->em->clear();
        $this->assertSame('Saved subject', $this->em->find(Email::class, $id)?->getSubject());
        $audit = self::getContainer()->get(AuditLogRepository::class)->findOneBy(['bundle' => 'core', 'object' => 'ai_request']);
        $this->assertNotNull($audit);
        $this->assertSame('success', $audit->getAction());
        $this->assertSame(['feature' => EmailSubjectAdvisor::ID, 'provider_id' => 0, 'model' => 'editorial-fixture', 'error_code' => null], $audit->getDetails());
    }

    public function testInvalidInputAndMissingCsrfNeverReachAProvider(): void
    {
        $email = $this->createEmail();
        $id = $email->getId();
        $url = '/s/emails/'.$id.'/subject-suggestion';
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(static fn (): never => throw new \LogicException('Unexpected provider request.'));

        $this->client->request(Request::METHOD_POST, $url, ['subject' => 'Editorial subject']);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $csrf = $this->getCsrfToken('mautic_ajax_post');
        $this->client->request(Request::METHOD_POST, $url, ['subject' => 'Hello {contactfield=firstname}'], server: ['HTTP_X-CSRF-Token' => $csrf]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAnEditorCannotRequestSuggestionsForAnotherOwnersEmail(): void
    {
        $email = $this->createEmail();
        $id = $email->getId();
        $url = '/s/emails/'.$id.'/subject-suggestion';
        $role = new Role();
        $role->setName('Own email editor');
        $this->getService(RoleModel::class)->setRolePermissions($role, ['email:emails' => ['viewown', 'editown']]);
        $this->em->persist($role);
        $editor = new User();
        $editor->setFirstName('Subject');
        $editor->setLastName('Editor');
        $editor->setUsername('subject-editor');
        $editor->setEmail('subject-editor@example.test');
        $editor->setRole($role);
        $editor->setPassword(password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
        $this->em->persist($editor);
        $this->em->flush();
        $this->loginUser($editor);
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(static fn (): never => throw new \LogicException('Unexpected provider request.'));
        $this->client->request(Request::METHOD_POST, $url, ['subject' => 'Editorial subject'], server: ['HTTP_X-CSRF-Token' => $this->getCsrfToken('mautic_ajax_post')]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testInvalidProviderReplyIsNotReturned(): void
    {
        $id = $this->createEmail()->getId();
        $reply = new MockResponse(json_encode([
            'choices' => [[
                'message'       => ['role' => 'assistant', 'content' => json_encode(['suggestion' => "Invalid subject\n"], \JSON_THROW_ON_ERROR)],
                'finish_reason' => 'stop',
            ]],
        ], \JSON_THROW_ON_ERROR));
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory([$reply]);
        $this->client->request(Request::METHOD_POST, '/s/emails/'.$id.'/subject-suggestion', ['subject' => 'Editorial subject'], server: ['HTTP_X-CSRF-Token' => $this->getCsrfToken('mautic_ajax_post')]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringNotContainsString('Invalid subject', (string) $this->client->getResponse()->getContent());
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Subject advisor fixture');
        $email->setSubject('Saved subject');
        $email->setEmailType('template');
        $email->setCreatedBy(1);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
