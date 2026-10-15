<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Form\Type\FilterTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Reproduces https://github.com/mautic/mautic/issues/16280: saving a DWC "Segment Membership"
 * (or any multiselect) filter with the "Including ALL" / "Excluding ALL" operator crashed,
 * because FilterTrait always turns the submitted filter value into an array for these field
 * types, but only flagged the underlying ChoiceType as `multiple` for the "in"/"!in" ("Including
 * ANY" / "Excluding ANY") operators - leaving "in_all"/"!in_all" building a single-select
 * ChoiceType fed an array value.
 */
final class FilterTraitTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testLeadlistFilterWithIncludingAllOperatorDoesNotCrashOnSubmit(): void
    {
        $form = $this->factory->create(FilterTraitStubFormType::class, null, [
            'lists'  => ['Segment A' => '1', 'Segment B' => '2'],
            'fields' => [],
        ]);

        $form->submit([
            'field'    => 'leadlist',
            'object'   => 'lead',
            'type'     => 'leadlist',
            'operator' => 'in_all',
            'filter'   => ['2'],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertSame(['2'], $form->get('filter')->getData());
    }

    public function testMultiselectFilterWithExcludingAllOperatorDoesNotCrashOnSubmit(): void
    {
        $form = $this->factory->create(FilterTraitStubFormType::class, null, [
            'lists'  => [],
            'fields' => [
                'lead' => [
                    'custom_multiselect' => [
                        'properties' => [
                            'list' => ['Option A' => 'a', 'Option B' => 'b'],
                        ],
                    ],
                ],
            ],
        ]);

        $form->submit([
            'field'    => 'custom_multiselect',
            'object'   => 'lead',
            'type'     => 'multiselect',
            'operator' => '!in_all',
            'filter'   => ['a'],
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertSame(['a'], $form->get('filter')->getData());
    }
}

/**
 * Minimal stand-in for the real FilterTrait consumers (e.g. DwcEntryFiltersType, FilterType):
 * wires FilterTrait::buildFiltersForm() up to PRE_SET_DATA/PRE_SUBMIT exactly as they do, without
 * needing their heavier constructor dependencies. The field type under test ('leadlist' vs
 * 'multiselect') is driven entirely by the submitted data, not by this stub, so a single stub
 * type covers both scenarios.
 *
 * @extends AbstractType<mixed>
 */
final class FilterTraitStubFormType extends AbstractType
{
    use FilterTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translator = new class() implements TranslatorInterface {
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id ?? '';
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };

        $formModifier = function (FormEvent $event, string $eventName) use ($translator): void {
            $this->buildFiltersForm($eventName, $event, $translator);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier): void {
            $formModifier($event, FormEvents::PRE_SET_DATA);
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier): void {
            $formModifier($event, FormEvents::PRE_SUBMIT);
        });

        $builder->add('field', HiddenType::class);
        $builder->add('object', HiddenType::class);
        $builder->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'lists'  => [],
            'fields' => [],
        ]);
    }
}
