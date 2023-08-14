<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Mautic\MessengerBundle\Serializer\Handler\HttpRequestHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class MauticMessengerSerializer implements SerializerInterface
{
    private \JMS\Serializer\SerializerInterface $serializer;

    public function __construct()
    {
        $this->serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->configureHandlers(function (HandlerRegistry $registry) {
                $registry->registerSubscribingHandler(new HttpRequestHandler());
            })
            ->build();
    }

    /**
     * @param array<string,array<StampInterface>|array<string>|string> $encodedEnvelope
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $body    = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        [$className, $classData] = explode(':', $body, 2);

        try {
            $message = $this->serializer->deserialize($classData, $className, 'json');
        } catch (\Exception $exception) {
            throw new MessageDecodingFailedException(sprintf('Could not decode message: %s', $exception->getMessage()), 0, $exception);
        }

        $stamps = [];
        if (isset($headers['stamps'])) {
            $stamps = unserialize($headers['stamps']);
        }

        return new Envelope($message, $stamps);
    }

    /**
     * @return array<string,array<StampInterface>|array<string>|string>
     */
    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        $messageClass = $message::class;

        $data = $this->serializer->serialize($message, 'json');

        $allStamps = [];
        foreach ($envelope->all() as $stamps) {
            $allStamps = array_merge($allStamps, $stamps);
        }

        return [
            'body'    => sprintf('%s:%s', $messageClass, $data),
            'headers' => [
                // store stamps as a header - to be read in decode()
                'stamps' => serialize($allStamps),
            ],
        ];
    }
}
