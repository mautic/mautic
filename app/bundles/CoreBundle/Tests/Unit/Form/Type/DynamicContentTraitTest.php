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

use Mautic\CoreBundle\Entity\DynamicContentEntityTrait;
use Mautic\CoreBundle\Form\Type\DynamicContentTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

final class DynamicContentTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var MockObject|FormEvent
     */
    private $formEvent;

    /**
     * @var MockObject|FormInterface
     */
    private $form;

    /**
     * @var MockObject|DynamicContentEntityTrait
     */
    private $entity;

    /**
     * @var MockObject|DynamicContentTrait
     */
    private $trait;

    protected function setUp()
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formEvent   = $this->createMock(FormEvent::class);
        $this->form        = $this->createMock(FormInterface::class);
        $this->entity      = $this->getMockForTrait(DynamicContentEntityTrait::class);
        $this->trait       = $this->getMockForTrait(DynamicContentTrait::class);
    }

    /**
     * There is a problem when a user just grags&drop the Dynamic Content slot
     * without configuring it. New email won't save with no error. We must ensure
     * each dynamic content slot has its full structure.
     */
    public function testAddDynamicContentFieldWithDecWithoutFiltersAndContent()
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) {
                    $inputData = [
                        'dynamicContent' => [
                            [
                                'content' => '',
                            ],
                        ],
                    ];

                    $outputData = [
                        'dynamicContent' => [
                            [
                                'content' => '',
                                'filters' => [
                                    [
                                        'content' => null,
                                        'filters' => [
                                            [
                                                'glue'     => null,
                                                'field'    => null,
                                                'object'   => null,
                                                'type'     => null,
                                                'operator' => null,
                                                'display'  => null,
                                                'filter'   => null,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $this->formEvent->expects($this->once())
                        ->method('getForm')
                        ->willReturn($this->form);

                    $this->form->expects($this->once())
                        ->method('getData')
                        ->willReturn($this->entity);

                    $this->formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn($inputData);

                    $this->formEvent->expects($this->once())
                        ->method('setData')
                        ->with($outputData);

                    $formModifier($this->formEvent);

                    return true;
                })
            );

        $this->invokeMethod($this->trait, 'addDynamicContentField', [$this->formBuilder]);
    }

    private function invokeMethod($object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
