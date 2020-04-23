<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Form\Type\DynamicListType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class DynamicListTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var DynamicListType
     */
    private $form;

    protected function setUp()
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->form        = new DynamicListType();
    }

    public function testBuildFormWhenDataIsNull()
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) {
                    $formEvent = $this->createMock(FormEvent::class);

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn(null);

                    $formEvent->expects($this->never())
                        ->method('setData');

                    $formModifier($formEvent);

                    return true;
                })
            );

        $this->form->buildForm($this->formBuilder, []);
    }

    public function testBuildFormWhenDataIsArray()
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) {
                    $formEvent = $this->createMock(FormEvent::class);
                    $data = [['content' => 'dynamic slot content']];

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn($data);

                    $formEvent->expects($this->once())
                        ->method('setData')
                        ->with($data);

                    $formModifier($formEvent);

                    return true;
                })
            );

        $this->form->buildForm($this->formBuilder, []);
    }
}
