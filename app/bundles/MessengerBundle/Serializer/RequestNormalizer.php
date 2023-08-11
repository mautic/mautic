<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RequestNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        \assert($object instanceof Request);

        return array_filter([
            'attributes' => $object->attributes->all(),
            'request'    => $object->request->all(),
            'query'      => $object->query->all(),
            'cookies'    => $object->cookies->all(),
            'files'      => $object->files->all(),
            'server'     => $object->server->all(),
            'headers'    => $object->headers->all(),
        ]);
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof Request;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Request
    {
        return new Request(
            $data['query'] ?? [],
            $data['request'] ?? [],
            $data['attributes'] ?? [],
            $data['cookies'] ?? [],
            $data['files'] ?? [],
            $data['server'] ?? []
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return Request::class === $type;
    }
}
