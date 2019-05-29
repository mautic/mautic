<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Deduplicate\Helper;

use Mautic\LeadBundle\Deduplicate\Exception\ValueNotMergeableException;
use Mautic\LeadBundle\Deduplicate\Helper\MergeValueHelper;

class MergeValueHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMergeValueWhenNewAndOldValuesAreIdentical()
    {
        $newerValue     = 'bbb';
        $olderValue     = 'bbb';
        $winnerValue    = null;
        $defaultValue   = null;
        $newIsAnonymous = false;

        $this->expectException(ValueNotMergeableException::class);
        MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);
    }

    public function testGetMergeValueWhenNewAndWinnerValuesAreIdentical()
    {
        $newerValue     = 'bbb';
        $olderValue     = 'aaa';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $this->expectException(ValueNotMergeableException::class);
        MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);
    }

    public function testGetMergeValueWhenNewerValueIsNotNull()
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('aaa', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNotNullAndSameAsDefaultValueForAnonymousContact()
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = 'aaa';
        $newIsAnonymous = true;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('bbb', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNotNullAndSameAsDefaultValueForIdentifiedContact()
    {
        $newerValue     = 'aaa';
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = 'aaa';
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('aaa', $value);
    }

    public function testGetMergeValueWhenNewerValueIsNull()
    {
        $newerValue     = null;
        $olderValue     = 'bbb';
        $winnerValue    = 'bbb';
        $defaultValue   = null;
        $newIsAnonymous = false;

        $value = MergeValueHelper::getMergeValue($newerValue, $olderValue, $winnerValue, $defaultValue, $newIsAnonymous);

        $this->assertSame('bbb', $value);
    }
}
