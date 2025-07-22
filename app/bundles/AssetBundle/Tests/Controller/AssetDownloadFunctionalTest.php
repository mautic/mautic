<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AssetDownloadFunctionalTest extends MauticMysqlTestCase
{
    public function testDownloadOfNotFoundAsset(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');

        // The 500 error happened only on the second request.
        // It happened only if the device was already tracked.
        $this->client->request(Request::METHOD_GET, '/asset/unicorn'); // returns 404 correctly
        $this->client->request(Request::METHOD_GET, '/asset/unicorn'); // returned 500 but it should return 404

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
