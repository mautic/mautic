<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Helper\RequestStorageHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport\NullTransport;

/**
 * @deprecated as unused. To be removed in Mautic 6.0.
 */
class RequestStorageHelperTest extends MauticMysqlTestCase
{
    private RequestStorageHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = static::getContainer()->get(RequestStorageHelper::class);
    }

    public function testStoreRequest(): void
    {
        $key = $this->helper->storeRequest(NullTransport::class, new Request([], ['some' => 'values']));

        $this->assertStringStartsWith('Symfony|Component|Mailer|Transport|NullTransport', $key);

        $request = $this->helper->getRequest($key);
        $this->assertEquals(['some' => 'values'], $request->request->all());
    }

    public function testDeleteRequest(): void
    {
        $key = $this->helper->storeRequest(NullTransport::class, new Request([], ['some' => 'values']));

        $request = $this->helper->getRequest($key);
        $this->assertEquals(['some' => 'values'], $request->request->all());

        $this->helper->deleteCachedRequest($key);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Request with key '{$key}' was not found.");
        $request = $this->helper->getRequest($key);
    }

    public function testGetRequestIfNotFound(): void
    {
        $key = NullTransport::class.';webhook_request;5b43832134cfb0.36545510';

        $this->expectException(\UnexpectedValueException::class);
        $this->helper->getRequest($key);
    }

    public function testGetTransportNameFromKey(): void
    {
        $this->assertEquals(NullTransport::class, $this->helper->getTransportNameFromKey(NullTransport::class.';webhook_request;5b43832134cfb0.36545510'));
    }

    /**
     * The StorageHelper will add '%mautic.db_table_prefix%' as a prefix to each cache key.
     */
    public function testGetTransportNameFromKeyWithGlobalPrefix(): void
    {
        $this->assertEquals(NullTransport::class, $this->helper->getTransportNameFromKey('mautic:Symfony|Component|Mailer|Transport|NullTransport;webhook_request;5bfbe8ce671198.00044461'));
    }
}
