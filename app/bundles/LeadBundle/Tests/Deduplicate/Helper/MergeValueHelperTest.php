<?php

namespace Mautic\LeadBundle\Tests\Deduplicate\Helper;

use Mautic\LeadBundle\Deduplicate\Exception\ValueNotMergeableException;
use Mautic\LeadBundle\Deduplicate\Helper\MergeValueHelper;

class MergeValueHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMergeValueWhenNewAndOldValuesAreIdentical(): void
    {
        $newerValue     = 'bbb';
        $olderValue     = 'bbb';
        $winnerValue    = null;
        $defaultValue   = null;
        $newIsAnonymous = false;

        $this->expectException(ValueNotMergeableException::class);
        MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);
    }

    public function testGetMergeValueWhenNewAndWinnerValuesAreIdentical(): void
    {
        $newerValue     = 'bbb';
        $olderValue     = 'aaa';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $this->expectException(ValueNotMergeableException::class);
        MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);
    }

    public function testGetMergeValueWhenNewerValueIsNotNull(): void
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('aaa', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNotNullAndSameAsDefaultValueForAnonymousContact(): void
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = 'aaa';
        $newIsAnonymous = true;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('bbb', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNotNullAndSameAsDefaultValueForIdentifiedContact(): void
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = 'aaa';
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('aaa', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNull(): void
    {
        $newerValue     = null;
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('bbb', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNotNullAndDefaultValueIsZero(): void
    {
        $newerValue     = 0;
        $olderValue     = 1;
        $winnerValue    = 1;
        $defaultValue   = 0;
        $newIsAnonymous = true;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame($winnerValue, $value);
    }
}
