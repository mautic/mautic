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

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;

class FilterPropertiesTypeEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormInterface
     */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = $this->createMock(FormInterface::class);
    }

    public function testConstructGettersSetters(): void
    {
        $alias        = 'email';
        $object       = 'lead';
        $operator     = OperatorOptions::EQUAL_TO;
        $fieldDetails = [
            'properties' => [
                'type' => 'text',
                'list' => ['one', 'two'],
            ],
        ];
        $event = new FilterPropertiesTypeEvent($this->form, $alias, $object, $operator, $fieldDetails);

        $this->assertSame($this->form, $event->getFilterPropertiesForm());
        $this->assertSame($alias, $event->getFieldAlias());
        $this->assertSame($object, $event->getFieldObject());
        $this->assertSame($operator, $event->getOperator());
        $this->assertSame($fieldDetails, $event->getFieldDetails());
        $this->assertSame('text', $event->getFieldType());
        $this->assertSame(['one', 'two'], $event->getFieldChoices());
        $this->assertFalse($event->filterShouldBeDisabled());
        $this->assertFalse($event->operatorIsOneOf(OperatorOptions::LESS_THAN));
        $this->assertFalse($event->operatorIsOneOf(OperatorOptions::LESS_THAN, OperatorOptions::NOT_EQUAL_TO));
        $this->assertTrue($event->operatorIsOneOf(OperatorOptions::LESS_THAN, OperatorOptions::EQUAL_TO));
        $this->assertFalse($event->fieldTypeIsOneOf('select'));
        $this->assertTrue($event->fieldTypeIsOneOf('select', 'text'));
    }
}
