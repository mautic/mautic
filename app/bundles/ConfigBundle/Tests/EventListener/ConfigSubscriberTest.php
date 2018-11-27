<?php

namespace Mautic\ConfigBundle\Tests\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $paramHelper = $this->createMock(CoreParametersHelper::class);
        $logger      = $this->createMock(ConfigChangeLogger::class);
        $subscriber  = new ConfigSubscriber($paramHelper, $logger);

        $this->assertEquals(
            [
                ConfigEvents::CONFIG_PRE_SAVE  => ['escapePercentCharacters', 1000],
                ConfigEvents::CONFIG_POST_SAVE => ['onConfigPostSave', 0],
            ],
            $subscriber->getSubscribedEvents()
        );
    }

    public function testEscapePercentCharacters()
    {
        $config = [
            'regularValue'             => 'Nothing to do here',
            'single percent'           => 'Still nothing % to do here',
            'simple escape'            => 'This will be %escaped%',
            'do not escape valid vars' => 'this is cool var %kernel.root_dir%',
            'escape complex string'    => "
                trk_tit = trk_tit.replace(/\\%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            ",
        ];
        $configEscaped = [
            'regularValue'             => 'Nothing to do here',
            'single percent'           => 'Still nothing % to do here',
            'simple escape'            => 'This will be %%escaped%%',
            'do not escape valid vars' => 'this is cool var %kernel.root_dir%',
            'escape complex string'    => "
                trk_tit = trk_tit.replace(/\\%%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            ",
        ];

        $event       = new ConfigEvent($config, $this->getMockBuilder('\Symfony\Component\HttpFoundation\ParameterBag')->getMock());
        $paramHelper = $this->getMockBuilder('\Mautic\CoreBundle\Helper\CoreParametersHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $paramHelper->method('getParameter')->will($this->returnCallback(function ($param) {
            if ($param === 'kernel.root_dir') {
                return '/some/path';
            }

            return null;
        }));

        $configLogger = $this->createMock(ConfigChangeLogger::class);

        $configSubscriber = new ConfigSubscriber($paramHelper, $configLogger);

        $configSubscriber->escapePercentCharacters($event);

        $this->assertEquals($configEscaped, $event->getConfig());
    }

    public function testOnConfigPostSave()
    {
        // Test nothing to log

        $paramHelper = $this->createMock(CoreParametersHelper::class);
        $logger      = $this->createMock(ConfigChangeLogger::class);
        $logger->expects($this->never())
            ->method('log');
        $event = $this->createMock(ConfigEvent::class);
        $event->expects($this->once())
            ->method('getOriginalNormData')
            ->willReturn(null);

        $subscriber = new ConfigSubscriber($paramHelper, $logger);
        $subscriber->onConfigPostSave($event);

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
        $logger = $this->createMock(ConfigChangeLogger::class);
        $logger->expects($this->once())
            ->method('setOriginalNormData')
            ->with($originalNormData)
            ->willReturn($logger);
        $logger->expects($this->once())
            ->method('log')
            ->with($normData);

        $subscriber = new ConfigSubscriber($paramHelper, $logger);
        $subscriber->onConfigPostSave($event);
    }
}
