<?php

namespace Mautic\QueueBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class QueueRequestHelper
{
    public static function flattenRequest(Request $request)
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

    public static function buildRequest(array $request)
    {
        if (!isset($request['attributes'])) {
            throw new \InvalidArgumentException('Request does not have expected keys. Use QueueRequestHelper::flattenRequest to prepare a Request object');
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
