<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Event;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Tests\CommonMocks;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class ConfigBuilderEventTest extends CommonMocks
{
    public function testAddForm(): void
    {
        $event = $this->initEvent();
        $form  = ['formAlias' => 'testform'];
        $event->addForm($form);

        $forms = $event->getForms();

        $this->assertEquals($form, $forms[$form['formAlias']]);
    }

    public function testRemoveForm(): void
    {
        $event = $this->initEvent();
        $form  = ['formAlias' => 'testform'];

        $event->addForm($form);

        $result = $event->removeForm($form['formAlias']);
        $forms  = $event->getForms();

        $this->assertSame([], $forms);
        $this->assertTrue($result);
    }

    #[RunInSeparateProcess]
    public function testGetParametersFromConfigHandlesIncompleteBundleMetadata(): void
    {
        $bundleHelper = $this->getBundleHelperMock();
        $bundleHelper->expects($this->once())
            ->method('getMauticBundles')
            ->with(true)
            ->willReturn([
                'BundleWithoutConfig' => [],
                'BundleWithoutParameters' => ['config' => []],
                'BundleWithParameters' => [
                    'config' => [
                        'parameters' => ['example' => 'value'],
                    ],
                ],
            ]);

        $event = new ConfigBuilderEvent($bundleHelper);

        $this->assertSame([], $event->getParametersFromConfig('MissingBundle'));
        $this->assertSame([], $event->getParametersFromConfig('BundleWithoutConfig'));
        $this->assertSame([], $event->getParametersFromConfig('BundleWithoutParameters'));
        $this->assertSame(['example' => 'value'], $event->getParametersFromConfig('BundleWithParameters'));
    }

    protected function initEvent(): ConfigBuilderEvent
    {
        return new ConfigBuilderEvent($this->getBundleHelperMock());
    }
}
