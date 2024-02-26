<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\LookupType;
use Symfony\Component\Form\Test\TypeTestCase;

class LookupTypeTest extends TypeTestCase
{
    /**
     * @param array<string, string> $attributes
     * @param array<string, string> $expected
     *
     * @dataProvider provideLookupAttributes
     */
    public function testCreateViewAddsAttributesWithoutOverride(array $attributes, array $expected): void
    {
        $form = $this->factory->create(LookupType::class, null, ['attr' => $attributes]);
        $view = $form->createView();

        self::assertSame($expected, $view->vars['attr']);
    }

    public static function provideLookupAttributes(): \Generator
    {
        yield 'empty attributes' => [
            [],
            [
                'data-toggle' => 'field-lookup',
                'data-action' => 'lead:fieldList',
            ],
        ];

        yield 'other attributes' => [
            [
                'data-other' => 'value',
            ],
            [
                'data-toggle' => 'field-lookup',
                'data-action' => 'lead:fieldList',
                'data-other'  => 'value',
            ],
        ];

        yield 'does not override attributes (all)' => [
            [
                'data-toggle' => 'other-lookup',
                'data-action' => 'lead:lead',
            ],
            [
                'data-toggle' => 'other-lookup',
                'data-action' => 'lead:lead',
            ],
        ];

        yield 'does not override attributes (toggle)' => [
            [
                'data-toggle' => 'other-lookup',
            ],
            [
                'data-toggle' => 'other-lookup',
                'data-action' => 'lead:fieldList',
            ],
        ];

        yield 'does not override attributes (action)' => [
            [
                'data-action' => 'lead:lead',
            ],
            [
                'data-toggle' => 'field-lookup',
                'data-action' => 'lead:lead',
            ],
        ];
    }
}
