<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Helper\RequestStorageHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport\NullTransport;

class RequestStorageHelperTest extends MauticMysqlTestCase
{
    private RequestStorageHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = $this->getContainer()->get('mautic.email.helper.request.storage');
    }

    public function testStoreRequest(): void
    {
        $key = $this->helper->storeRequest(MomentumTransport::class, new Request([], ['some' => 'values']));

        $this->assertStringStartsWith('Mautic|EmailBundle|Swiftmailer|Transport|MomentumTransport', $key);
        $this->assertEquals(98, strlen($key));

        $request = $this->helper->getRequest($key);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals(['some' => 'values'], $request->request->all());
    }

    public function testGetRequestIfNotFound(): void
    {
        $payload = ['some' => 'values'];
        $key     = NullTransport::class.':webhook_request:5b43832134cfb0.36545510';

        $this->expectException(\UnexpectedValueException::class);
        $this->helper->getRequest($key);
    }

    public function testGetTransportNameFromKey(): void
    {
        $this->assertEquals(NullTransport::class, $this->helper->getTransportNameFromKey(NullTransport::class.':webhook_request:5b43832134cfb0.36545510'));
    }

    /**
     * The StorageHelper will add '%mautic.db_table_prefix%' as a prefix to each cache key.
     */
    public function testGetTransportNameFromKeyWithGlobalPrefix(): void
    {
        $this->assertEquals(NullTransport::class, $this->helper->getTransportNameFromKey('mautic:Symfony|Component|Mailer|Transport|NullTransport:webhook_request:5bfbe8ce671198.00044461'));
    }
}
