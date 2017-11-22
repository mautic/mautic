<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\ReportBundle\Model\ExportResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testResponse()
    {
        $responce = new Response();
        ExportResponse::setResponseHeaders($responce, 'fileName.csv');

        $this->assertSame('application/octet-stream', $responce->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="fileName.csv"', $responce->headers->get('Content-Disposition'));
        $this->assertSame('must-revalidate, private', $responce->headers->get('Cache-Control'));
        $this->assertSame('public', $responce->headers->get('Pragma'));
    }
}
