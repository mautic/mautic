<?php

namespace Mautic\ReportBundle\Model;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExportResponse.
 */
class ExportResponse
{
    /**
     * @param string $fileName
     */
    public static function setResponseHeaders(Response $response, $fileName)
    {
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
    }
}
