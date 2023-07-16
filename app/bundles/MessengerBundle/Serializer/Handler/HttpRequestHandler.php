<?php declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Symfony\Component\HttpFoundation\Request;

class HttpRequestHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Request::class,
                'method' => 'serializeRequestToString',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Request::class,
                'method' => 'deserializeStringToRequest',
            ],
        ];
    }

    public function serializeRequestToString(JsonSerializationVisitor $visitor, Request $request, array $type, Context $context)
    {
        return self::toArray($request);
    }

    public function deserializeStringToRequest(JsonDeserializationVisitor $visitor, $requestAsArray, array $type, Context $context)
    {
        return self::fromArray($requestAsArray);
    }

    public static function toArray(Request $request): array
    {
        return array_filter([
            'attributes' => $request->attributes->all(),
            'request'    => $request->request->all(),
            'query'      => $request->query->all(),
            'cookies'    => $request->cookies->all(),
            'files'      => $request->files->all(),
            'server'     => $request->server->all(),
            'headers'    => $request->headers->all(),
        ]);
    }

    /**
     * @param array<string,mixed> $request
     */
    public static function fromArray(array $request): Request
    {
        return new Request(
            $request['query'] ?? [],
            $request['request'] ?? [],
            $request['attributes'] ?? [],
            $request['cookies'] ?? [],
            $request['files'] ?? [],
            $request['server'] ?? []
        );
    }
}
