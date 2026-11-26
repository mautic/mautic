<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
final readonly class AiModelResolver implements AiModelResolverInterface
{
    public function __construct(
        private CoreParametersHelper $parameters,
        private EncryptionHelper $encryption,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function resolve(ModelWorkload $workload): ConfiguredModel
    {
        $prefix    = 'ai_'.$workload->value;
        $id        = $this->parameters->get($prefix.'_provider');
        $model     = $this->parameters->get($prefix.'_model');
        $providers = $this->parameters->get('ai_providers');

        if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id) || !is_string($model) || '' === $model || !is_array($providers)) {
            throw new AiGatewayException('No AI model is assigned to this workload.', AiGatewayException::NOT_CONFIGURED);
        }

        $row = $providers[$id] ?? null;
        if (!is_array($row) || !is_string($row['location'] ?? null) || null === $location = ProviderLocation::tryFrom($row['location'])) {
            throw new AiGatewayException('No valid AI provider is assigned to this workload.', AiGatewayException::NOT_CONFIGURED);
        }
        $providerId = (int) $id;
        if (ProviderLocation::Cloud === $location && true !== $this->parameters->get('ai_allow_cloud')) {
            throw new AiGatewayException('AI requests to cloud providers are disabled.', AiGatewayException::CLOUD_DISABLED, $providerId, $model);
        }
        if (!is_string($row['endpoint'] ?? null)) {
            throw new AiGatewayException('The assigned AI provider has an invalid base URL.', AiGatewayException::NOT_CONFIGURED, $providerId, $model);
        }

        $key = $this->decryptKey($row['api_key'] ?? null, $providerId, $model);
        try {
            $provider = new OpenAiCompatibleProvider($location, $row['endpoint'], $this->httpClient, $key);
        } catch (\InvalidArgumentException) {
            throw new AiGatewayException('The assigned AI provider has an invalid base URL.', AiGatewayException::NOT_CONFIGURED, $providerId, $model);
        }

        return new ConfiguredModel($provider, $model, [], providerId: $providerId);
    }

    private function decryptKey(#[\SensitiveParameter] mixed $sealed, int $providerId, string $model): ?string
    {
        if (null === $sealed || '' === $sealed) {
            return null;
        }

        if (is_string($sealed) && 1 === preg_match('/\A[A-Za-z0-9+\/=]+\|[A-Za-z0-9+\/=]+\z/D', $sealed)) {
            try {
                $key = $this->encryption->decrypt($sealed);
                if (is_string($key) && 1 === preg_match('/\A[\x21-\x7e]+\z/D', $key)) {
                    return $key;
                }
            } catch (\InvalidArgumentException|\ErrorException) {
            }
        }

        throw new AiGatewayException('The assigned AI provider credential could not be read.', AiGatewayException::INVALID_CREDENTIAL, $providerId, $model);
    }
}
