<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingPixelHelper
{
    public static function sendResponse(Request $request): void
    {
        $response = self::getResponse($request);
        $response->send();
    }

    /**
     * @return Response
     */
    public static function getResponse(Request $request)
    {
        $response = new Response();

        if ('test' === MAUTIC_ENV) {
            return $response;
        }

        if (ini_get('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        // turn off gzip compression
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }

        ini_set('zlib.output_compression', '0');

        // removing any content encoding like gzip etc.
        $response->headers->set('Content-Encoding', 'none');

        // check to ses if request is a POST
        if ('GET' == $request->getMethod()) {
            if ('HTTP/1.1' == $request->getProtocolVersion()) {
                $response->headers->set('Connection', 'close');
            }

            // return 1x1 pixel transparent gif
            $response->headers->set('Content-Type', 'image/gif');
            // avoid cache time on browser side
            $response->headers->set('Content-Length', '43');
            $response->headers->set('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate');
            $response->headers->set('Expires', 'Wed, 11 Jan 2000 12:59:00 GMT');
            $response->headers->set('Last-Modified', 'Wed, 11 Jan 2006 12:59:00 GMT');
            $response->headers->set('Pragma', 'no-cache');

            $response->setContent(self::getImage());
        } else {
            $response->setContent(' ');
        }

        return $response;
    }

    public static function getImage(): string
    {
        return base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
    }
}
