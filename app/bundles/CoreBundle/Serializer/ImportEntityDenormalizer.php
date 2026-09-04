<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * The export subscribers write their payloads with snake_case keys while the entities expose
 * camelCase properties. The default denormalizer silently discards any key it cannot map, so
 * every multi-word field was dropped on import: email bodies, form styling, page HTML. This
 * wrapper restores the mapping before delegating to the real denormalizer.
 *
 * Mapped keys are added alongside the originals rather than replacing them, so payloads that
 * already use the canonical form keep working unchanged.
 *
 * Deliberately not a DenormalizerInterface implementation: that would register it inside the
 * serializer's own normalizer chain and create a circular reference back onto itself.
 */
final readonly class ImportEntityDenormalizer
{
    /**
     * Export keys whose camelCase form still does not match the entity property.
     *
     * @var array<string, string>
     */
    private const array ALIASES = [
        'lang'           => 'language',
        'form_attr'      => 'formAttributes',
        'container_attr' => 'containerAttributes',
        'input_attr'     => 'inputAttributes',
        'label_attr'     => 'labelAttributes',
        'field_order'    => 'order',
        'field_group'    => 'group',
    ];

    /**
     * Exported keys that describe the state of the source install rather than the entity itself.
     * They were dropped before this class existed, and they should stay dropped: a copy starts
     * with its own traffic counters, and the schema flag belongs to the database it came from.
     *
     * @var array<int, string>
     */
    private const array NOT_IMPORTABLE = [
        'unique_hits',
        'variant_hits',
        'column_is_not_created',
    ];

    public function __construct(
        private DenormalizerInterface $decorated,
    ) {
    }

    /**
     * @param array<mixed>         $data
     * @param class-string         $type
     * @param array<string, mixed> $context
     */
    public function denormalize(array $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->decorated->denormalize($this->mapKeys($data), $type, $format, $context);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function mapKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!is_string($key) || in_array($key, self::NOT_IMPORTABLE, true)) {
                continue;
            }

            $mapped = self::ALIASES[$key] ?? $this->toCamelCase($key);

            if ($mapped === $key || array_key_exists($mapped, $data)) {
                continue;
            }

            $data[$mapped] = $value;
        }

        return $data;
    }

    private function toCamelCase(string $key): string
    {
        if (!str_contains($key, '_')) {
            return $key;
        }

        return lcfirst(str_replace('_', '', ucwords($key, '_')));
    }
}
