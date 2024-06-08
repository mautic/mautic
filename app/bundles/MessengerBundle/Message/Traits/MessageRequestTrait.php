<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Traits;

use Symfony\Component\HttpFoundation\Request;

trait MessageRequestTrait
{
    private ?\DateTimeInterface $eventTime = null;

    private Request $request;

    public function getEventTime(): ?\DateTimeInterface
    {
        return $this->eventTime;
    }

    public function setEventTime(\DateTimeInterface $eventTime = null): self
    {
        $this->eventTime = $eventTime;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function __serialize(): array
    {
        $data            = get_object_vars($this);
        $data['request'] = array_filter([
            'attributes' => $this->request->attributes->all(),
            'request'    => $this->request->request->all(),
            'query'      => $this->request->query->all(),
            'cookies'    => $this->request->cookies->all(),
            'files'      => $this->request->files->all(),
            'server'     => $this->request->server->all(),
        ]);

        return $data;
    }

    /**
     * @param mixed[] $data
     */
    public function __unserialize(array $data): void
    {
        $requestData     = $data['request'];
        $data['request'] = new Request(
            $requestData['query'] ?? [],
            $requestData['request'] ?? [],
            $requestData['attributes'] ?? [],
            $requestData['cookies'] ?? [],
            $requestData['files'] ?? [],
            $requestData['server'] ?? []
        );

        foreach ($data as $key => $item) {
            $this->$key = $item;
        }
    }
}
