<?php

declare(strict_types=1);

namespace Mautic\ConfigBundle\Tests\Event;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Helper\BundleHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ConfigBuilderEventTest extends TestCase
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

    protected function initEvent(): ConfigBuilderEvent
    {
        return new ConfigBuilderEvent($this->createStub(BundleHelper::class));
    }
}
