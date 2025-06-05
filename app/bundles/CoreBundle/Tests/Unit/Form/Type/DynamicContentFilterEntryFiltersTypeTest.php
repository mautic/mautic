<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Form\Type\DynamicContentFilterEntryFiltersType;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicContentFilterEntryFiltersTypeTest extends TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var ListModel&MockObject
     */
    private MockObject $listModel;

    private DynamicContentFilterEntryFiltersType $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listModel  = $this->createMock(ListModel::class);
        $this->form       =  new DynamicContentFilterEntryFiltersType($this->translator, $this->listModel);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $matcher = self::exactly(4);
        $builder->expects($matcher)
            ->method('add')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('glue', $parameters[0]);
                    $this->assertSame(ChoiceType::class, $parameters[1]);
                    $this->assertSame([
                        'label'   => false,
                        'choices' => [
                            'mautic.lead.list.form.glue.and' => 'and',
                            'mautic.lead.list.form.glue.or'  => 'or',
                        ],
                        'attr' => [
                            'class'    => 'form-control not-chosen glue-select',
                            'onchange' => 'Mautic.updateFilterPositioning(this)',
                        ],
                    ], $parameters[2]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('field', $parameters[0]);
                    $this->assertSame(HiddenType::class, $parameters[1]);
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('object', $parameters[0]);
                    $this->assertSame(HiddenType::class, $parameters[1]);
                }
                if (4 === $matcher->numberOfInvocations()) {
                    $this->assertSame('type', $parameters[0]);
                    $this->assertSame(HiddenType::class, $parameters[1]);
                }

                return $builder;
            });
        $matcher = $this->exactly(2);

        $builder->expects($matcher)
            ->method('addEventListener')->willReturnCallback(function (...$parameters) use ($matcher, $builder) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SET_DATA, $parameters[0]);
                    $this->assertIsCallable($parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame(FormEvents::PRE_SUBMIT, $parameters[0]);
                    $this->assertIsCallable($parameters[1]);
                }

                return $builder;
            });

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
