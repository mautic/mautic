<?php

namespace Mautic\SmsBundle\Tests\DependencyInjection\Compiler;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\DependencyInjection\Compiler\SmsTransportPass;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SmsTransportPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new SmsTransportPass());
        $container
            ->register('foo')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAliasDefault', 'integrationAlias' => 'fakeIntegrationDefault']);

        $container
            ->register('chocolate')
            ->setPublic(true)
            ->setAbstract(true);

        $container
            ->register('bar')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport');

        $transport = $this->getMockBuilder(TransportChain::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addTransport'])
            ->getMock();

        $container
            ->register('mautic.sms.transport_chain')
            ->setClass(get_class($transport))
            ->setArguments(['foo', $this->createMock(IntegrationHelper::class)])
            ->setShared(false)
            ->setSynthetic(true)
            ->setAbstract(true);

        $pass = new SmsTransportPass();
        $pass->process($container);

        $this->assertEquals(2, count($container->findTaggedServiceIds('mautic.sms_transport')));

        $methodCalls = $container->getDefinition('mautic.sms.transport_chain')->getMethodCalls();
        $this->assertCount(count($methodCalls), $container->findTaggedServiceIds('mautic.sms_transport'));

        // Translation string
        $this->assertEquals('fakeAliasDefault', $methodCalls[0][1][2]);
        // Integration name/alias
        $this->assertEquals('fakeIntegrationDefault', $methodCalls[0][1][3]);

        // Translation string is set as service ID by default
        $this->assertEquals('bar', $methodCalls[1][1][2]);
        // Integration name/alias is set to service ID by default
        $this->assertEquals('bar', $methodCalls[1][1][3]);
    }
}
