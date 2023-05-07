<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Serializer;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Mautic\MessengerBundle\Serializer\Handler\SymfonyRequestHandler;

class MessageSerializer
{
    private array $handlers = [];
    private SerializerBuilder $builder;
    private ?Serializer $serializer = null;

    public function __construct()
    {
        $this->builder = SerializerBuilder::create();
        //  Register custom handlers
        $this->addHandler(new SymfonyRequestHandler());
    }

    public function getSerializer(): Serializer
    {
        if (null !== $this->serializer) {
            return $this->serializer;
        }

        $this->configureHandlers();

        return $this->serializer = $this->builder->build();
    }

    public function addHandler(SubscribingHandlerInterface $handler): self
    {
        $this->handlers[get_class($handler)] = $handler;

        return $this;
    }

    private function configureHandlers(): self
    {
        $this->builder
            ->addDefaultHandlers()
            ->configureHandlers($this->registerHandler)
        ;
        $this->serializer = null;

        return $this;
    }
}
