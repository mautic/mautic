<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SmsTransportPassTest extends AbstractMauticTestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new SmsTransportPass());
        $container
            ->register('foo')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAliasDefault', 'fakeIntegrationDefault']);

        $container
            ->register('chocolate')
            ->setPublic(true)
            ->setAbstract(true);

        $container
            ->register('bar')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAlias', 'fakeIntegration']);

        $transport = $this->getMockBuilder(TransportChain::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['addTransport'])
                          ->getMock();

        $container
            ->register('mautic.sms.transport_chain')
            ->setClass(get_class($transport))
            ->setArguments(['foo', $this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic')])
            ->setShared(false)
            ->setSynthetic(true)
            ->setAbstract(true);

        $pass = new SmsTransportPass();
        $pass->process($container);

        $this->assertEquals(2, count($container->findTaggedServiceIds('mautic.sms_transport')));

        $this->assertCount(count($container->getDefinition('mautic.sms.transport_chain')->getMethodCalls()),
                           $container->findTaggedServiceIds('mautic.sms_transport'));
    }
}
