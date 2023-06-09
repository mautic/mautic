<?php

namespace Mautic\MessengerBundle\Factory;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class MessengerRequestFactory
{
    public static function toArray(Request $request): array
    {
        return [
            'attributes' => $request->attributes->all(),
            'request'    => $request->request->all(),
            'query'      => $request->query->all(),
            'cookies'    => $request->cookies->all(),
            'files'      => $request->files->all(),
            'server'     => $request->server->all(),
            'headers'    => $request->headers->all(),
        ];
    }

    public static function fromArray(array $request): Request
    {
        if (!isset($request['attributes'])) {
            throw new InvalidArgumentException('Request does not have expected keys. Use QueueRequestFactory::toArray to prepare a Request object');
        }

        return new Request(
            $request['query'],
            $request['request'],
            $request['attributes'],
            $request['cookies'],
            $request['files'],
            $request['server']
        );
    }
}
