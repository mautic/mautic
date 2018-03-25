<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\SmsBundle\Api\TwilioApi;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Compiler\RepeatedPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SmsTransportPassTest extends AbstractMauticTestCase
{
    private function getWilioMock()
    {
        return $this->getMockBuilder(TwilioApi::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new SmsTransportPass(), PassConfig::TYPE_OPTIMIZE);
        $container
            ->register('foo')
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAliasDefault', 'fakeIntegration'])
            ->setPublic(true)
            ->setSynthetic(true)
        ;
        $container->set('foo', $this->getWilioMock());

        $container
            ->register('chocolate')
            ->setPublic(true)
            ->setAbstract(true)
        ;
        $container
            ->register('bar')
            ->setPublic(true)
            ->setAbstract(true)
            ->addTag('mautic.sms_transport', ['alias'=>'fakeAlias', 'fakeIntegration'])
        ;
        $container->set('bar', $this->getWilioMock());

        $transport = $this->getMockBuilder(TransportChain::class)
                            ->disableOriginalConstructor()
                            ->setMethods(['addTransport'])
                            ->getMock();

        $container
            ->register('mautic.sms.transport_chain')
            ->setClass(get_class($transport))
            ->setArguments(['foo', $this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic')])
            ->setShared(false)
        ;

        $adds = 0;
        $transport->expects($this->exactly(10000))
                  ->method('addTransport')
                  ->willReturnCallback(
                      function ($endpoint, $parameters) use (&$adds) {
                          ++$adds;
                          var_dump($adds);

                          return $this->countAdds(3);
                      }
                  );

        $definition     = $container->getDefinition('mautic.sms.transport_chain');
        var_dump($definition->getMethodCalls());

        $container->compile();

        $definition     = $container->getDefinition('mautic.sms.transport_chain');
        var_dump($container->get('mautic.sms.transport_chain'));
        var_dump($definition->getMethodCalls());

        $this->assertCount(2, $container->findTaggedServiceIds('mautic.sms_transport'));

        $this->assertEquals(3, $adds);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new RepeatedPass([new SmsTransportPass()]);
        $repeatedPass->process($container);
    }
}
