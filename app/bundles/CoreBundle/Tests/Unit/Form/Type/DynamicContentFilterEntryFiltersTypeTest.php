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

use Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class DynamicContentFilterEntryFiltersTypeTest extends TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var ListModel|MockObject
     */
    private $listModel;

    /**
     * @var DynamicContentFilterEntryFiltersType
     */
    private $form;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listModel  = $this->createMock(ListModel::class);

        $this->form =  new DynamicContentFilterEntryFiltersType($this->translator, $this->listModel);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(4))
            ->method('add')
            ->withConsecutive(
                [
                    'glue',
                    ChoiceType::class,
                    [
                        'label'             => false,
                        'choices'           => [
                            'mautic.lead.list.form.glue.and' => 'and',
                            'mautic.lead.list.form.glue.or'  => 'or',
                        ],
                        'attr'              => [
                            'class'    => 'form-control not-chosen glue-select',
                            'onchange' => 'Mautic.updateFilterPositioning(this)',
                        ],
                    ],
                ],
                [
                    'field',
                    HiddenType::class,
                ],
                [
                    'object',
                    HiddenType::class,
                ],
                [
                    'type',
                    HiddenType::class,
                ]
            );

        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [
                    FormEvents::PRE_SET_DATA,
                    function (FormEvent $event) use ($formModifier) {
                        $formModifier($event, FormEvents::PRE_SET_DATA);
                    },
                ],
                [
                    FormEvents::PRE_SUBMIT,
                    function (FormEvent $event) use ($formModifier) {
                        $formModifier($event, FormEvents::PRE_SUBMIT);
                    },
                ]
            );

        $this->form->buildForm($builder, []);
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('dynamic_content_filter_entry_filters', $this->form->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setRequired')
            ->with([
                'countries',
                'regions',
                'timezones',
                'stages',
                'locales',
                'fields',
                'lists',
            ]);

        $this->form->configureOptions($resolver);
    }
}
