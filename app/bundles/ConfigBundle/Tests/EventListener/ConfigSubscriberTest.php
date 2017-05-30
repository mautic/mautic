<?php

namespace Mautic\ConfigBundle\Tests\EventListener;

use Mautic\ConfigBundle\EventListener\ConfigSubscriber;
use Mautic\ConfigBundle\Event\ConfigEvent;

class ConfigSubscriberTest extends \PHPUnit_Framework_TestCase {

    public function testEscapePercentCharacters() {
        $config = [
            'regularValue' => 'Nothing to do here',
            'single percent' => 'Still nothing % to do here',
            'simple escape' => 'This will be %escaped%',
            'do not escape valid vars' => 'this is cool var %kernel.root_dir%',
            'escape complex string' => "
                trk_tit = trk_tit.replace(/\\%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            "
        ];
        $configEscaped = [
            'regularValue' => 'Nothing to do here',
            'single percent' => 'Still nothing % to do here',
            'simple escape' => 'This will be %%escaped%%',
            'do not escape valid vars' => 'this is cool var %kernel.root_dir%',
            'escape complex string' => "
                trk_tit = trk_tit.replace(/\\%%u00a0/g, '');
                trk_tit = trk_tit.replace(/\\%%u2122/g, '');
                trk_tit = trk_tit.replace(/\\%u[0-9][0-9][0-9][0-9]/g, '');
            "
        ];

        $event = new ConfigEvent($config, $this->getMockBuilder('\Symfony\Component\HttpFoundation\ParameterBag')->getMock());
        $paramHelper = $this->getMockBuilder('\Mautic\CoreBundle\Helper\CoreParametersHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $paramHelper->method('getParameter')->will($this->returnCallback(function ($param) {
            if($param === 'kernel.root_dir') {
                return '/some/path';
            }
            return null;
        }));

        $configSubscriber = new ConfigSubscriber($paramHelper);

        $configSubscriber->escapePercentCharacters($event);

        $this->assertEquals($configEscaped, $event->getConfig());
    }
}
