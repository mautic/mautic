<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;

final class LeadListTest extends \PHPUnit\Framework\TestCase
{
    public function testAddLegacyParamsWithEmptyFilters(): void
    {
        $entity = new LeadList();
        $this->assertSame([], $entity->getFilters());
    }

    public function testAddLegacyParamsWithLegacyFilters(): void
    {
        $entity = new LeadList();

        $entity->setFilters(
            [
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'owner_id',
                    'type'     => 'lookup_id',
                    'operator' => '=',
                    'display'  => 'John Doe',
                    'filter'   => '4',
                ],
            ]
        );

        $this->assertSame(
            [
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'owner_id',
                    'type'     => 'lookup_id',
                    'operator' => '=',
                    'display'  => 'John Doe',
                    'filter'   => '4',
                ],
            ],
            $entity->getFilters()
        );
    }

    public function testAddLegacyParamsWithNewFilters(): void
    {
        $entity = new LeadList();

        $entity->setFilters(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => [
                        'filter'  => 'Prague',
                    ],
                ],
            ]
        );

        $this->assertSame(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                    'filter'  => '4',
                    'display' => 'John Doe',
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => [
                        'filter'  => 'Prague',
                    ],
                    'filter'  => 'Prague',
                    'display' => null,
                ],
            ],
            $entity->getFilters()
        );
    }

    public function testAddLegacyParamsWithHybridFilters(): void
    {
        $entity = new LeadList();

        $entity->setFilters(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'filter'     => 'outdated_id',
                    'display'    => 'Outdated Name',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                ],
            ]
        );

        $this->assertSame(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'filter'     => '4',
                    'display'    => 'John Doe',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                ],
            ],
            $entity->getFilters()
        );
    }

    /**
     * @dataProvider setIsGlobalDataProvider
     */
    public function testSetIsGlobal($value, $expected, array $changes): void
    {
        $segment = new LeadList();
        $segment->setIsGlobal($value);

        Assert::assertSame($expected, $segment->getIsGlobal());
        Assert::assertSame($changes, $segment->getChanges());
    }

    public function setIsGlobalDataProvider(): iterable
    {
        yield [null, false, ['isGlobal' => [true, false]]];
        yield [true, true, []];
        yield [false, false, ['isGlobal' => [true, false]]];
        yield ['', false, ['isGlobal' => [true, false]]];
        yield [0, false, ['isGlobal' => [true, false]]];
        yield ['string', true, []];
    }

    /**
     * @dataProvider setIsPreferenceCenterDataProvider
     */
    public function testSetIsPreferenceCenter($value, $expected, array $changes): void
    {
        $segment = new LeadList();
        $segment->setIsPreferenceCenter($value);

        Assert::assertSame($expected, $segment->getIsPreferenceCenter());
        Assert::assertSame($changes, $segment->getChanges());
    }

    public function setIsPreferenceCenterDataProvider(): iterable
    {
        yield [null, false, []];
        yield [true, true, ['isPreferenceCenter' => [false, true]]];
        yield [false, false, []];
        yield ['', false, []];
        yield [0, false, []];
        yield ['string', true, ['isPreferenceCenter' => [false, true]]];
    }
}
