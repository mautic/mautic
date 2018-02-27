<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TrackingPixelHelper.
 */
class TrackingPixelHelper
{
    public static function sendResponse(Request $request)
    {
        $response = self::getResponse($request);
        $response->send();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public static function getResponse(Request $request)
    {
        $response = new Response();

        if ('test' === MAUTIC_ENV) {
            return $response;
        }

        ignore_user_abort(true);

        //turn off gzip compression
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }

        ini_set('zlib.output_compression', 0);

        //removing any content encoding like gzip etc.
        $response->headers->set('Content-Encoding', 'none');

        //check to ses if request is a POST
        if ($request->getMethod() == 'GET') {
            $response->headers->set('Connection', 'close');

            //return 1x1 pixel transparent gif
            $response->headers->set('Content-Type', 'image/gif');
            //avoid cache time on browser side
            $response->headers->set('Content-Length', '42');
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

    /**
     * @return string
     */
    public static function getImage()
    {
        return sprintf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%', 71, 73, 70, 56, 57, 97, 1, 0, 1, 0, 128, 255, 0, 192, 192, 192, 0, 0, 0, 33, 249, 4, 1, 0, 0, 0, 0, 44, 0, 0, 0, 0, 1, 0, 1, 0, 0, 2, 2, 68, 1, 0, 59);
    }
}
