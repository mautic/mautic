<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

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
        $feature = $this->features[$featureId] ?? throw new AiGatewayException('The AI feature is not registered.', AiGatewayException::UNKNOWN_FEATURE);
        $permission = $feature->permission();
        if ($this->permissions->isAnonymous() || !$this->permissions->checkPermissionExists($permission) || !$this->permissions->isGranted($permission)) {
            throw new AiGatewayException('The current user cannot use this AI feature.', AiGatewayException::ACCESS_DENIED);
        }

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
        $model = $this->models->resolve($feature->workload());
        try {
            $text = $model->provider->chat($model->name, $instructions, $prompt);
        } catch (\RuntimeException|\TypeError) {
            throw new AiGatewayException('The assigned AI provider could not return a response.', AiGatewayException::PROVIDER_FAILED);
        }

        return $this->validateResponse($text, $schema);
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
