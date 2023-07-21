<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\HttpFoundation\Request;

class HttpRequestHandler implements SubscribingHandlerInterface
{
    /** @return array<int, array<int|string>> */
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Request::class,
                'method' => 'serializeRequestToArray',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Request::class,
                'method' => 'deserializeArrayToRequest',
            ],
        ];
    }

    /**
     * @param array<string> $type
     * @return array<string,array<string>|mixed>
     */
    public function serializeRequestToArray(JsonSerializationVisitor $visitor, Request $request, array $type, Context $context): array
    {
        return array_filter([
            'attributes' => $request->attributes->all(),
            'request' => $request->request->all(),
            'query' => $request->query->all(),
            'cookies' => $request->cookies->all(),
            'files' => $request->files->all(),
            'server' => $request->server->all(),
            'headers' => $request->headers->all(),
        ]);
    }

    /**
     * @param array<string,mixed> $requestAsArray
     * @param array<string> $type
     */
    public function deserializeArrayToRequest(JsonDeserializationVisitor $visitor, array $requestAsArray, array $type, Context $context): Request
    {
        return new Request(
            $requestAsArray['query'] ?? [],
            $requestAsArray['request'] ?? [],
            $requestAsArray['attributes'] ?? [],
            $requestAsArray['cookies'] ?? [],
            $requestAsArray['files'] ?? [],
            $requestAsArray['server'] ?? []
        );
    }
}
