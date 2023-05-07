<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer\Handler;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;

class SymfonyRequestHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'DateTime',
                'method'    => 'serializeDateTimeToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format'    => 'json',
                'type'      => 'DateTime',
                'method'    => 'deserializeDateTimeToJson',
            ],
        ];
    }
}
