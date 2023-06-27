<?php

namespace Mautic\MessengerBundle\Factory;

use Symfony\Component\HttpFoundation\Request;

class MessengerRequestFactory
{
    /**
     * @return array<string,mixed>
     */
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
