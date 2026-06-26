<?php

namespace Mautic\ConfigBundle\Tests\Event;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Tests\CommonMocks;

class ConfigBuilderEventTest extends CommonMocks
{
    public function testAddForm(): void
    {
        $event  = $this->initEvent();
        $form   = ['formAlias' => 'testform'];
        $result = $event->addForm($form);

        $this->assertInstanceOf(ConfigBuilderEvent::class, $result);

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

        $this->assertEquals([], $forms);
        $this->assertTrue($result);
    }

    protected function initEvent(): ConfigBuilderEvent
    {
        return new ConfigBuilderEvent($this->getBundleHelperMock());
    }
}
