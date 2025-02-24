<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class NullTransportFactory implements TransportFactoryInterface
{
    /**
     * @param mixed[] $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return new NullTransport();
    }

    /**
     * @param mixed[] $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'null://');
    }
}
