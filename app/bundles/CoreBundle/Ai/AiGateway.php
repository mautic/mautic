<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Opis\JsonSchema\Validator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final readonly class AiGateway
{
    /**
     * @var array<string, AiFeatureInterface>
     */
    private array $features;

    /**
     * @param iterable<AiFeatureInterface> $features
     */
    public function __construct(
        private CorePermissions $permissions,
        private AiModelResolverInterface $models,
        private AuditLogModel $auditLog,
        #[AutowireIterator(tag: AiFeatureInterface::TAG)]
        iterable $features,
    ) {
        $registered = [];
        foreach ($features as $feature) {
            $id = $feature->id();
            if ('' === trim($id) || isset($registered[$id])) {
                throw new \LogicException('AI features must have unique, non-empty IDs.');
            }
            $registered[$id] = $feature;
        }
        $this->features = $registered;
    }

    /**
     * The caller must check access to the source records before supplying context.
     *
     * @param array<string, mixed> $context
     */
    public function request(string $featureId, #[\SensitiveParameter] array $context): \stdClass
    {
        $feature = $this->features[$featureId] ?? null;
        $model   = null;
        $reply   = null;
        $failure = null;
        $outcome = 'failed';

        try {
            if (null === $feature) {
                throw new AiGatewayException('The AI feature is not registered.', AiGatewayException::UNKNOWN_FEATURE);
            }

            $this->authorize($feature);
            [$instructions, $prompt, $schema] = $this->prepareInput($feature, $context);
            $model = $this->models->resolve($feature->workload());
            try {
                $reply = $model->provider->chat($model->name, $instructions, $prompt);
            } catch (\RuntimeException|\TypeError) {
                throw new AiGatewayException('The assigned AI provider could not return a response.', AiGatewayException::PROVIDER_FAILED);
            }

            $result  = $this->validateResponse($reply->text, $schema);
            $outcome = 'success';

            return $result;
        } catch (AiGatewayException $exception) {
            $failure = $exception;
            throw $exception;
        } finally {
            $providerId = $model->providerId ?? $failure?->providerId;
            $usage      = array_filter([
                'input_tokens'  => $reply?->inputTokens,
                'output_tokens' => $reply?->outputTokens,
                'total_tokens'  => $reply?->totalTokens,
            ], static fn (?int $count): bool => null !== $count && $count >= 0);

            $this->auditLog->writeToLog([
                'bundle'   => 'core',
                'object'   => 'ai_request',
                'objectId' => $providerId ?? 0,
                'action'   => $outcome,
                'details'  => [
                    'feature'     => null !== $feature ? $featureId : null,
                    'provider_id' => $providerId,
                    'model'       => $model->name ?? $failure?->model,
                    'error_code'  => $failure?->getCode(),
                ] + $usage,
            ]);
        }
    }

    private function authorize(AiFeatureInterface $feature): void
    {
        $permission = $feature->permission();
        if ($this->permissions->isAnonymous() || (null === $permission
            ? !$this->permissions->isAdmin()
            : (!$this->permissions->checkPermissionExists($permission) || !$this->permissions->isGranted($permission)))) {
            throw new AiGatewayException('The current user cannot use this AI feature.', AiGatewayException::ACCESS_DENIED);
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{string, string, string}
     */
    private function prepareInput(AiFeatureInterface $feature, #[\SensitiveParameter] array $context): array
    {
        $projected = array_intersect_key($context, array_fill_keys($feature->allowedContextFields(), true));
        foreach ($projected as $value) {
            if (null !== $value && !is_scalar($value)) {
                throw new AiGatewayException('AI context must contain only scalar values.', AiGatewayException::INVALID_CONTEXT);
            }
        }
        try {
            $prompt = json_encode((object) $projected, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new AiGatewayException('The AI context could not be encoded.', AiGatewayException::INVALID_CONTEXT);
        }

        $schema = json_encode((object) $feature->responseSchema(), \JSON_THROW_ON_ERROR);
        $instructions = $feature->instructions()."\n\nReturn only a JSON object matching this schema:\n".$schema;
        return [$instructions, $prompt, $schema];
    }

    private function validateResponse(#[\SensitiveParameter] string $text, string $schema): \stdClass
    {
        $message = 'The AI response does not match the feature schema.';
        try {
            $result = json_decode($text, false, flags: \JSON_THROW_ON_ERROR);
            if ($result instanceof \stdClass && new Validator()->validate($result, $schema)->isValid()) {
                return $result;
            }
        } catch (\JsonException|\InvalidArgumentException|\RuntimeException) {
        }

        throw new AiGatewayException($message, AiGatewayException::INVALID_RESPONSE);
    }
}
