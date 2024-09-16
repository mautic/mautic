<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class LeadListFiltersChoicesEventTest extends TestCase
{
    public function testGetters(): void
    {
        $operators = [
            'text' => [
                'equals'      => '=',
                'not equal'   => '!=',
                'empty'       => 'empty',
                'not empty'   => '!empty',
                'like'        => 'like',
                'not like'    => '!like',
                'regexp'      => 'regexp',
                'not regexp'  => '!regexp',
                'starts with' => 'startsWith',
                'ends with'   => 'endsWith',
                'contains'    => 'contains',
            ],
            'select' => [
                'equals'     => '=',
                'not equal'  => '!=',
                'empty'      => 'empty',
                'not empty'  => '!empty',
                'including'  => 'in',
                'excluding'  => '!in',
                'regexp'     => 'regexp',
                'not regexp' => '!regexp',
            ],
            'bool' => [
                'equals'    => '=',
                'not equal' => '!=',
            ],
        ];

        $choices                     = [0 => 'Choice1', 1 => 'Choice2'];
        $search                      = 'Test Search';
        $translator                  = $this->createMock(TranslatorInterface::class);
        $leadListFiltersChoicesEvent = new LeadListFiltersChoicesEvent($choices, $operators, $translator, new Request(), $search);
        $this->assertSame($operators, $leadListFiltersChoicesEvent->getOperators());
        $this->assertSame($choices, $leadListFiltersChoicesEvent->getChoices());
        $this->assertSame($translator, $leadListFiltersChoicesEvent->getTranslator());
        $this->assertSame($search, $leadListFiltersChoicesEvent->getSearch());
    }
}
