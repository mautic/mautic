<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\TestCase;

class RandomParameterNameTest extends TestCase
{
    public function testGenerateRandomParameterName(): void
    {
        $generator = new RandomParameterName();

        $expectedValues = [
            'par0',
            'par1',
            'par2',
            'par3',
            'par4',
            'par5',
            'par6',
            'par7',
            'par8',
            'par9',
            'para',
            'parb',
            'parc',
            'pard',
            'pare',
            'parf',
            'parg',
            'parh',
            'pari',
            'parj',
            'park',
            'parl',
            'parm',
            'parn',
            'paro',
            'parp',
            'parq',
            'parr',
            'pars',
            'part',
            'paru',
            'parv',
            'parw',
            'parx',
            'pary',
            'parz',
            'par10',
            'par11',
        ];

        foreach ($expectedValues as $expectedValue) {
            self::assertSame($expectedValue, $generator->generateRandomParameterName());
        }
    }
}
