<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Bridge\Generic\EmbeddingsModel;
use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\Exception\ExceptionInterface as PlatformException;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[Exclude]
final readonly class OpenAiCompatibleProvider implements ModelProviderInterface
{
    private PlatformInterface $platform;

    public function __construct(
        private ProviderLocation $location,
        string $baseUrl,
        HttpClientInterface $httpClient,
        #[\SensitiveParameter]
        ?string $apiKey = null,
    ) {
        self::validateEndpoint($baseUrl, $location, null !== $apiKey);

        $this->platform = Factory::createPlatform(
            $baseUrl,
            $apiKey,
            $httpClient->withOptions(['max_redirects' => 0, 'max_duration' => 120]),
            completionsPath: '/chat/completions',
            embeddingsPath: '/embeddings',
        );
    }

    public function location(): ProviderLocation
    {
        return $this->location;
    }

    /**
     * @param non-empty-string $model
     */
    public function chat(string $model, #[\SensitiveParameter] string $instructions, #[\SensitiveParameter] string $prompt): AiTextResult
    {
        try {
            return new SymfonyTextGenerator($this->platform)->generateResult(new CompletionsModel($model), $instructions, $prompt);
        } catch (PlatformException|HttpException) {
            throw new \RuntimeException('The AI provider could not complete the chat request.');
        }
    }

    /**
     * @param non-empty-string     $model
     * @param array<string, mixed> $schema
     */
    public function structured(string $model, #[\SensitiveParameter] string $instructions, #[\SensitiveParameter] string $prompt, array $schema): \stdClass
    {
        try {
            $text = new SymfonyTextGenerator($this->platform)->generate(new CompletionsModel($model), $instructions, $prompt, [
                'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'mautic_response', 'schema' => $schema]],
            ]);
            $value = json_decode($text, flags: \JSON_THROW_ON_ERROR);
        } catch (PlatformException|HttpException|\JsonException) {
            throw new \RuntimeException('The AI provider did not return a structured response.');
        }

        if (!$value instanceof \stdClass) {
            throw new \RuntimeException('The AI provider did not return a JSON object.');
        }

        return $value;
    }

    /**
     * @param non-empty-string $model
     *
     * @return list<float>
     */
    public function embed(string $model, #[\SensitiveParameter] string $text): array
    {
        try {
            $vectors = $this->platform->invoke(new EmbeddingsModel($model), $text)->asVectors();
        } catch (PlatformException|HttpException) {
            throw new \RuntimeException('The AI provider could not complete the embeddings request.');
        }

        if (1 !== count($vectors)) {
            throw new \RuntimeException('The AI provider did not return exactly one embedding.');
        }

        return array_values($vectors[0]->getData());
    }

    public static function validateEndpoint(string $baseUrl, ProviderLocation $location, bool $usesCredentials): void
    {
        $parts = parse_url($baseUrl);
        if (false === filter_var($baseUrl, \FILTER_VALIDATE_URL) || false === $parts || !isset($parts['host'])
            || [] !== array_intersect_key($parts, array_flip(['user', 'pass', 'query', 'fragment']))) {
            throw new \InvalidArgumentException('An AI provider needs an HTTP(S) base URL without credentials, query or fragment.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ('https' !== $scheme && ('http' !== $scheme || ProviderLocation::Local !== $location || $usesCredentials)) {
            throw new \InvalidArgumentException('AI providers require HTTPS except for local endpoints without credentials.');
        }
    }
}
