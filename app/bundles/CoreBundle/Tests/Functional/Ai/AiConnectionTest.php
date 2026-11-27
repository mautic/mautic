<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\AiGatewayException;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AiConnectionTest extends MauticMysqlTestCase
{
    private const string ROUTINE = '/s/ai/connection-test/routine';

    private const string COMPLEX = '/s/ai/connection-test/complex';

    protected function setUp(): void
    {
        $this->configParams += [
            'ai_providers' => [
                ['name' => 'Local fixture', 'endpoint' => 'http://inference.example.test/v1', 'location' => 'local'],
                ['name' => 'Cloud fixture', 'endpoint' => 'https://cloud.example.test/v1', 'location' => 'cloud'],
            ],
            'ai_routine_provider' => '0',
            'ai_routine_model'    => 'local-fixture',
            'ai_complex_provider' => '1',
            'ai_complex_model'    => 'cloud-fixture',
            'ai_allow_cloud'      => false,
        ];
        parent::setUp();
    }

    public function testSavedAssignmentIsTestedAndOnlyMetadataIsAudited(): void
    {
        $success = new MockResponse('{"choices":[{"message":{"role":"assistant","content":"{\"ok\":true}"},"finish_reason":"stop"}],"usage":{"prompt_tokens":17,"completion_tokens":0,"total_tokens":17}}');
        $invalid = new MockResponse('{"choices":[{"message":{"role":"assistant","content":"private fixture output"},"finish_reason":"stop"}]}');
        $requests = 0;
        $http = self::getContainer()->get(MockHttpClient::class);
        $http->setResponseFactory(static function () use (&$requests, $success, $invalid): MockResponse {
            return match (++$requests) {
                1 => $success,
                2 => $invalid,
                default => throw new \LogicException('Unexpected provider request.'),
            };
        });

        $csrf = $this->getCsrfToken('mautic_ai_connection_test');
        $this->client->request(Request::METHOD_POST, self::ROUTINE, ['prompt' => 'untrusted fixture input', 'model' => 'unsaved-model'], server: ['HTTP_X-CSRF-Token' => $csrf]);
        self::assertResponseIsSuccessful();
        $this->assertSame('http://inference.example.test/v1/chat/completions', $success->getRequestUrl());
        $body = json_decode((string) $success->getRequestOptions()['body'], true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame('local-fixture', $body['model']);
        $this->assertSame('{}', $body['messages'][1]['content']);
        $this->assertStringContainsString('This is a connection test.', (string) $body['messages'][0]['content']);
        $this->assertStringNotContainsString('untrusted fixture input', json_encode($body, \JSON_THROW_ON_ERROR));

        $this->client->request(Request::METHOD_POST, self::ROUTINE, server: ['HTTP_X-CSRF-Token' => $csrf]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringNotContainsString('private fixture output', (string) $this->client->getResponse()->getContent());

        $this->client->request(Request::METHOD_POST, self::COMPLEX, server: ['HTTP_X-CSRF-Token' => $csrf]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cloud access is off', (string) $this->client->getResponse()->getContent());
        $this->assertSame(2, $requests);

        $records = $this->auditRecords();
        $this->assertCount(3, $records);
        $actor = self::getContainer()->get(UserHelper::class)->getUser(nullIfGuest: true);
        $this->assertNotNull($actor);
        foreach ($records as $record) {
            $this->assertSame($actor->getId(), $record->getUserId());
        }
        $this->assertSame('success', $records[0]->getAction());
        $this->assertSame([
            'feature' => 'connection_test.routine', 'provider_id' => 0, 'model' => 'local-fixture', 'error_code' => null,
            'input_tokens' => 17, 'output_tokens' => 0, 'total_tokens' => 17,
        ], $records[0]->getDetails());
        $this->assertSame('failed', $records[1]->getAction());
        $this->assertSame([
            'feature' => 'connection_test.routine', 'provider_id' => 0, 'model' => 'local-fixture', 'error_code' => AiGatewayException::INVALID_RESPONSE,
        ], $records[1]->getDetails());
        $this->assertSame('failed', $records[2]->getAction());
        $this->assertSame([
            'feature' => 'connection_test.complex', 'provider_id' => 1, 'model' => 'cloud-fixture', 'error_code' => AiGatewayException::CLOUD_DISABLED,
        ], $records[2]->getDetails());
    }

    public function testNonAdministratorCannotRunConnectionTest(): void
    {
        $sales = self::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'sales']);
        $this->assertNotNull($sales);
        $this->assertFalse($sales->isAdmin());
        $this->loginUser($sales);
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(static fn (): never => throw new \LogicException('Unexpected provider request.'));

        $this->client->request(Request::METHOD_POST, self::ROUTINE, server: ['HTTP_X-CSRF-Token' => $this->getCsrfToken('mautic_ai_connection_test')]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame([], $this->auditRecords());
    }

    public function testRequestRequiresPostAndValidCsrfToken(): void
    {
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(static fn (): never => throw new \LogicException('Unexpected provider request.'));
        $this->client->request(Request::METHOD_GET, self::ROUTINE);
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode(), (string) $this->client->getResponse()->getContent());
        $this->client->request(Request::METHOD_POST, self::ROUTINE, server: ['HTTP_X-CSRF-Token' => 'invalid']);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame([], $this->auditRecords());
    }

    /**
     * @return list<AuditLog>
     */
    private function auditRecords(): array
    {
        return self::getContainer()->get(AuditLogRepository::class)->findBy(['bundle' => 'core', 'object' => 'ai_request'], ['id' => 'ASC']);
    }
}
