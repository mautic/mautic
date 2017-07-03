<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class QueueRequestHelper
{
    /**
     * @param Request $request
     */
    static public function flattenRequest(Request $request)
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

    /**
     * @param array $request
     */
    static public function buildRequest(array $request)
    {
        if (!isset($request['attributes'])) {
            throw new \InvalidArgumentException(
                'Request does not have expected keys. Use QueueRequestHelper::flattenRequest to prepare a Request object'
            );
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