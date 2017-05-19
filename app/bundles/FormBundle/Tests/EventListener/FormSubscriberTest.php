<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormSubscriberTest extends WebTestCase
{
    public function testOnFormBuilder()
    {
        $testActionCallback = [
            'group'       => 'this is the group name',
            'label'       => null,
            'description' => 'short description of action',
            'formType'    => 'name of form to display',
            'formTheme'   => 'MauticSomeBundle:FormTheme\AformSubmitAction',
            'callback'    => '\A\Bundle\Path\To::functionName',
        ];

        $translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()
            ->getMock();

        $formBuilderEvent = new FormBuilderEvent($translator);
        $formBuilderEvent->addSubmitAction('myActionCallback', $testActionCallback);
        $actions = $formBuilderEvent->getSubmitActions();

        foreach ($actions as $action) {
            if (isset($action['callback'])) {
                $this->assertEquals($testActionCallback, $action);
                $this->assertArrayHasKey('callback', $action);
                $this->returnCallback($action['callback']); //create a callbackfunction that will return something
            }
        }
    }
}
