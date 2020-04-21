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

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Form\Type\FilterType;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Provider\FormAdjustmentsProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class FilterTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|FormAdjustmentsProviderInterface
     */
    private $formAdjustmentsProvider;

    /**
     * @var MockObject|ListModel
     */
    private $listModel;

    /**
     * @var FilterType
     */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator              = $this->createMock(TranslatorInterface::class);
        $this->formAdjustmentsProvider = $this->createMock(FormAdjustmentsProviderInterface::class);
        $this->listModel               = $this->createMock(ListModel::class);
        $this->form                    = new FilterType(
            $this->translator,
            $this->formAdjustmentsProvider,
            $this->listModel
        );
    }

    public function testTransform(): void
    {
        /** @var MockObject|FormBuilderInterface $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        $this->listModel->expects($this->once())
            ->method('getChoiceFields')
            ->willReturn([
                'lead' => [
                    'address1' => [
                        'label'      => 'Address Line 1',
                        'properties' => [
                            'type' => 'text',
                        ],
                        'object'    => 'lead',
                        'operators' => [
                            'equals' => 'eq',
                        ],
                    ],
                ],
            ]);

        // Adding a filter with an existing field:
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                $this->callback(
                    function (callable $formModifier) {
                        /** @var FormInterface|MockObject $form */
                        $form = $this->createMock(FormInterface::class);
                        $data = [
                            'field'    => 'address1',
                            'object'   => 'lead',
                            'operator' => 'eq',
                        ];

                        $form->expects($this->exactly(2))
                            ->method('add');

                        $form->expects($this->once())
                            ->method('get')
                            ->willReturnSelf();

                        $this->formAdjustmentsProvider->expects($this->once())
                            ->method('adjustForm');

                        $formModifier(new FormEvent($form, $data));

                        return true;
                    }
                )
            );

        // Adding a filter with a deleted field:
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(
                    function (callable $formModifier) {
                        /** @var FormInterface|MockObject $form */
                        $form = $this->createMock(FormInterface::class);
                        $data = [
                            'field'    => 'deleted',
                            'object'   => 'lead',
                            'operator' => 'eq',
                        ];

                        $form->expects($this->never())
                            ->method('add');

                        $form->expects($this->never())
                            ->method('get')
                            ->willReturnSelf();

                        $this->formAdjustmentsProvider->expects($this->never())
                            ->method('adjustForm');

                        $formModifier(new FormEvent($form, $data));

                        return true;
                    }
                )
            );

        $this->form->buildForm($builder, $options);
    }
}
