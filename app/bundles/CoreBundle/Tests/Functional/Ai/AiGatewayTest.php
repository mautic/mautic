<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\AiFeatureInterface;
use Mautic\CoreBundle\Ai\AiGateway;
use Mautic\CoreBundle\Ai\AiGatewayException;
use Mautic\CoreBundle\Ai\AiModelResolverInterface;
use Mautic\CoreBundle\Ai\ConfiguredModel;
use Mautic\CoreBundle\Ai\ModelProviderInterface;
use Mautic\CoreBundle\Ai\ModelWorkload;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AiGatewayTest extends TestCase
{
    private const string FEATURE = 'content_advice';

    private const string PERMISSION = 'email:emails:editown';

    private const string MODEL = 'local-model';

    public function testOnlyAllowedFieldsReachTheAssignedProvider(): void
    {
        $provider = $this->createMock(ModelProviderInterface::class);
        $provider->expects($this->once())->method('chat')->with(
            self::MODEL,
            'Suggest a shorter draft.'."\n\nReturn only a JSON object matching this schema:\n"
            .'{"type":"object","properties":{"suggestion":{"type":"string"}},"required":["suggestion"],"additionalProperties":false}',
            '{"draft":"Campaign draft","count":0}',
        )->willReturn('{"suggestion":"Campaign summary"}');

        $result = $this->gateway($provider)->request(self::FEATURE, [
            'draft' => 'Campaign draft',
            'count' => 0,
            'email' => 'forbidden@example.test',
            'contact' => ['phone' => 'forbidden detail'],
        ]);

        $this->assertSame('Campaign summary', $result->suggestion);
    }

    #[DataProvider('blockedRequests')]
    public function testBlockedRequestsNeverResolveAModel(string $feature, bool $anonymous, bool $permissionExists, bool $granted, mixed $draft, int $code): void
    {
        $models = $this->createMock(AiModelResolverInterface::class);
        $models->expects($this->never())->method('resolve');
        $permissions = $this->permissions($anonymous, $permissionExists, $granted);
        $gateway = new AiGateway($permissions, $models, [$this->feature()]);

        $this->expectException(AiGatewayException::class);
        $this->expectExceptionCode($code);
        $gateway->request($feature, ['draft' => $draft]);
    }

    /**
     * @return iterable<string, array{string, bool, bool, bool, mixed, int}>
     */
    public static function blockedRequests(): iterable
    {
        yield 'unknown feature' => ['missing', false, true, true, 'draft', AiGatewayException::UNKNOWN_FEATURE];
        yield 'anonymous' => [self::FEATURE, true, true, true, 'draft', AiGatewayException::ACCESS_DENIED];
        yield 'unknown permission' => [self::FEATURE, false, false, true, 'draft', AiGatewayException::ACCESS_DENIED];
        yield 'denied' => [self::FEATURE, false, true, false, 'draft', AiGatewayException::ACCESS_DENIED];
        yield 'nested fields' => [self::FEATURE, false, true, true, ['email' => 'forbidden@example.test'], AiGatewayException::INVALID_CONTEXT];
        yield 'invalid encoding' => [self::FEATURE, false, true, true, "\xff", AiGatewayException::INVALID_CONTEXT];
    }

    #[DataProvider('invalidResponses')]
    public function testInvalidResponsesAreNotReturned(string $response): void
    {
        $provider = $this->createMock(ModelProviderInterface::class);
        $provider->expects($this->once())->method('chat')->willReturn($response);

        $this->expectException(AiGatewayException::class);
        $this->expectExceptionCode(AiGatewayException::INVALID_RESPONSE);
        $this->gateway($provider)->request(self::FEATURE, []);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidResponses(): iterable
    {
        yield 'not JSON' => ['plain text'];
        yield 'array rather than object' => ['[]'];
        yield 'missing field' => ['{}'];
        yield 'incorrect field type' => ['{"suggestion":false}'];
        yield 'unexpected field' => ['{"suggestion":"Campaign summary","unexpected":true}'];
    }

    public function testProviderErrorsAreNotExposed(): void
    {
        $provider = $this->createMock(ModelProviderInterface::class);
        $provider->expects($this->once())->method('chat')->willThrowException(new \RuntimeException('Private provider details'));

        try {
            $this->gateway($provider)->request(self::FEATURE, []);
            $this->fail('Expected a provider failure.');
        } catch (AiGatewayException $exception) {
            $this->assertSame(AiGatewayException::PROVIDER_FAILED, $exception->getCode());
            $this->assertStringNotContainsString('Private provider details', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function testDuplicateFeatureIdsAreRejected(): void
    {
        $this->expectException(\LogicException::class);
        new AiGateway($this->permissions(), $this->createStub(AiModelResolverInterface::class), [$this->feature(), $this->feature()]);
    }

    private function gateway(ModelProviderInterface $provider): AiGateway
    {
        $models = $this->createMock(AiModelResolverInterface::class);
        $models->expects($this->once())->method('resolve')->with(ModelWorkload::Complex)->willReturn(new ConfiguredModel($provider, self::MODEL, []));

        return new AiGateway($this->permissions(), $models, [$this->feature()]);
    }

    private function permissions(bool $anonymous = false, bool $exists = true, bool $granted = true): CorePermissions
    {
        $permissions = $this->createStub(CorePermissions::class);
        $permissions->method('isAnonymous')->willReturn($anonymous);
        $permissions->method('checkPermissionExists')->willReturnCallback(static fn (string $permission): bool => self::PERMISSION === $permission && $exists);
        $permissions->method('isGranted')->willReturnCallback(static fn (string $permission): bool => self::PERMISSION === $permission && $granted);

        return $permissions;
    }

    private function feature(): AiFeatureInterface
    {
        $feature = $this->createStub(AiFeatureInterface::class);
        $feature->method('id')->willReturn(self::FEATURE);
        $feature->method('permission')->willReturn(self::PERMISSION);
        $feature->method('workload')->willReturn(ModelWorkload::Complex);
        $feature->method('allowedContextFields')->willReturn(['draft', 'count']);
        $feature->method('instructions')->willReturn('Suggest a shorter draft.');
        $feature->method('responseSchema')->willReturn([
            'type' => 'object',
            'properties' => ['suggestion' => ['type' => 'string']],
            'required' => ['suggestion'],
            'additionalProperties' => false,
        ]);

        return $feature;
    }
}
