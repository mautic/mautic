<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

use Mautic\FormBundle\Event\FormBuilderEvent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormSubscriberTest extends WebTestCase
{
    public function testOnFormBuilder()
    {
        $testAction = [
            'group'       => 'this is the group name',
            'label'       => null,
            'description' => 'short description of action',
            'formType'    => 'name of form to display',
            'formTheme'   => 'MauticSomeBundle:FormTheme\AformSubmitAction',
            'callback'    => '\A\Bundle\Path\To::functionName',
        ];

        $translator = $this->getMockBuilder('\Symfony\Bundle\FrameworkBundle\Translation\Translator')->disableOriginalConstructor()
            ->getMock();

        $formBuilderEvent = new FormBuilderEvent($translator);
        $formBuilderEvent->addSubmitAction('myAction', $testAction);
        $actions = $formBuilderEvent->getSubmitActions();

        $mockEventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        foreach ($actions as $action) {
            $this->assertEquals($testAction, $action);
            if (isset($action['callback'])) {
                $this->returnCallback($action['callback']); //create a callbackfunction that will return something
            }
            if (isset($action['eventName'])) {
                $mockEventDispatcher->expects($this->once())
                    ->method('dispatch')
                    ->with('my_custom_event', $this->isInstanceOf('MyCustomEvent')
                        ->will($this->returnCallback($action['eventName']))); //create an event to be dispatched
            }
        }
    }
}
