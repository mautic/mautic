<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\AiGatewayException;
use Mautic\CoreBundle\Ai\AiModelResolver;
use Mautic\CoreBundle\Ai\ModelWorkload;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AiModelResolverTest extends TestCase
{
    private const string ROUTINE_MODEL = 'ollama-fixture';

    private const string COMPLEX_MODEL = 'hosted-fixture';

    private const string LOCAL_URL = 'http://ollama:11434/v1';

    private const string CLOUD_URL = 'https://models.example.test/v1';

    private const string KEY = 'fixture-key';

    #[DataProvider('assignments')]
    public function testCallsOnlyTheConfiguredProviderAndModel(ModelWorkload $workload, bool $allowCloud, string $endpoint, string $modelId): void
    {
        $response = new MockResponse('{"choices":[{"message":{"role":"assistant","content":"Suggestion"},"finish_reason":"stop"}]}');
        $client = new MockHttpClient($response);
        $resolver = $this->resolver($client, $allowCloud);
        $model = $resolver->resolve($workload);

        $this->assertSame('Suggestion', $model->provider->chat($model->name, 'Answer briefly.', 'Synthetic prompt'));
        $this->assertSame($endpoint.'/chat/completions', $response->getRequestUrl());
        $options = $response->getRequestOptions();
        $this->assertSame($modelId, json_decode((string) $options['body'], true, flags: \JSON_THROW_ON_ERROR)['model']);
        $this->assertSame(0, $options['max_redirects']);

        $auth = $options['normalized_headers']['authorization'] ?? [];
        $this->assertSame(ModelWorkload::Complex === $workload ? ['Authorization: Bearer '.self::KEY] : [], $auth);
    }

    /**
     * @return iterable<string, array{ModelWorkload, bool, string, string}>
     */
    public static function assignments(): iterable
    {
        yield 'routine remains local with cloud off' => [ModelWorkload::Routine, false, self::LOCAL_URL, self::ROUTINE_MODEL];
        yield 'complex uses selected cloud provider with cloud on' => [ModelWorkload::Complex, true, self::CLOUD_URL, self::COMPLEX_MODEL];
    }

    #[DataProvider('blockedAssignments')]
    public function testBlockedAssignmentsDoNotContactAProvider(mixed $allowCloud, ?string $provider, ?string $credential, int $code): void
    {
        $client = new MockHttpClient();
        $resolver = $this->resolver($client, $allowCloud, $provider, $credential);

        try {
            $resolver->resolve(ModelWorkload::Complex);
            $this->fail('Expected the assignment to be blocked.');
        } catch (AiGatewayException $exception) {
            $this->assertSame($code, $exception->getCode());
            $this->assertSame(0, $client->getRequestsCount());
            $this->assertNull($exception->getPrevious());
        }
    }

    /**
     * @return iterable<string, array{mixed, ?string, ?string, int}>
     */
    public static function blockedAssignments(): iterable
    {
        yield 'cloud off with local alternative' => [false, '1', null, AiGatewayException::CLOUD_DISABLED];
        yield 'string is not cloud consent' => ['false', '1', null, AiGatewayException::CLOUD_DISABLED];
        yield 'assignment missing with local alternative' => [true, null, null, AiGatewayException::NOT_CONFIGURED];
        yield 'provider removed' => [true, '5', null, AiGatewayException::NOT_CONFIGURED];
        yield 'credential unreadable' => [true, '1', 'invalid', AiGatewayException::INVALID_CREDENTIAL];
    }

    private function resolver(MockHttpClient $client, mixed $allowCloud, ?string $provider = '1', ?string $credential = null): AiModelResolver
    {
        $parameters = $this->createStub(CoreParametersHelper::class);
        $parameters->method('get')->willReturnMap([['mautic.secret_key', null, EncryptionHelper::generateKey()]]);
        $encryption = new EncryptionHelper($parameters, new OpenSSLCipher());

        $settings = $this->createStub(CoreParametersHelper::class);
        $settings->method('get')->willReturnMap([
            ['ai_providers', null, [
                ['location' => 'local', 'endpoint' => self::LOCAL_URL],
                ['location' => 'cloud', 'endpoint' => self::CLOUD_URL, 'api_key' => $credential ?? $encryption->encrypt(self::KEY)],
            ]],
            ['ai_allow_cloud', null, $allowCloud],
            ['ai_routine_provider', null, '0'],
            ['ai_routine_model', null, self::ROUTINE_MODEL],
            ['ai_complex_provider', null, $provider],
            ['ai_complex_model', null, self::COMPLEX_MODEL],
        ]);

        return new AiModelResolver($settings, $encryption, $client);
    }
}
