<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Yaml\Yaml;

final readonly class OpenApiYamlEncoder implements EncoderInterface, DecoderInterface
{
    public function __construct(
        private EncoderInterface&DecoderInterface $decorated,
    ) {
    }

    public function encode(mixed $data, string $format, array $context = []): string
    {
        $context[YamlEncoder::YAML_INLINE] ??= 10;
        $context[YamlEncoder::YAML_FLAGS] = ($context[YamlEncoder::YAML_FLAGS] ?? 0)
            | Yaml::DUMP_OBJECT_AS_MAP
            | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
            | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
            | Yaml::DUMP_NUMERIC_KEY_AS_STRING;

        return $this->decorated->encode($data, $format, $context);
    }

    public function supportsEncoding(string $format): bool
    {
        return $this->decorated->supportsEncoding($format);
    }

    public function decode(string $data, string $format, array $context = []): mixed
    {
        return $this->decorated->decode($data, $format, $context);
    }

    public function supportsDecoding(string $format): bool
    {
        return $this->decorated->supportsDecoding($format);
    }
}
