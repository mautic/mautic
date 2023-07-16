<?php declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer;

use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Mautic\MessengerBundle\Serializer\Handler\HttpRequestHandler;
use Symfony\Component\Messenger\Envelope;
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

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        $data = json_decode($body, true);

        if (!is_array($data) || count($data) !== 1) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        [$messageClassName, $message] = [array_key_first($data), array_values($data)[0]];
        $message = $this->serializer->deserialize($message, $messageClassName, 'json');

        // in case of redelivery, unserialize any stamps
        $stamps = [];
        if (isset($headers['stamps'])) {
            $stamps = unserialize($headers['stamps']);
        }
        return new Envelope($message, $stamps);
    }

    public function encode(Envelope $envelope): array
    {
        // this is called if a message is redelivered for "retry"
        $message = $envelope->getMessage();

        $messageClass = $message::class;

        $data = [$messageClass => $this->serializer->serialize($message, 'json')];

        $allStamps = [];
        foreach ($envelope->all() as $stamps) {
            $allStamps = array_merge($allStamps, $stamps);
        }

        return [
            'body' => json_encode($data),
            'headers' => [
                // store stamps as a header - to be read in decode()
                'stamps' => serialize($allStamps)
            ],
        ];
    }


}
