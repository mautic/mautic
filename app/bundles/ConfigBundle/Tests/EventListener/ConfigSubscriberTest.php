<?php

namespace Mautic\ConfigBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConfigSubscriberTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var Container|MockObject
     */
    private $container;

    /**
     * @var ConfigChangeLogger|MockObject
     */
    private $logger;

    /**
     * @var ConfigSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->container            = $this->createMock(Container::class);
        $this->logger               = $this->createMock(ConfigChangeLogger::class);
        $this->subscriber           = new ConfigSubscriber($this->coreParametersHelper, $this->container, $this->logger);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                ConfigEvents::CONFIG_PRE_SAVE  => ['escapePercentCharacters', 1000],
                ConfigEvents::CONFIG_POST_SAVE => ['onConfigPostSave', 0],
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testEscapePercentCharacters()
    {
        $config        = [
            'regular value'                           => 'Nothing to do here',
            'single percent'                          => 'Still nothing % to do here',
            'simple escape'                           => 'This will be %escaped%',
            'do not escape valid vars from container' => 'this is cool var %kernel.root_dir%',
            'do not escape valid vars from config'    => 'Check out %mautic.something%',
            'escape complex string'                   => "
                trk_tit = trk_tit.replace(/\\%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            ",
        ];
        $configEscaped = [
            'regular value'                           => 'Nothing to do here',
            'single percent'                          => 'Still nothing % to do here',
            'simple escape'                           => 'This will be %%escaped%%',
            'do not escape valid vars from container' => 'this is cool var %kernel.root_dir%',
            'do not escape valid vars from config'    => 'Check out %mautic.something%',
            'escape complex string'                   => "
                trk_tit = trk_tit.replace(/\\%%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            ",
        ];

        $event = new ConfigEvent($config, new ParameterBag());

        $this->container->method('getParameter')->will(
            $this->returnCallback(
                function ($param) {
                    if ('kernel.root_dir' === $param) {
                        return '/some/path';
                    }

                    throw new ParameterNotFoundException($param);
                }
            )
        );

        $this->coreParametersHelper->method('hasParameter')->will(
            $this->returnCallback(
                function ($param) {
                    return 'mautic.something' === $param;
                }
            )
        );

        $this->subscriber->escapePercentCharacters($event);

        $this->assertEquals($configEscaped, $event->getConfig());
    }

    public function testNothingToLogOnConfigPostSave()
    {
        // Test nothing to log
        $this->logger->expects($this->never())
            ->method('log');
        $event = $this->createMock(ConfigEvent::class);
        $event->expects($this->once())
            ->method('getOriginalNormData')
            ->willReturn(null);

        $this->subscriber->onConfigPostSave($event);
    }

    public function testSomethingToLogOnConfigPostSave()
    {
        // Test something to log
        $originalNormData = ['orig'];
        $normData         = ['norm'];

        $event = $this->createMock(ConfigEvent::class);
        $event->expects($this->once())
            ->method('getOriginalNormData')
            ->willReturn($originalNormData);
        $event->expects($this->once())
            ->method('getNormData')
            ->willReturn($normData);
        $this->logger->expects($this->once())
            ->method('setOriginalNormData')
            ->with($originalNormData)
            ->willReturn($this->logger);
        $this->logger->expects($this->once())
            ->method('log')
            ->with($normData);

        $this->subscriber->onConfigPostSave($event);
    }
}
