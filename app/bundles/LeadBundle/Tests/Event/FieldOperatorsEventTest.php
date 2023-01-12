<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\FieldOperatorsEvent;

final class FieldOperatorsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $type         = 'select';
        $field        = 'country';
        $allOperators = [
            '=' => [
                'label'       => 'equals',
                'expr'        => 'eq',
                'negate_expr' => 'neq',
            ],
            '!=' => [
                'label'       => 'not equal',
                'expr'        => 'neq',
                'negate_expr' => 'eq',
            ],
        ];

        $defaultOperators = [
            'equals' => '=',
        ];

        $event = new FieldOperatorsEvent($type, $field, $allOperators, $defaultOperators);

        $this->assertSame($type, $event->getType());
        $this->assertSame($field, $event->getField());
        $this->assertSame($defaultOperators, $event->getOperators());

        $event->addOperator('!=');

        $this->assertSame(['equals' => '=', 'not equal' => '!='], $event->getOperators());
    }
}
